# MVP Master Plan (Draft)

This plan covers MVP scope in [project brief §17](../project-brief.md#17-mvp-scope). It is a planning baseline, not implementation approval. All five phase plans remain **draft** and require owner approval before implementation.

## Architecture and module map

- Laravel 12 monolith: Blade + Livewire + Tailwind/Vite; no SPA or API-first layer. Implement screens from approved Figma frames and tokens.
- Authentication and organization membership/policies form the tenant boundary. Tenant models use a shared organization trait/global scope; jobs and raw queries enforce tenant filters explicitly.
- Domain services/actions in `app/Services` and `app/Actions` implement import normalization, matching, eligibility, scoring/ranking, allocation and ledger posting. Keep domain decisions deterministic and independent of HTTP, Livewire and AI.
- `app/Jobs` handles chunked imports, matching and exports with explicit organization/cycle identifiers, progress and retry-safe chunks. Store uploaded originals and generated exports privately.
- `app/Enums`, `app/Support`, policies, factories and PHPUnit feature tests provide shared status values, normalization, access control and regression coverage. Laravel language files provide parallel Arabic/English strings.
- AI may suggest column maps, text cleanup or candidate matches; only deterministic configured rules and human decisions may determine eligibility, ranking or exceptions.

## Proposed database schema

Use portable relational columns, foreign keys and ordinary indexes compatible with MySQL/MariaDB; avoid vendor-specific SQL. IDs may use Laravel's selected key convention. All tables below are tenant tables unless marked **global**. Every tenant table has non-null indexed `organization_id` FK; per-tenant unique constraints start with `organization_id`. Prefer composite FK/index patterns that prevent cross-tenant references where practical. Enforce ownership in policies and scoped relations as well as the global scope.

| Table | Key columns and constraints |
|---|---|
| `organizations` (global root) | `id`, `name`, `status`, timestamps; no tenant FK. |
| `users` (global identity) | Laravel auth columns plus `locale` (`ar`/`en`), timestamps. |
| `organization_user` | `organization_id`, `user_id`, `role` or permission assignment, timestamps; unique `(organization_id,user_id)`, indexes both FKs. |
| `people` | `id`, `organization_id`, optional display name, normalized name, DOB, phone, timestamps; indexes `(organization_id,normalized_name)`, `(organization_id,date_of_birth)`. Avoid one presumed universal ID field. |
| `person_identifiers` | `organization_id`, `person_id`, `type`, normalized `value`, timestamps; unique `(organization_id,type,value)`, index `(organization_id,person_id)`. Store only necessary identifiers. |
| `person_names` | `organization_id`, `person_id`, `name`, `normalized_name`, `script/language` nullable, source; index `(organization_id,normalized_name)` and `(organization_id,person_id)`. |
| `programs` | `organization_id`, name, sector/type, status, timestamps; unique `(organization_id,name)` if policy allows, index `(organization_id,status)`. |
| `participations` | `organization_id`, `person_id`, `program_id`, optional originating `cycle_id`/decision, status enum, start/end dates, completion/withdrawal timestamps; indexes `(organization_id,person_id,status)`, `(organization_id,person_id,end_date)`, `(organization_id,program_id,status)`. |
| `rule_sets` | `organization_id`, name, purpose/status, current version pointer nullable; index `(organization_id,status)`. |
| `rule_versions` | `organization_id`, `rule_set_id`, monotonically increasing version, schema/config JSON, creator/time, immutable after use; unique `(organization_id,rule_set_id,version)`. |
| `cycles` | `organization_id`, `program_id`, `rule_version_id`, name, status, evaluation/start date, available quantity, automation mode, input/import reference, started/completed timestamps; index `(organization_id,status)`, `(organization_id,program_id,status)`. Pin exact rule version at cycle creation/run. |
| `import_batches` | `organization_id`, optional `cycle_id`, uploader, private file path, format, status, row counts, progress cursor, quality summary, error summary, timestamps; indexes `(organization_id,status)`, `(organization_id,cycle_id)`. Never store public paths. |
| `import_rows` | `organization_id`, `import_batch_id`, row number, raw payload, mapped/cleaned payload, validation status/errors, linked applicant/person nullable; unique `(organization_id,import_batch_id,row_number)`, index `(organization_id,import_batch_id,status)`. Define retention for raw data. |
| `column_mappings` | `organization_id`, optional owner, source headers, canonical field mapping, locale/format hints, approved flag, timestamps; index `(organization_id,owner_id)`. Suggestions remain reviewable. |
| `cycle_applicants` | `organization_id`, `cycle_id`, source row, person nullable until resolved, status, verified-data flags, registration time; unique `(organization_id,cycle_id,source_row_id)`, indexes `(organization_id,cycle_id,status)`, `(organization_id,person_id)`. |
| `cycle_applicant_members` | `organization_id`, `cycle_applicant_id`, `person_id`, member role; unique `(organization_id,cycle_applicant_id,person_id)`, index `(organization_id,person_id)`. Team policy remains configurable/open. |
| `match_candidates` | `organization_id`, import/applicant row, candidate `person_id`, method, confidence numeric/band, reason codes, blocking key; unique `(organization_id,source_row_id,person_id)`, index `(organization_id,blocking_key)`, `(organization_id,confidence_band)`. Candidate generation uses blocking, never all-pairs. |
| `match_reviews` | `organization_id`, candidate/source, reviewer, outcome, reason, reviewed_at; indexes `(organization_id,reviewer_id,reviewed_at)`, `(organization_id,source_row_id)`. Preserve prior review decisions where needed. |
| `applicant_scores` | `organization_id`, `cycle_id`, `cycle_applicant_id`, rule version, criterion breakdown, total, missing/unverified indicators, computed input fingerprint/time; unique `(organization_id,cycle_id,cycle_applicant_id,rule_version_id)`, index `(organization_id,cycle_id,total_score)`. |
| `cycle_results` | `organization_id`, `cycle_id`, applicant, rank, outcome (accepted/waiting/rejected/exception/pending), tie-break values, cutoff marker, reason snapshot, decision status; unique `(organization_id,cycle_id,applicant_id)`, indexes `(organization_id,cycle_id,rank)`, `(organization_id,cycle_id,outcome)`. |
| `decisions` | `organization_id`, `cycle_result_id`, outcome, reason, actor, decided_at, supersedes nullable; index `(organization_id,cycle_result_id,decided_at)`. Append decision history; do not overwrite prior decisions. |
| `audit_events` | `organization_id`, actor nullable, event type, subject type/id, reason, before/after or event payload, occurred_at, request/job correlation; indexes `(organization_id,occurred_at)`, `(organization_id,subject_type,subject_id)`. Append-only: application and DB permissions prohibit update/delete. |
| `exports` | `organization_id`, `cycle_id`, requester, format, locale, status, private file path, filters/parameters, expiry, timestamps; indexes `(organization_id,cycle_id,status)`, `(organization_id,requester_id)`. |

Laravel/framework auth, queue, cache and permission tables may be global infrastructure. If roles/permissions are organization-specific, scope assignment to organization and ensure authorization combines membership with role. Do not add cross-organization person matching or shared registries.

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

## Cross-cutting rules

- **Tenant isolation:** scope every tenant model; organization ID is explicit in jobs, imports, exports and scheduled work. Raw SQL/joins repeat tenant filters. Test read/write/delete denial, automatic ownership, queue/export boundaries and guessed IDs.
- **Determinism:** identical normalized inputs, pinned rule version and fixed evaluation date produce identical eligibility, scores, tie order and allocation. Persist rule version, score breakdown, input provenance and decision reasons. AI output requires human acceptance and cannot decide outcomes.
- **Bilingual/RTL:** all UI/export strings in identical `lang/en` and `lang/ar` keys. User locale controls `lang`/`dir`; use logical Tailwind utilities, Arabic/Latin-capable design-token fonts, bidi isolation for names/IDs, localized number/date parsing and output. Test key screens in both locales against approved Figma.
- **Queues/performance:** database queue initially; chunked/streaming reads, bounded memory, explicit tenant IDs, idempotent chunk writes, progress, retry/resume and failure reporting. Blocking indexes restrict match candidates. Keep DB operations portable. Performance assumptions (large ledger and ~1M-row file) need pilot benchmarks; they are not guarantees.
- **Testing:** PHPUnit feature tests with factories and fake/anonymized data. Cover tenant boundaries, workflow state transitions, deterministic service outputs, Arabic/English data, duplicate/poor-quality input, queued retries, authorization, audit immutability and export isolation. Test each AC in phase plans. Add performance tests/benchmarks before claiming target scale.

## Risks and rollback

- Wrong identity matches can wrongly exclude applicants: medium confidence requires human review; show reason evidence and preserve correction/audit history.
- Incorrect rules or incomplete data can distort priority: validate configurations and missing data before execution; pin versions and simulate against sample data before approval.
- Tenant leakage through jobs, joins, files or guessed IDs: repeat explicit organization checks at every boundary and test them.
- Large spreadsheets can exhaust memory or stall workers: streaming/chunking, circuit breakers, progress and resumable chunks; benchmark with anonymized synthetic data.
- Sensitive data may exceed legal/purpose limits: minimize fields, private storage, restricted roles, retention/deletion design and resolve jurisdiction/hosting before pilot data.
- Roll back unreleased schema via reversible migrations and discard synthetic/import staging only when safe. For production data, stop the affected cycle, preserve audit evidence, restore from backup or run compensating actions; never erase append-only audit/decision history. Ledger posting must be idempotent and compensatable.

## Open questions (owner decisions)

- Public platform name.
- Launch markets, applicable data-protection laws and hosting location.
- Pricing.
- Team-member ineligibility policy and team project handling.
- First pilot organization, its rules and weighted criteria.
- Whether the default 12-month waiting period varies by sector.
- Confirm identifier retention/encryption, raw import retention, deletion/appeal policy and exact Figma screens/tokens before implementation or live data.

No open decision above is resolved by this draft.
