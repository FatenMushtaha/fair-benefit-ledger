# SPEC-02 — Core Schema & Import

**Status:** owner-approved — implementation still requires slice approval

**Planning baseline:** owner-approved [project brief](../project-brief.md) §17, [MVP master plan](../plans/MASTER-PLAN.md), and [Plan 02](../plans/PLAN-02-core-schema-import.md); approved intent-baseline alternative in [spec-writer](../../.agents/skills/spec-writer/SKILL.md).
**Scope:** tenant-safe core ledger/cycle/rules/audit schema and private, validated XLSX/CSV staging, mapping, normalization, chunk processing and quality summaries. No application changes are authorized by this draft.

## Section 17 traceability

| Section 17 requirement | Specification coverage |
|---|---|
| XLSX/CSV import, column mapping and cleaning | Slices 2–5 |
| Audit trail, security and validation safeguards | Slices 1–5 |

**Plan acceptance trace:** PLAN-02 AC-02.1 → Slice 1; AC-02.2 → Slice 3; AC-02.3 → Slices 2 and 5; AC-02.4 → Slice 3; AC-02.5 → Slice 4; AC-02.6 → Slices 2 and 5; AC-02.7 → Slice 1; AC-02.8 → Slices 1–2.

**Technical refinement:** nullable `authoritative_key` enforces uniqueness only for owner-designated identifier types while allowing duplicate supporting values; it is an implementation of the approved per-organization uniqueness rule, not a new identity policy.

## Requirements and contracts

- Build after SPEC-01 tenant context. All tenant rows have non-null indexed `organization_id`; every tenant-to-tenant FK includes the same organization key and parent unique `(organization_id,id)`. Per-tenant uniqueness starts with organization ID.
- Initial core entities: `people`, `person_identifiers`, `person_names`, `programs`, `participations`, `rule_sets`, immutable `rule_versions`, `cycles`, `import_batches`, `import_rows`, `column_mappings`, and append-only `audit_events`. Cycle/applicant/matching tables are added in SPEC-03 as consumers of staged rows.
- `import_batches` has exactly one target: cycle applicants (`cycle_id`) or historical participation (`program_id`); enforce in validation and portable constraints where supported. Files are private and every access is authorized.
- Import accepts XLSX and CSV only. Validate content, structure, required mapped fields and configured size/row limits before row jobs. Invalid rows retain row number, original source, and reason, and cannot enter matching or ledger writes until corrected/revalidated.
- Preserve raw source values unchanged; deterministic normalized values live separately. A retry processes each `(organization_id, import_batch_id, row_number)` at most once.
- OpenSpout is selected for streaming XLSX/CSV and bounded memory; confirm installed version/API before implementation. No package is installed during this Specs task.
- **Approved policy:** required fields, accepted formats/date formats, and quality/circuit-breaker thresholds are configurable per organization and pilot; missing configuration leaves the batch in `NeedsConfiguration` and blocks downstream materialization. Concrete first-pilot values must be supplied before that pilot batch is processed.

## Ordered implementation slices

### Slice 1 — Tenant-safe core schema and rule/cycle records

- **Goal/scope:** create core tenant entities and portable constraints for people, identifiers/names, programs, participations, rules and cycles.
- **Dependencies:** SPEC-01 Slices 1–3 approved and implemented; tenant scope/authorization available.
- **Tables/constraints:** `people(org,id,display_name,normalized_name,date_of_birth,phone,timestamps)`; `person_identifiers(org,person_id,type,normalized_value,authoritative_key?,...)`; `person_names(org,person_id,name,normalized_name,script,source,...)`; `programs(org,name,sector,status,...)`; `participations(org,person_id,program_id,import_row_id?,source_decision_id?,status,start_date,end_date,completion/withdrawal timestamps,...)`; `rule_sets(org,name,purpose,status,current_version_id?)`; immutable `rule_versions(org,rule_set_id,version,config,created_by,created_at)` unique `(org,rule_set_id,version)`; `cycles(org,program_id,rule_version_id,name,status,start_date,available_quantity,automation_mode,...)`. Add parent composite unique `(org,id)`, tenant-safe composite FKs and query indexes. Enforce authoritative identifier uniqueness with nullable `authoritative_key` and unique `(org,authoritative_key)`; populate it only for owner-designated authoritative type/value pairs and leave it null for supporting identifiers. This avoids treating phones/supporting values as unique and is portable to MySQL/MariaDB nullable unique semantics.
- **Classes/routes/components:** migrations, Eloquent models/relations/casts, organization trait/global scope, factories, `RuleVersion` immutability guard, policies, `PersonStatus`, `ParticipationStatus`, `CycleStatus`, `RuleVersionStatus` enums. No frontend beyond existing approved MVP designs.
- **Behavior:** all inserts derive tenant ID from active context; cycles pin one immutable rule version; used rule versions cannot be edited. Supporting identifiers (for example phone) are not assumed unique.
- **Authorization/tenant isolation:** all reads and writes are tenant scoped; parent-child links must match `organization_id` at DB and policy levels. Global user/actor references are allowed only with membership authorization.
- **Edge/error handling:** missing/deleted referenced parent is rejected; attempt to mutate a version used by a cycle is denied; optional nullable source references preserve historical import semantics.
- **Acceptance/tests:**
  - **AC-02.1** Given tenant A and tenant B records, when a child links to the other tenant's parent, then database constraints reject it; migration/feature tests cover composite FKs and uniqueness.
  - **AC-02.2** Given a tenant model is created in active context, when saved, then ownership comes from context and cross-tenant reads/updates/deletes fail; feature tests use two organizations.
  - **AC-02.3** Given a cycle is created, when a rule version is later changed, then the cycle's pinned configuration remains immutable; model/service tests cover version sequencing and mutation denial.
- **Definition of Done:** schema matches portable MySQL/MariaDB requirements; tenant FK/unique/index tests pass; factories use synthetic tenant-owned data; append-only behavior for used rules is tested.

### Slice 2 — Import staging, mappings and append-only intake audit

- **Goal/scope:** persist private batch metadata, source rows and explicit column mapping separately from normalized values.
- **Dependencies:** Slice 1; SPEC-01 tenant context. Downstream materialization waits for SPEC-03 identity resolution.
- **Tables/constraints:** `import_batches(org,purpose,cycle_id?,program_id?,uploader_id,private_path,format,status,counts,cursor,quality_summary,error_summary,...)`, exactly one applicable target; `import_rows(org,import_batch_id,row_number,raw_payload,mapped_payload,cleaned_payload,status,errors,person_id?,...)`, unique `(org,batch,row_number)`; `column_mappings(org,owner_id?,source_headers,canonical_map,locale_hints,approved,...)`; append event types to the tenant-scoped `audit_events` table introduced in SPEC-01—do not create a second audit table. Composite tenant FKs and batch/status indexes.
- **Classes/routes/components:** `ImportBatch`, `ImportRow`, `ColumnMapping`, `ImportUploadRequest`, upload/mapping policies, private storage adapter, named upload/show/map/confirm routes, Livewire upload/mapping component matching the provisional Figma baseline, audit action.
- **Behavior:** accept only intended target (cycle or historical program); store original privately with opaque key; store headers and mapping proposal; require explicit user confirmation before row processing. Raw payload is immutable; corrections create a new mapped/cleaned value and audit event.
- **Authorization/tenant isolation:** only Admin and Data Entry may upload/manage imports, and only for the active organization; raw source-file access is limited to authorized import operations. Every row, file and mapping query includes tenant scope; foreign IDs return denied/not-found without disclosure.
- **Edge/error handling:** reject both/neither target, empty file, duplicate header names unless disambiguated, unsafe storage failure, malformed mapping, and failed upload; remove uncommitted partial file and create no processable batch.
- **Acceptance/tests:**
  - **AC-02.4** Given a valid tenant-authorized upload, when staged, then batch and source rows belong to that organization and original values are preserved; feature tests verify private storage and raw immutability.
  - **AC-02.5** Given invalid target selection, ambiguous headers or unauthorized actor, when upload/mapping is submitted, then it is rejected without processable rows; request tests cover each failure.
  - **AC-02.6** Given mapping confirmation/correction, when recorded, then actor, time, before/after mapping and reason are auditable; feature tests assert append-only events.
- **Definition of Done:** private file paths are not exposed; all batch-row relationships are tenant-safe; mapping requires confirmation; audit history survives correction.

### Slice 3 — File preflight and deterministic field mapping/cleaning

- **Goal/scope:** reject unsupported/unsafe inputs before jobs and convert confirmed rows to canonical staged values without changing source data.
- **Dependencies:** Slice 2; organization-configured canonical fields, source formats/date formats, and pilot quality policy. These concrete values are required before a batch becomes processable; absent configuration produces `NeedsConfiguration` rather than a guessed default.
- **Tables/constraints:** batch preflight status/format/headers/config snapshot; row mapped and cleaned payload, validation status/errors. No new identity or participation writes here.
- **Classes/routes/components:** `SpreadsheetPreflight`, OpenSpout reader adapter, `ColumnMappingValidator`, deterministic `FieldNormalizer`, XLSX/CSV content validators, per-format parsing rules, validation message translation keys.
- **Behavior:** check extension and actual content; enforce configured byte/row limits (limits must be explicitly configured and may not silently be unlimited); reject malformed workbook/CSV, missing required canonical fields, duplicate ambiguous headers, and formula cells in mapped fields without evaluating formulas. Only process confirmed mappings. Normalize whitespace, Arabic/Western digits, dates and identifiers into separate fields; retain raw data unchanged. Ambiguous date/numeric formats are invalid until corrected. Do not use LLM output as accepted mapping or cleaning without explicit human confirmation.
- **Authorization/tenant isolation:** preflight job receives batch and organization IDs; reads only the private file for that tenant; never accepts arbitrary file paths.
- **Edge/error handling:** encoding errors, formula cells, invalid dates/numbers, blank required values, oversized files and unknown columns create safe batch/row errors; no stack trace/path is displayed. Numeric/date parse policy is configuration-driven; if ambiguous, mark invalid rather than guess.
- **Acceptance/tests:**
  - **AC-02.7** Given XLSX/CSV within configured limits and valid content, when preflight runs, then it confirms format and headers before dispatch; tests cover both accepted formats.
  - **AC-02.8** Given unsupported extension/content, malformed file, formula in a mapped field, missing required field, or exceeded limit, when uploaded, then no processing job runs and a safe error is recorded; feature tests cover each.
  - **AC-02.9** Given Arabic-Indic digits, whitespace and valid configured date formats, when normalized, then canonical output is deterministic and raw values are unchanged; unit tests cover Arabic/Western/mixed inputs and ambiguous values.
- **Definition of Done:** rejection occurs before row jobs; error summaries are safe and translated; normalization is repeatable and source-preserving.

### Slice 4 — Chunked row processing, retry and quality gate

- **Goal/scope:** stage validated rows in resumable chunks and stop downstream processing when quality policy is missing or thresholds are exceeded.
- **Dependencies:** Slice 3; database queue configured; SPEC-03 required to materialize identity/applicant/participation links.
- **Tables/constraints:** batch status/cursor/progress/quality summary; rows unique by `(org,batch,row_number)`; per-row processing idempotency key. Audit events for validation completion, pause/failure and retry.
- **Classes/routes/components:** `ProcessImportBatch` queued job, `ProcessImportChunk` action, `ImportQualityEvaluator`, progress/status enums, retry/resume action, quality summary component after design approval.
- **Behavior:** use Laravel's database queue driver. Chunk job carries explicit org/batch IDs and re-establishes tenant scope; each row write is idempotent; retry resumes unprocessed rows and does not duplicate staged rows. Quality evaluator reports totals/invalid/duplicate counts, compares against owner-configured thresholds, and gates downstream handoff. If policy is absent, status is `NeedsConfiguration`; if exceeded, status is `NeedsReview`; no automatic person/applicant/ledger write occurs.
- **Authorization/tenant isolation:** only authorized active-org users can view/retry; worker rejects org/batch mismatch; raw queries include organization filter.
- **Edge/error handling:** worker crash leaves cursor at last committed chunk; retries do not double count; permanent parser error marks batch failed with safe report; partial file remains private until retention policy allows cleanup.
- **Acceptance/tests:**
  - **AC-02.10** Given a batch and a retry after partial completion, when chunks resume, then each `(org,batch,row)` is staged once and progress is monotonic; job tests cover crash/retry boundaries.
  - **AC-02.11** Given missing or exceeded quality thresholds, when evaluation completes, then downstream handoff is blocked with row-level reasons and summary; tests cover unconfigured, below, equal and above boundaries using supplied config.
  - **AC-02.12** Given a job is given another tenant's batch ID, when it runs, then it reads/writes no foreign data and records a safe failure; job isolation tests cover mismatched IDs.
- **Definition of Done:** jobs are retry-safe, bounded-memory and tenant-explicit; progress/errors are observable; no row proceeds beyond staging without valid quality approval.

### Slice 5 — Import review and correction handoff

- **Goal/scope:** allow authorized correction/revalidation of invalid staged rows and hand off only valid confirmed rows to matching.
- **Dependencies:** Slice 4; SPEC-01 approved role matrix; SPEC-03 matching contract. The provisional Figma baseline is approved; inspect the import review/correction context and verify missing-configuration and failure interactions before coding this UI.
- **Tables/constraints:** `import_rows` correction version/audit metadata; batch summary; no direct participation creation in this slice.
- **Classes/routes/components:** correction action/request, import review policy, validation services, review routes/Livewire table and error download after approved design, `ImportAuditRecorder`.
- **Behavior:** authorized correction updates mapped/cleaned representation while preserving raw source and appending actor/reason/time; rerun validation; only `Valid` rows in an approved batch are exposed to SPEC-03 matching. Invalid rows remain excluded.
- **Authorization/tenant isolation:** Admin and Data Entry may correct source rows and access only the operational records needed for those tasks; all correction/list/download actions enforce active organization and approved capability. Reviewer and Viewer have no raw import-file access.
- **Edge/error handling:** malformed correction leaves previous version intact; stale concurrent corrections fail with a conflict; invalid rows cannot be force-marked valid without validation.
- **Acceptance/tests:**
  - **AC-02.13** Given an invalid row, when a valid correction is submitted by an authorized user, then raw data remains and the new normalized value is revalidated and audited; feature tests cover success and invalid correction.
  - **AC-02.14** Given a batch contains invalid/unapproved rows, when matching handoff is requested, then only valid confirmed rows are handed off; feature tests cover row filtering and cross-tenant denial.
- **Definition of Done:** correction is versioned/audited, raw source is immutable, and the matching boundary accepts only valid rows.

## Rollback and test strategy

- Pause import workers before rollback. Keep source files and raw rows under owner-approved retention; clean abandoned staging only via tenant-scoped audited procedure. Reversible schema changes may be rolled back only before dependent records exist.
- PHPUnit feature tests cover upload validation, mapping/correction authorization, two-tenant isolation, storage privacy, validation errors and handoff. Job tests cover chunk/retry/resume. Unit tests cover normalizers and quality threshold boundaries. Use synthetic data only.
- Manual verification: inspect synthetic XLSX/CSV samples with Arabic, English and mixed headers; verify errors and progress against the provisional Figma baseline before the relevant UI slice.

## Open questions / conflicts

- **Configuration checkpoint before the pilot batch:** the policy is approved as configurable per organization, but the first pilot's required fields, authoritative identifier types, accepted source formats/date formats, size/row limits, and quality thresholds must be entered and reviewed before processing. Missing required configuration blocks the batch in `NeedsConfiguration`.
- **BLOCKED before live data:** jurisdiction/hosting, encryption, and raw upload/identifier retention/deletion policy. Use synthetic data until safeguards are approved; do not infer a retention period.
- **Spec-level implementation check:** confirm installed OpenSpout version and supported XLSX/CSV reader API before coding; no package installation is part of this task.
