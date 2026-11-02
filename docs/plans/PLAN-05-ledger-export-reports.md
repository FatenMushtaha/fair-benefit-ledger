# PLAN-05: Ledger, Exports and Reports (Draft)

## Goal and scope

After human approval, update cumulative participation ledger safely, support withdrawal/replacement without losing decision history, and provide bilingual export lists and operational/fairness reports. Covers phases (h) and (i).

## Tables, files and classes

- Tables: `participations`, `cycles`, `cycle_results`, append-only `decisions`, `audit_events`, `exports`; report queries use scoped ledger, cycle, score and result records. Add posting/idempotency key if needed to prevent duplicate ledger writes.
- Files/classes: transactional/idempotent ledger-post action; compensating reversal workflow; withdrawal/waitlist cascade action; queued streaming Excel/PDF exporters using approved packages; report query services; export policy/controller/Livewire actions; private expiring file storage; translations.
- Confirm permitted packages and installed versions before implementation. Avoid persisting unnecessary report copies of sensitive data.

## Acceptance criteria and tests

- **AC-05.1** Only approved decisions post eligible acceptances to the cumulative ledger — feature tests for approval gate, status/date semantics and excluded withdrawals/noncompletion.
- **AC-05.2** Repeated job execution does not duplicate ledger participations; correction uses a compensating auditable action — job/service tests for idempotence and reversal history.
- **AC-05.3** Accepted participant withdrawal reopens capacity and moves next eligible waitlisted applicant according to a new deterministic allocation — workflow tests for seat replacement and audit trail.
- **AC-05.4** Excel and PDF exports include authorized accepted/waitlist/exception lists and report headings in selected language — export tests for content, locale, bidi names and generated status.
- **AC-05.5** Coverage and prior-benefit summaries agree with scoped ledger records and explain filters — query/report tests against known synthetic fixtures.
- **AC-05.6** Download access, jobs, and raw report joins remain organization-scoped, including guessed export IDs — policy/feature tests with two organizations.
- **AC-05.7** Expired or failed exports cannot be downloaded and errors do not expose private paths — access/expiry tests.

## Tenant-isolation checklist

- Participation, export and report sources are tenant scoped; jobs carry organization/cycle IDs and re-establish scope.
- Every raw query/aggregate/join manually constrains `organization_id`; private storage paths are tenant-specific and non-guessable.
- Export permissions are checked at creation and download; test organization A cannot enumerate or download B's artifacts.
- Ledger posting and compensations carry tenant IDs and append audit events; test retries cannot duplicate or affect another tenant.

## RTL/bilingual checklist

- Excel/PDF headings, statuses, reasons, dates and number formats use matching translation keys and selected locale.
- Column order/alignment follows direction; mixed-script names/IDs remain readable and dates/numbers are locale-aware.
- Test representative Arabic and English exports and reports, including RTL metadata/fonts and no raw translation keys.

## Rollback, risks and open questions

- Stop export/posting workers before rollback. Remove incomplete generated files only after authorization/retention checks. Never erase audit/decision history; reverse ledger effects through compensating events and restore snapshots when needed.
- Risks: duplicate posting, stale exports after decisions change, report leakage through raw joins, unsupported Arabic PDF fonts, large export memory/time.
- Open: retention/deletion and appeal policy, exact report definitions and audience, export locale default, whether reports are scheduled (later scope), hosting/data-law requirements.
