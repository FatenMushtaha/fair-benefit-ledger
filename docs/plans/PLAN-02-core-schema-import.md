# PLAN-02: Core Schema and Import (Draft)

## Goal and scope

Create organization-scoped ledger/cycle/rule/audit structures and reliable Excel/CSV intake with mapping, cleaning and quality checks. Covers phases (b) and (c) of the [master plan](MASTER-PLAN.md). Build after foundation approval.

## Tables, files and classes

- Tables: `people`, `person_identifiers`, `person_names`, `programs`, `participations`, `rule_sets`, immutable `rule_versions`, `cycles`, `import_batches`, `import_rows`, `column_mappings`, append-only `audit_events`; queue tables remain framework infrastructure.
- Key indexes: tenant-prefixed unique identifier `(organization_id,type,value)`; person/program/status/date indexes; rule version unique `(organization_id,rule_set_id,version)`; cycle status/program indexes; unique import row `(organization_id,import_batch_id,row_number)` and indexed batch/status.
- Files/classes: migrations/models/factories, tenant trait/scope, policies, import upload/stream reader, column-map suggestion/approval action, normalization/cleaning services, chunk jobs, progress/status enums, quality/circuit-breaker report, private storage config, translations and Livewire import/mapping screens.
- Choose one approved spreadsheet package after checking versions and formats; prefer OpenSpout for streaming large XLSX/CSV input with bounded memory. CSV library may be reused where useful. No package install without checking current dependencies and approval constraints.

## Acceptance criteria and tests

- **AC-02.1** Tenant schema and relationships cannot cross organizations — migration/feature tests for unique constraints, scoped relation access, automatic organization ID and cross-tenant denial.
- **AC-02.2** CSV/XLSX files stream in chunks and resume/retry idempotently — job tests for chunk boundaries, retry without duplicate rows, progress counts and failure state.
- **AC-02.3** Arabic/English/mixed headers map to canonical fields with user confirmation — feature tests for suggested mapping review and rejected/ambiguous required columns.
- **AC-02.4** Cleaning normalizes Arabic-Indic digits, whitespace, dates and identifiers without silently destroying originals — service tests for stable normalization and retained raw values.
- **AC-02.5** Abnormal duplicate/header/error rates stop processing before downstream work — tests for circuit-breaker thresholds, quality summary and recoverable status.
- **AC-02.6** Rules are versioned and each cycle pins the version it used — tests for immutable used version and cycle provenance.
- **AC-02.7** Audit events record uploader, mapping changes, reasons and timestamps and cannot be edited/deleted through app paths — feature tests for append behavior.

## Tenant-isolation checklist

- `organization_id` non-null indexed FK on every listed tenant table; tenant-local uniqueness includes it.
- Jobs carry organization and batch IDs explicitly, restore tenant context, and scope every write/read.
- Private file paths and exports never trust user-supplied paths; authorize access by organization.
- Factories create tenant-owned records; test A cannot read/update/delete B rows and retry/export/query boundaries remain scoped.

## RTL/bilingual checklist

- Translate upload, mapping, progress, validation, quality and error states in both languages.
- Test Arabic, English and mixed headers, digit/date normalization, bidi-safe user values and direction-aware table/field order.
- Import UI renders with correct `lang`/`dir` and no raw keys; use logical Tailwind utilities and approved Figma tokens.

## Rollback, risks and open questions

- Stop workers before rollback; reverse schema only if no dependent records exist. Preserve source files/audit per retention policy; clean abandoned staging rows in a tenant-scoped, auditable operation.
- Risks: memory spikes, malformed/malicious files, lossy normalization, duplicate data and schema lock time.
- Open: raw upload retention, encryption/identifier policy, pilot formats and expected volume. Spreadsheet package choice must be checked against installed versions before implementation.
