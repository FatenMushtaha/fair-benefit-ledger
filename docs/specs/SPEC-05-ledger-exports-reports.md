# SPEC-05 — Ledger, Exports & Reports

**Status:** draft — owner approval required

**Planning baseline:** owner-approved [project brief](../project-brief.md) §§4, 6–8, 12, 17; [MVP master plan](../plans/MASTER-PLAN.md); [Plan 05](../plans/PLAN-05-ledger-export-reports.md).
**Scope:** post valid approved acceptances to the cumulative organization ledger, preserve withdrawal/correction history, replace reopened seats through deterministic allocation, and provide authorized XLSX/CSV lists and essential reports. No PDF or future modules.

## Section 17 traceability

| Section 17 requirement | Specification coverage |
|---|---|
| Update cumulative ledger after approval | Slices 1–2 |
| Export distribution/waiting/exception lists and report | Slices 3–4; XLSX/CSV only |
| Audit trail | Slices 1, 2 and 5 |

**Plan acceptance trace:** PLAN-05 AC-05.1–05.2 → Slice 1; AC-05.3 → Slice 2; AC-05.4 → Slice 4; AC-05.5 → Slice 3; AC-05.6 → Slices 3–4; AC-05.7 → Slice 4.

**Technical refinement:** the source decision fingerprint lets download authorization detect results or decisions changed after export creation; this preserves the planned stale-export safety without adding another product feature.

## Requirements and non-functional rules

- Ledger posting occurs only after a valid authorized approval decision. Every posting is idempotent and references its source decision; repeated jobs cannot duplicate a participation.
- Corrections and withdrawals are compensating append-only events/status transitions. Never delete a posted participation, decision or audit event.
- Accepted participant withdrawal reopens capacity and offers the seat to the next eligible waiting-list person through a new SPEC-04 allocation run; previous decision/posting remains visible.
- Reports, exports, joins, jobs, files and downloads are tenant-scoped. Store exports privately, authorize creation and download, use opaque keys and expire per approved retention configuration.
- Export formats are **XLSX and CSV only**. XLSX user data is emitted as text cells; CSV formula injection is neutralized. No PDF, quota, billing or cross-organization feature is included.
- Person-level report/export fields and retention/expiry are **BLOCKED — OWNER DECISION REQUIRED**. Recommendation: least-privilege field allowlist and short configurable expiry; do not expose fields or generate live-data artifacts until approved.

## Ordered implementation slices

### Slice 1 — Idempotent ledger posting after approval

- **Goal/scope:** create cumulative participation records from valid final approvals only.
- **Dependencies:** SPEC-01 approved auth/permission matrix; SPEC-02 core schema; SPEC-03 eligibility; SPEC-04 completed result and decision. Role authority remains blocked until owner matrix approval.
- **Tables/constraints:** `participations(org,person_id,program_id,source_import_row_id?,source_decision_id,status,start_date,end_date,completed_at,withdrawn_at,created_at,...)`; required unique `(org,source_decision_id)` for cycle posting (non-null source decision), composite FKs to same-tenant person/program/decision/import row, indexes `(org,person_id,status)`, `(org,person_id,end_date)`, `(org,program_id,status)`. Append-only `audit_events` records posting actor/time/reason/correlation.
- **Classes/routes/components:** `PostApprovedParticipation` transactional action/job; `Participation` model/status enum; `LedgerPostingPolicy`; `RecordAuditEvent`; posting trigger/action from approved decision workflow.
- **Behavior:** accept only a current valid approval decision for an eligible accepted outcome and same tenant/cycle/program/person; insert once using source-decision uniqueness/idempotency key; record status/date semantics from the approved cycle/program data. No post for waiting, rejected, exception without separately approved posting rule, withdrawn-before-benefit or noncompletion cases. The latter remain visible as participation history if already registered.
- **Authorization/tenant isolation:** service validates active org and decision actor authority; worker payload includes org/cycle/decision IDs and re-establishes context; all links must match org.
- **Edge/error handling:** duplicate execution returns the already-created participation; conflicting second person/program for the same decision is a hard integrity error; missing approval, person resolution, dates or tenant match prevents posting; audit failure rolls back posting transaction.
- **Acceptance/tests:**
  - **AC-05.1** Given a valid approved accepted result, when posting runs, then exactly one tenant-owned participation is created and an audit event records source decision/actor/time; feature tests cover approval gate and atomicity.
  - **AC-05.2** Given retry of the same decision or duplicate job delivery, when posting repeats, then the same participation is returned and no duplicate row is created; job tests verify idempotency.
  - **AC-05.3** Given a waiting/rejected/unresolved/foreign-tenant decision, when posting is requested, then no participation is written; feature tests cover each denial.
- **Definition of Done:** source decision is mandatory and unique; transaction covers ledger and audit; worker retries are safe and tenant-explicit.

### Slice 2 — Withdrawal, correction and waitlist replacement

- **Goal/scope:** preserve history when an accepted participant withdraws or a ledger posting needs correction; release capacity and run a new deterministic allocation.
- **Dependencies:** Slice 1; SPEC-04 run/result history and approved waitlist order; owner-approved authority for withdrawal/correction actions.
- **Tables/constraints:** retain original `participations` and decisions; add append-only `participation_events` or use append-only `audit_events` with typed before/after payload and source IDs; source posting remains immutable. Link replacement decision/posting to new cycle result/run.
- **Classes/routes/components:** `WithdrawParticipant` action, `CompensateParticipation` action, `ReallocateCycle` workflow/job, withdrawal policy, audit recorder, withdrawal/replacement screen actions after approved Figma.
- **Behavior:** an authorized withdrawal appends event and changes current participation status to withdrawn without deleting prior data; release one cycle seat; invoke a new SPEC-04 run against remaining eligible waitlist; only the next valid accepted/approved result may post replacement. Preserve original and replacement provenance; do not auto-approve replacement unless owner-approved automation mode explicitly permits it.
- **Authorization/tenant isolation:** active organization, approved role and reason required; reallocation job carries explicit tenant/cycle IDs; no other tenant's waitlist can be read or moved.
- **Edge/error handling:** repeated withdrawal is idempotent; already completed or non-withdrawable statuses are rejected according to approved lifecycle mapping; no candidate on waitlist leaves capacity vacant and records reason; concurrent seat release is serialized/optimistically locked; failed rerun leaves prior run current.
- **Acceptance/tests:**
  - **AC-05.4** Given a valid accepted participant withdrawal, when recorded, then history remains, capacity reopens, and the next eligible waiting applicant is considered by a new deterministic run; workflow tests cover seat movement.
  - **AC-05.5** Given no eligible waitlist candidate or failed allocation, when withdrawal completes, then no unauthorized ledger posting occurs and the vacancy/failure is audited; tests cover empty/failed queue.
  - **AC-05.6** Given repeated or cross-tenant withdrawal request, when processed, then it is idempotent for the same event and denied for another tenant; feature/job tests cover both.
- **Definition of Done:** no destructive ledger correction; vacancy/replacement history is traceable; new posting waits for the valid approval gate.

### Slice 3 — Tenant-scoped report queries and essential lists

- **Goal/scope:** provide testable cycle lists and summaries derived from current decisions and cumulative ledger.
- **Dependencies:** SPEC-01 authorization; SPEC-02/03/04 data contracts; owner-approved person-level visibility and report audience/definitions. **BLOCKED — OWNER DECISION REQUIRED** for person-level fields, filters and report audience.
- **Tables/constraints:** scoped queries over `cycles`, `cycle_runs`, `cycle_results`, `decisions`, `participations`, `programs`, `people`, `audit_events`; every raw join/aggregate includes explicit organization predicate. No cross-org summary.
- **Classes/routes/components:** `CycleReportQuery`, `LedgerCoverageQuery`, `PriorBenefitSummaryQuery`, report DTOs, report policy, named list/report routes and Livewire list/filter views only after approved Figma.
- **Behavior:** MVP reports include accepted/approved list, ordered waiting list, exceptions/reasons list, cycle outcome totals, and prior-benefit/coverage summary derived from scoped ledger. Every report states active filters and generation timestamp; only fields allowed by owner-approved field allowlist are returned.
- **Authorization/tenant isolation:** report/list permission checked on every request; service queries scope all joined tables; guessed cycle IDs and filter IDs from another org deny.
- **Edge/error handling:** empty cycles produce zero-count/empty results; stale current run is clearly unavailable; invalid filters reject; missing data is labeled unverified rather than omitted as zero; query failures reveal no SQL/private data.
- **Acceptance/tests:**
  - **AC-05.7** Given a known synthetic ledger and current cycle, when each report query runs, then counts/lists match source records and filters are included; feature tests verify fixtures and calculation.
  - **AC-05.8** Given tenant A requests report data for tenant B, when any route/query executes, then no foreign rows/counts are returned; feature tests cover direct and aggregate joins.
  - **AC-05.9** Given a viewer/reviewer role, when report fields are requested, then only owner-approved fields/actions are exposed; policy tests use the approved matrix.
- **Definition of Done:** report values reconcile with ledger fixtures; all joins are tenant constrained; audience/fields and UI designs are approved before implementation.

### Slice 4 — Private XLSX/CSV export jobs and safe downloads

- **Goal/scope:** generate authorized accepted, waitlist, exception and essential report exports in XLSX or CSV.
- **Dependencies:** Slice 3; SPEC-02 OpenSpout selection/version validation; approved role matrix, field allowlist, locale and retention/expiry policy. **BLOCKED — OWNER DECISION REQUIRED** for person fields and retention.
- **Tables/constraints:** `exports(org,cycle_id,requester_id,format,locale,status,filters,cycle_run_id,source_decision_fingerprint,private_file_path,expires_at,created_at,...)`; allowed `ExportFormat` enum only XLSX/CSV and `ExportStatus` enum; composite FK requires the selected run to belong to that organization and cycle; indexes `(org,cycle,status)` and `(org,requester,status)`; opaque private file key. Export job records row count and failure reason without storing unnecessary copies. Fingerprint captures immutable decision IDs/outcomes used to create the artifact.
- **Classes/routes/components:** `CreateExport` action, `BuildXlsxExport`/`BuildCsvExport` jobs using approved package, `ExportPolicy`, private storage adapter, safe serializer, named request/status/download routes, export request/status component after approved Figma, translated headings.
- **Behavior:** user selects only approved report type, filters, locale and XLSX/CSV format; request captures current run ID, immutable decision fingerprint and authorized field list; job carries organization ID and rebuilds scope. XLSX user-provided strings are emitted as explicit text cells. CSV text values whose first non-whitespace/control character is `=`, `+`, `-`, or `@`, or begin with tab/CR, are prefixed with apostrophe before CSV quoting; validated server-generated numeric columns remain numeric. Exports use selected Arabic/English headings and direction-aware column order. Recompute source run and decision fingerprint at download; mark artifact stale and deny download if either changed.
- **Authorization/tenant isolation:** authorize creation and each download; file stored outside public webroot under unpredictable tenant-owned path; guessed export ID, path, stale, failed or expired artifact denies without path disclosure; join predicates explicitly scope tenant.
- **Edge/error handling:** no export for unsupported format (including PDF), unavailable report, missing retention/field policy or stale source; chunk/stream rows with bounded memory; retry is idempotent per export request; partial file is deleted only under approved retention rules and job failure is auditable.
- **Acceptance/tests:**
  - **AC-05.10** Given an authorized export request in XLSX or CSV, when generated, then rows/headings/locale match authorized filters and the captured run; export tests cover content and Arabic/English.
  - **AC-05.11** Given spreadsheet-dangerous text values, when exported, then XLSX stores them as text and CSV neutralizes dangerous prefixes without altering validated numeric values; tests cover `=`, `+`, `-`, `@`, tab/CR, whitespace and numeric columns.
  - **AC-05.12** Given foreign, stale, failed, expired or guessed export ID, when downloaded, then access is denied without leaking file path or foreign metadata; feature tests cover two tenants and each status.
  - **AC-05.13** Given a PDF/unsupported format request, when submitted, then validation rejects it and creates no job/artifact; feature test verifies scope boundary.
- **Definition of Done:** only XLSX/CSV exist; jobs are tenant-scoped, streaming and retry-safe; formula injection, authorization, expiry/staleness and private storage tests pass.

### Slice 5 — Append-only audit integrity and reconciliation

- **Goal/scope:** make ledger/export/review events auditable and verify report/ledger reconciliation without changing history.
- **Dependencies:** Slices 1–4; SPEC-02 append-only audit base.
- **Tables/constraints:** `audit_events(org,actor_id,event_type,subject_type,subject_id,reason,payload,occurred_at,correlation_id)` append-only; indexes `(org,occurred_at)` and `(org,subject_type,subject_id)`. Append-only `participation_events` if separate from audit is not required; use one canonical event path.
- **Classes/routes/components:** audit writer interface/action, audit read policy/query, ledger reconciliation command/service invoked by an authorized operator, audit view route only after approved design.
- **Behavior:** record posting, correction, withdrawal, replacement, export creation/download and failure events with actor/system identity, reason, timestamp and correlation; application exposes no update/delete path and runtime database credentials have no audit update/delete privilege (migrations use separate credentials). Reconciliation compares current participation postings with source decisions and reports discrepancies; it never auto-repairs or overwrites data.
- **Authorization/tenant isolation:** audit list is tenant-scoped and restricted to owner-approved role; system jobs carry org ID; DB/app permissions prevent update/delete.
- **Edge/error handling:** failed audit write aborts the related financial/ledger mutation; missing actor is explicit system actor, not null without explanation; discrepancy output is read-only and safe.
- **Acceptance/tests:**
  - **AC-05.14** Given posting, withdrawal, correction or export lifecycle actions, when performed, then append-only events contain actor, timestamp, reason/event type and tenant; feature tests assert required fields.
  - **AC-05.15** Given an audit event, when an app path attempts update/delete, then the operation is denied; feature/database permission tests verify immutability.
  - **AC-05.16** Given synthetic source decisions and ledger rows, when reconciliation runs, then it reports mismatches without changing records; service tests verify both matched and discrepant cases.
- **Definition of Done:** audit is immutable, tenant-scoped and transactionally coupled to ledger writes; runtime privileges prevent update/delete; reconciliation is read-only and tested.

## Rollback and test strategy

- Stop posting/export workers before rollback. Never delete ledger postings, decisions or audit history; compensate with an authorized append-only event. Reopen capacity through a new SPEC-04 run. Delete private partial exports only under approved retention rules.
- PHPUnit feature tests cover idempotency, approval gates, tenant isolation, reports, export safety, access expiry/staleness and audit immutability. Job tests cover retry/streaming; query tests compare against synthetic ledger fixtures. No real personal data.
- Manual verification: compare XLSX/CSV in Arabic/English with approved designs; verify RTL column order, mixed-script bidi handling, accessible download states and private storage configuration.

## Open questions / conflicts

- **BLOCKED — OWNER DECISION REQUIRED:** permission to approve/post, withdraw/correct, see person-level data, create/download each report/export.
- **BLOCKED — OWNER DECISION REQUIRED:** whether an approved exception is eligible for ledger posting. Recommendation: post only when the explicit approved decision outcome maps to a valid accepted participation; otherwise preserve the exception without posting.
- **BLOCKED — OWNER DECISION REQUIRED:** field allowlist, report audience/filters, default export locale and retention/expiry policy; recommendation: least privilege and configurable expiry, without a hardcoded retention period.
- **No PDF:** PDF, quotas, billing, cross-organization matching and public registries are explicitly excluded.
- **Technical dependency:** confirm installed OpenSpout version and output API before implementation; no install is performed in this Specs task.
