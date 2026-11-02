# PLAN-05: Ledger, Exports and Reports (Draft)

## Goal and scope

After human approval, update cumulative participation ledger safely, support withdrawal/replacement without losing decision history, and provide bilingual export lists and operational/fairness reports. Covers phases (h) and (i).

## Tables, files and classes

- Tables: tenant-scoped `participations`, `cycles`, `cycle_results`, append-only `decisions`, `audit_events`, `exports`; participation has a required tenant-scoped unique source-decision/posting key to prevent duplicate ledger writes. Report queries use scoped ledger, cycle, score and result records.
- Files/classes: transactional/idempotent ledger-post action; compensating reversal workflow; withdrawal/waitlist cascade action; queued XLSX/CSV exporters using approved packages; report query services; export policy/controller/Livewire actions; private expiring file storage; translations. PDF export is excluded from MVP and remains an open future-scope question.
- Confirm permitted packages and installed versions before implementation. Avoid persisting unnecessary report copies of sensitive data.

## Acceptance criteria and tests

- **AC-05.1** Only valid, authorized approved decisions post eligible acceptances to the cumulative ledger — feature tests for approval gate, status/date semantics and excluded withdrawals/noncompletion.
- **AC-05.2** Repeated job execution does not duplicate ledger participations; correction uses a compensating auditable action — job/service tests for idempotence and reversal history.
- **AC-05.3** Accepted participant withdrawal reopens capacity and offers the seat to the next eligible waitlisted applicant through a new deterministic allocation; prior posting and decisions remain visible — workflow tests for seat replacement, duplicate prevention and audit history.
- **AC-05.4** Authorized XLSX and CSV exports include accepted, waitlist and exception lists with translated headings; CSV neutralizes spreadsheet formula-injection cells — export tests for content, dangerous cell prefixes, locale, bidi names and generated status.
- **AC-05.5** Essential reports provide cycle outcomes and prior-benefit/coverage summaries that agree with scoped ledger records and state their filters — query/report tests against known synthetic fixtures.
- **AC-05.6** Download access, jobs, and raw report joins remain organization-scoped, including guessed export IDs — policy/feature tests with two organizations.
- **AC-05.7** Expired or failed exports cannot be downloaded and errors do not expose private paths — access/expiry tests.

## Tenant-isolation checklist

- Participation, export and report sources are tenant scoped; tenant-safe constraints prevent cross-organization links; jobs carry organization/cycle IDs and re-establish scope.
- Every raw query/aggregate/join manually constrains `organization_id`; private storage paths are tenant-specific and non-guessable.
- Export permissions are checked at creation and download; test organization A cannot enumerate or download B's artifacts.
- Ledger posting and compensations carry tenant IDs and append audit events; test retries cannot duplicate or affect another tenant.

## RTL/bilingual checklist

- XLSX/CSV headings, statuses, reasons, dates and number formats use matching translation keys and selected locale.
- Column order/alignment follows direction; mixed-script names/IDs remain readable and dates/numbers are locale-aware.
- Test representative Arabic and English exports and reports, including RTL metadata/fonts and no raw translation keys.

## Rollback, risks and open questions

- Stop export/posting workers before rollback. Remove incomplete generated files only after authorization/retention checks. Never erase audit/decision history; reverse ledger effects through compensating events and restore snapshots when needed. Never “undo” a posted participation by deleting it.
- Risks: duplicate posting, stale exports after decisions change, report leakage through raw joins and large export memory/time.
- MVP-blocking before live data: retention/deletion policy and hosting/data-law safeguards must be settled; define who may see person-level export fields under the approved permission matrix.
- Spec-level: exact report filters/definitions, export locale default, stale-export invalidation, worker limits and safe CSV neutralization details.
- PDF is an open question for a later scope decision. Scheduled reports and complex appeals are deferred; no PDF generation is in this MVP plan.
