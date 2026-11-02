# PLAN-02: Core Schema and Import (Draft)

## Goal and scope

Create the tenant-safe ledger/cycle/rule/audit schema and validated XLSX/CSV intake for both historical participation lists and new cycle applicants, with mapping, cleaning, row errors, and a minimum quality report. Covers phases (b) and (c) of the [master plan](MASTER-PLAN.md). Build after foundation approval.

## Tables, files and classes

- Tables: `people`, `person_identifiers`, `person_names`, `programs`, `participations`, `rule_sets`, immutable `rule_versions`, `cycles`, `import_batches`, `import_rows`, `column_mappings`, append-only `audit_events`; queue tables remain framework infrastructure. `import_batches` targets either a new-applicant `cycle_id` or a historical `program_id`; historical rows may produce participations without a cycle.
- Key constraints/indexes: each tenant row has non-null `organization_id`; tenant-to-tenant FKs use `(organization_id,referenced_id)` and matching unique parent keys. Per-tenant authoritative identifier uniqueness is `(organization_id,type,normalized_value)` only for owner-designated authoritative types; supporting identifiers are not assumed unique. Rule versions are unique `(organization_id,rule_set_id,version)`. Import rows are unique `(organization_id,import_batch_id,row_number)` and indexed by batch/status.
- Files/classes: migrations/models/factories, tenant trait/scope, policies, private upload and streaming reader, column-map suggestion/approval, normalization/cleaning services, chunk jobs, progress/status enums, quality/error summary, private storage config, translations and import/mapping screen plan. Keep the pipeline to staging rows, validation, review, and tenant-safe writes; no extra orchestration infrastructure.
- Choose one approved spreadsheet package after checking versions and formats; prefer OpenSpout for streaming large XLSX/CSV input with bounded memory. CSV library may be reused where useful. No package install without checking current dependencies and approval constraints.

## Acceptance criteria and tests

- **AC-02.1** Database constraints and scoped policies reject tenant-owned rows linked to another organization's parent — migration/feature tests for composite tenant FKs, per-tenant uniqueness, ownership assignment, and cross-tenant denial.
- **AC-02.2** Only XLSX/CSV files with permitted content/size and required headers enter row processing; malformed/unsupported files are rejected with a safe message before jobs run — feature tests for wrong extension/content, missing headers, malformed workbook/CSV, and oversized input.
- **AC-02.3** Required columns map to canonical fields with explicit confirmation; invalid rows are retained with row number/reason but excluded from matching/ledger writes until corrected — tests for ambiguous/missing fields and invalid-record isolation.
- **AC-02.4** Cleaning normalizes Arabic-Indic digits, whitespace, dates and identifiers without overwriting raw source values — service tests for deterministic normalization and original retention.
- **AC-02.5** A retry resumes each `(organization_id,import_batch_id,row_number)` exactly once and cannot duplicate persons, applicants, or historical participations — job tests for chunk boundaries, retries, duplicate submissions and progress/failure recovery.
- **AC-02.6** Duplicate/error quality thresholds stop downstream processing and produce a minimum file-quality/error summary — tests at configured threshold boundaries. Thresholds are not numerically fixed in this plan.
- **AC-02.7** Rules are versioned and each cycle pins the version it used — tests for immutable used version and cycle provenance.
- **AC-02.8** Audit events record uploader, mapping/validation decisions, actor, reason and time and cannot be edited/deleted through app paths — feature tests for append behavior.

## Tenant-isolation checklist

- `organization_id` non-null indexed FK on every tenant table; tenant-local uniqueness includes it and tenant-to-tenant FKs include it on both sides.
- Jobs carry organization and batch IDs explicitly, restore tenant context, and scope every write/read.
- Validate file extension and actual reader/content before queueing; store uploads outside public storage under opaque organization-owned paths and authorize every view/download by active membership/role.
- Factories create tenant-owned records; test A cannot read/update/delete B rows and retry/export/query boundaries remain scoped.

## RTL/bilingual checklist

- Translate upload, mapping, progress, validation, quality and error states in both languages.
- Test Arabic, English and mixed headers, digit/date normalization, bidi-safe user values and direction-aware table/field order.
- Import UI renders with correct `lang`/`dir` and no raw keys; use logical Tailwind utilities and approved Figma tokens.

## Rollback, risks and open questions

- Stop workers before rollback; reverse schema only if no dependent records exist. Preserve source files/audit according to an owner-approved retention policy; delete abandoned staging rows only through a tenant-scoped, auditable cleanup.
- Risks: memory spikes, malformed/malicious files, lossy normalization, duplicate data and schema lock time.
- MVP-blocking before live data: owner must set raw-upload/identifier retention and encryption policy; until then use synthetic data only.
- Spec-level: exact file size/row limits, header dictionaries, parser safety checks, duplicate/error circuit-breaker values, normalization rules and package/version. Check installed packages before choosing; OpenSpout is a candidate for streaming XLSX, not a settled dependency.
- Pilot inputs: sample/anonymized formats and expected volumes are needed for validation/performance tests; they do not block this plan review.
