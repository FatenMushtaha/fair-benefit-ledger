# PLAN-03: Matching and Eligibility (Draft)

## Goal and scope

Resolve applicants to organization-owned identities with explainable confidence and human review where uncertain, then apply the brief's active-project, waiting-period and completed-only eligibility rules. Covers phases (d) and (e); depends on core schema/import.

## Tables, files and classes

- Tables: `match_candidates`, `match_reviews`, and required additions to `people`, `person_identifiers`, `person_names`, `participations`, `cycle_applicants`, `cycle_applicant_members`, `cycles` and pinned `rule_versions`.
- Files/classes: normalization and blocking-key services; deterministic candidate generator/scorer; confidence-band enum; chunked matching job; match-review Livewire screen/policy; plain PHP `EligibilityEvaluator` and result/reason value objects; rule validation; feature factories/tests and translations.
- Candidate retrieval uses tenant-scoped identifier indexes and normalized-name blocking keys. Persist score, band and reason codes; medium confidence goes to review. AI can suggest candidates/text cleanup only; a person or deterministic policy confirms a match.

## Acceptance criteria and tests

- **AC-03.1** Exact normalized identifiers match only within the same organization — feature/service tests for exact match, type separation and cross-tenant non-match.
- **AC-03.2** Name normalization handles Arabic diacritics, order variants, Latin transliteration and Arabic/Western digits reproducibly — table-driven tests with synthetic names.
- **AC-03.3** Blocking avoids all-pairs comparison and candidate work is chunkable/retry-safe — service/job tests for block boundaries, bounded candidate set and idempotent retry.
- **AC-03.4** High/medium/low confidence thresholds are organization-configured and medium cases require review — tests at threshold boundaries, reason visibility and review authorization.
- **AC-03.5** Eligibility enforces max active participation, waiting period measured at new cycle start, and completed-only benefits; withdrawals/noncompletion do not count — deterministic service tests including exact date boundaries.
- **AC-03.6** Evaluation includes team members and flags ineligible members for human handling without inventing team policy — tests for member flags and no automatic resolution.
- **AC-03.7** Same inputs, pinned version and evaluation date yield the same eligibility/reasons — repeat-run equality tests.

## Tenant-isolation checklist

- All match tables and cycle applicant/member tables include required tenant FK and tenant-prefixed indexes/uniqueness.
- Candidate queries constrain `organization_id` in addition to global scopes; avoid raw cross-tenant joins.
- Match and eligibility jobs carry organization/cycle IDs and rebuild context explicitly.
- Test cross-tenant identity non-discovery, review authorization, automatic ownership and job isolation.

## RTL/bilingual checklist

- Translate confidence bands, reasons, review actions, eligibility outcomes and validation text identically in both language files.
- Match screen supports Arabic/English names and mixed scripts using bidi isolation; locale sets `lang`/`dir` and logical table alignment.
- Test both locales and assert human-readable translated reasons without raw keys.

## Rollback, risks and open questions

- Pause matching jobs; retain candidate/review/audit history and mark the run superseded. Reverse schema only if no cycles depend on it. Re-run against a corrected version instead of rewriting decisions.
- Risks: false identity merge can deprive an applicant; transliteration bias and poor identifiers; changing thresholds after a run.
- Open: matching thresholds and identifier formats per pilot; policy for team members; exact policy for accepted then withdrawn cases. No thresholds or team outcomes are fixed here.
