# MVP Master Plan (Draft)

This plan covers MVP scope in [project brief §17](../project-brief.md#17-mvp-scope). It is a planning baseline, not implementation approval. All five phase plans remain **draft** and require owner approval before implementation.

## Architecture and module map

- Laravel 12 monolith: Blade + Livewire + Tailwind/Vite; no SPA or API-first layer. Implement screens from approved Figma frames and tokens.
- Authentication, organization membership, active-organization selection, and authorization policies form the tenant boundary. A user may belong to several organizations; every organization switch and request revalidates membership. Tenant models use the shared organization trait/global scope; jobs and raw queries enforce tenant filters explicitly.
- Domain services/actions in `app/Services` and `app/Actions` implement import normalization, matching, eligibility, scoring/ranking, allocation and ledger posting. Keep domain decisions deterministic and independent of HTTP, Livewire and AI.
- `app/Jobs` handles chunked imports, matching and exports with explicit organization/cycle identifiers, progress and retry-safe chunks. Store uploaded originals and generated exports privately.
- `app/Enums`, `app/Support`, policies, factories and PHPUnit feature tests provide shared status values, normalization, access control and regression coverage. Laravel language files provide parallel Arabic/English strings.
- AI may suggest column maps, text cleanup or candidate matches; only deterministic configured rules and human decisions may determine eligibility, ranking or exceptions.

## Proposed database schema

Use portable relational columns, foreign keys and ordinary indexes compatible with MySQL/MariaDB; avoid vendor-specific SQL. IDs may use Laravel's selected key convention. All tables below are tenant tables unless marked **global**. Every tenant table has a non-null indexed `organization_id` FK. For every tenant-to-tenant relationship, the child carries `organization_id` and references a parent unique key `(organization_id,id)`; add that composite unique key to tenant parents. Per-tenant business uniqueness starts with `organization_id`. Enforce ownership in policies and scoped relations as well as database constraints and global scopes.

| Table | Key columns and constraints |
|---|---|
| `organizations` (global root) | `id`, `name`, `status`, timestamps; no tenant FK. |
| `users` (global identity) | Laravel auth columns plus `locale` (`ar`/`en`), timestamps. |
| `organization_user` | `organization_id`, `user_id`, preliminary `role_key`, membership status, timestamps; unique `(organization_id,user_id)`, FKs to organization and global user. Role permissions are organization-scoped; see proposed matrix in PLAN-01. |
| `people` | `id`, `organization_id`, optional display name, normalized name, DOB, phone, timestamps; indexes `(organization_id,normalized_name)`, `(organization_id,date_of_birth)`. Avoid one presumed universal ID field. |
| `person_identifiers` | `organization_id`, `person_id`, `type`, normalized `value`, timestamps; indexes `(organization_id,type,value)` and `(organization_id,person_id)`. Apply per-organization uniqueness only to identifier types owner-designated as authoritative; supporting data such as phone is not assumed unique. |
| `person_names` | `organization_id`, `person_id`, `name`, `normalized_name`, `script/language` nullable, source; index `(organization_id,normalized_name)` and `(organization_id,person_id)`. |
| `programs` | `organization_id`, name, sector/type, status, timestamps; index `(organization_id,status)`. Do not assume program names are unique. |
| `participations` | `organization_id`, `person_id`, `program_id`, optional source `import_row_id`, optional `source_decision_id`, status enum, start/end dates, completion/withdrawal timestamps; unique `(organization_id,source_decision_id)` for cycle postings; indexes `(organization_id,person_id,status)`, `(organization_id,person_id,end_date)`, `(organization_id,program_id,status)`. |
| `rule_sets` | `organization_id`, name, purpose/status, current version pointer nullable; index `(organization_id,status)`. |
| `rule_versions` | `organization_id`, `rule_set_id`, monotonically increasing version, schema/config JSON, creator/time, immutable after use; unique `(organization_id,rule_set_id,version)`. |
| `cycles` | `organization_id`, `program_id`, `rule_version_id`, name, status, evaluation/start date, available quantity, automation mode, input/import reference, started/completed timestamps; index `(organization_id,status)`, `(organization_id,program_id,status)`. Pin exact rule version at cycle creation/run. |
| `import_batches` | `organization_id`, `purpose` (cycle applicants or historical participations), optional `cycle_id`, optional historical `program_id`, uploader, private file path, format, status, row counts, progress cursor, quality summary, error summary, timestamps; indexes `(organization_id,status)`, `(organization_id,cycle_id)`. Validate exactly one applicable target; never store public paths. |
| `import_rows` | `organization_id`, `import_batch_id`, row number, raw payload, mapped/cleaned payload, validation status/errors, linked person nullable; unique `(organization_id,import_batch_id,row_number)`, index `(organization_id,import_batch_id,status)`. Define retention for raw data. |
| `column_mappings` | `organization_id`, optional owner, source headers, canonical field mapping, locale/format hints, approved flag, timestamps; index `(organization_id,owner_id)`. Suggestions remain reviewable. |
| `cycle_applicants` | `organization_id`, `cycle_id`, `import_row_id`, person nullable until resolved, status, verified-data flags, registration time; unique `(organization_id,cycle_id,import_row_id)`, indexes `(organization_id,cycle_id,status)`, `(organization_id,person_id)`. |
| `cycle_applicant_members` | `organization_id`, `cycle_applicant_id`, `person_id`, member role; unique `(organization_id,cycle_applicant_id,person_id)`, index `(organization_id,person_id)`. Team policy remains configurable/open. |
| `match_candidates` | `organization_id`, `import_row_id`, candidate `person_id`, method, confidence numeric/band, reason codes, blocking key; unique `(organization_id,import_row_id,person_id)`, indexes `(organization_id,blocking_key)`, `(organization_id,confidence_band)`. Candidate generation uses blocking, never all-pairs. |
| `match_reviews` | `organization_id`, `import_row_id`, candidate/reviewer, outcome, required reason, reviewed_at; indexes `(organization_id,reviewer_id,reviewed_at)`, `(organization_id,import_row_id)`. Preserve prior review decisions where needed. |
| `applicant_scores` | `organization_id`, `cycle_id`, `cycle_applicant_id`, rule version, criterion breakdown, total, missing/unverified indicators, computed input fingerprint/time; unique `(organization_id,cycle_id,cycle_applicant_id,rule_version_id)`, index `(organization_id,cycle_id,total_score)`. |
| `cycle_results` | `organization_id`, `cycle_id`, applicant, rank, outcome (accepted/waiting/rejected/exception/pending), tie-break values, cutoff marker, reason snapshot, decision status; unique `(organization_id,cycle_id,applicant_id)`, indexes `(organization_id,cycle_id,rank)`, `(organization_id,cycle_id,outcome)`. |
| `decisions` | `organization_id`, `cycle_result_id`, outcome, reason, actor, decided_at, supersedes nullable; index `(organization_id,cycle_result_id,decided_at)`. Append decision history; do not overwrite prior decisions. |
| `audit_events` | `organization_id`, actor nullable, event type, subject type/id, reason, before/after or event payload, occurred_at, request/job correlation; indexes `(organization_id,occurred_at)`, `(organization_id,subject_type,subject_id)`. Append-only: application and DB permissions prohibit update/delete. |
| `exports` | `organization_id`, `cycle_id`, requester, format, locale, status, private file path, filters/parameters, expiry, timestamps; indexes `(organization_id,cycle_id,status)`, `(organization_id,requester_id)`. |

Laravel/framework auth, queue, cache and permission tables may be global infrastructure. If roles/permissions are organization-specific, scope assignment to organization and ensure authorization combines membership with role. Do not add cross-organization person matching or shared registries.

All tenant relationship examples above use composite `(organization_id, referenced_id)` foreign keys, including import batch/row, cycle/program/rule version, applicant/import row/person, match candidate/person, result/decision, and participation/person/program/source references. Global `users` may be referenced by membership and actor fields, but permission checks must verify active-organization membership. Use portable constraints; settle exact Laravel/MariaDB syntax in Specs.

## Build order and dependencies

| Phase | Work | Depends on |
|---|---|---|
| (a) | Auth, organizations, roles, locale and RTL layout | — |
| (b) | Core ledger, program, cycle, rule-version, decision and audit schema | (a) tenant context |
| (c) | Import pipeline, mapping and cleaning | (a), (b) cycle/import schema |
| (d) | Identity/name normalization, blocking and human match review | (b), (c) |
| (e) | Eligibility rules and deterministic evaluation | (b), (d) resolved identities |
| (f) | Weighted scoring, tie breaks, quantity cutoff and waiting list | (b), (e), approved rule data |
| (g) | Review and approval UI with reasoned decisions/exceptions | (a), (c)-(f) |
| (h) | Idempotent ledger update after approval, withdrawal replacement handling | (b), (g) |
| (i) | XLSX/CSV exports and reports; PDF format remains an open question | (b), (g), (h) |

Detailed phase files: [01 Foundation](PLAN-01-foundation.md), [02 Core schema and import](PLAN-02-core-schema-import.md), [03 Matching and eligibility](PLAN-03-matching-eligibility.md), [04 Ranking and review](PLAN-04-ranking-review.md), [05 Ledger, exports and reports](PLAN-05-ledger-export-reports.md).

## Section 17 traceability

| MVP requirement | Coverage |
|---|---|
| Authentication; organization membership and active organization; basic roles/authorization | [PLAN-01](PLAN-01-foundation.md) |
| Organization data isolation | All phases; enforcement foundation in [PLAN-01](PLAN-01-foundation.md), tenant-safe schema/import in [PLAN-02](PLAN-02-core-schema-import.md) |
| XLSX/CSV import, column mapping and cleaning | [PLAN-02](PLAN-02-core-schema-import.md) |
| Identifier/name matching with confidence and human review | [PLAN-03](PLAN-03-matching-eligibility.md) |
| Active-project limit, waiting period, completed-only eligibility | [PLAN-03](PLAN-03-matching-eligibility.md) |
| Four to six weighted criteria, capacity cutoff and waiting list | [PLAN-04](PLAN-04-ranking-review.md) |
| Review, approval, rejection, reasoned exceptions | [PLAN-04](PLAN-04-ranking-review.md) |
| Cumulative ledger update after approval | [PLAN-05](PLAN-05-ledger-export-reports.md) |
| XLSX/CSV lists and essential reports | [PLAN-05](PLAN-05-ledger-export-reports.md) |
| Arabic/English, RTL/LTR | [PLAN-01](PLAN-01-foundation.md) and phase-specific UI/export checklists in [PLAN-02](PLAN-02-core-schema-import.md)–[PLAN-05](PLAN-05-ledger-export-reports.md) |
| Essential audit trail, security and automated tests | [PLAN-02](PLAN-02-core-schema-import.md), [PLAN-04](PLAN-04-ranking-review.md), [PLAN-05](PLAN-05-ledger-export-reports.md), and cross-cutting rules below |

Every Section 17 requirement has a phase owner; supporting controls are cross-cutting and remain mandatory.

## Cross-cutting rules

- **Tenant isolation:** scope every tenant model; organization ID is explicit in jobs, imports, exports and scheduled work. Raw SQL/joins repeat tenant filters. Test read/write/delete denial, automatic ownership, queue/export boundaries and guessed IDs.
- **Determinism:** identical normalized inputs, pinned rule version and fixed evaluation date produce identical eligibility, scores, tie order and allocation. Persist rule version, score breakdown, input provenance and decision reasons. AI output requires human acceptance and cannot decide outcomes.
- **Bilingual/RTL:** all UI/export strings in identical `lang/en` and `lang/ar` keys. User locale controls `lang`/`dir`; use logical Tailwind utilities, Arabic/Latin-capable design-token fonts, bidi isolation for names/IDs, localized number/date parsing and output. Test key screens in both locales against approved Figma.
- **Queues/performance:** database queue initially; chunked/streaming reads, bounded memory, explicit tenant IDs, idempotent chunk writes, progress, retry/resume and failure reporting. Blocking indexes restrict match candidates. Keep DB operations portable. Performance assumptions (large ledger and ~1M-row file) need pilot benchmarks; they are not guarantees.
- **Input/output safety:** validate file type and required headers before processing; retain row-level validation reasons and original source values under an approved retention policy. Export jobs and downloads are tenant-authorized; CSV escaping must prevent formula injection.
- **Testing:** PHPUnit feature tests with factories and fake/anonymized data. Cover tenant boundaries, workflow state transitions, deterministic service outputs, Arabic/English data, duplicate/poor-quality input, queued retries, authorization, audit immutability and export isolation. Test each AC in phase plans. Add performance tests/benchmarks before claiming target scale.

## Risks and rollback

- Wrong identity matches can wrongly exclude applicants: medium confidence requires human review; show reason evidence and preserve correction/audit history.
- Incorrect rules or incomplete data can distort priority: validate configurations and missing data before execution; pin versions and simulate against sample data before approval.
- Tenant leakage through jobs, joins, files or guessed IDs: repeat explicit organization checks at every boundary and test them.
- Large spreadsheets can exhaust memory or stall workers: streaming/chunking, circuit breakers, progress and resumable chunks; benchmark with anonymized synthetic data.
- Sensitive data may exceed legal/purpose limits: minimize fields, private storage, restricted roles, retention/deletion design and resolve jurisdiction/hosting before pilot data.
- Malformed workbooks, invalid rows, or formula-injection payloads can corrupt data or harm export users: validate before queueing, isolate invalid rows, keep exports private, and emit user values as safe text.
- Roll back unreleased schema via reversible migrations and discard synthetic/import staging only when safe. For production data, stop the affected cycle, preserve audit evidence, restore from backup or run compensating actions; never erase append-only audit/decision history. Ledger posting must be idempotent and compensatable.

## Open decisions and deferrals

### A. MVP-blocking owner decisions (only block affected slices)

- **Plan 01 authorization:** approve the proposed role matrix and whether a membership has one role or multiple roles. Conservative proposal: one role per membership and the least-privilege matrix in PLAN-01; this keeps authorization simple but requires a later migration if multiple roles are needed.
- **Plan 01 account provisioning:** choose login/account creation and recovery. Conservative proposal: first-party session login with organization-invited memberships, not public self-service organization creation; this limits accidental tenant creation but requires manual onboarding. The current repository is a Laravel scaffold with no auth routes or auth package.
- **Plan 01 UI:** approve Figma screens/tokens before frontend implementation. No UI assumptions are approved.
- **Matching/pilot:** confirm authoritative identifier types and matching policy/thresholds before enabling automatic match decisions. Conservative proposal: exact match on an owner-designated authoritative identifier may auto-link; name-only/uncertain matches require human review; no numeric threshold is set here.
- **Eligibility/team:** confirm team-member handling before team cases are evaluated. Until then, flag a team with an ineligible member for human review; do not automatically reject, replace, or grant an exception.
- **Pilot configuration:** choose the 4–6 criteria, weights, tie-break order, missing-data policy, and whether the 12-month default varies by sector before running a pilot. These do not block building unrelated phases.
- **Live personal data:** decide launch jurisdiction/hosting and identifier/raw-import encryption and retention/deletion policy before using real personal data; this blocks live deployment/pilot data, not this planning review.

### B. Spec-level decisions (do not invent in these plans)

- Active-organization session/preference storage, exact auth implementation, middleware and permission-table implementation.
- Exact column types, constraint/index names, FK deletion behavior, role storage, and supported MySQL/MariaDB versions.
- Import size/row limits, duplicate/circuit-breaker thresholds, date/number parsing rules, and package/version choice (OpenSpout is only a streaming candidate).
- Name normalization, candidate scoring/cutoffs, blocking-key construction, and match reason codes; no numeric matching thresholds are set.
- Score precision/rounding, tie-break evaluation and stable final fallback; criteria and weights remain owner inputs.
- Precise cycle state transitions, import row correction behavior, export expiry, and essential report definitions.

### C. Deferred or out of MVP scope

- PDF export; quotas; billing/subscriptions; cross-organization matching or a public person registry; advanced ML ranking; dedicated relief modules; offline apps; WhatsApp/Google Sheets integrations; a public API; advanced analytics dashboards; complex appeal management; scheduled reports; and distributed/microservice infrastructure.
- A richer what-if simulation is not required by Section 17 and is deferred unless separately approved.
- Project-brief §§16, 18, and 19 remain reference material, not additional product scope. Security and risk controls in this plan remain mandatory.

Non-blocking project decisions such as platform name and pricing do not affect the technical MVP plan. No pending decision above is treated as approved.
