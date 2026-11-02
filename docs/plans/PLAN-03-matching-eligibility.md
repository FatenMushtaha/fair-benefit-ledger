# PLAN-03: Matching and Eligibility (Draft)

## Goal and scope

Resolve each staged import row to an organization-owned identity using explainable evidence and human review where uncertain, then apply the active-project limit, waiting period and completed-only benefit rules. Covers phases (d) and (e); depends on core schema/import.

## Tables, files and classes

- Tables: `match_candidates` and `match_reviews` reference `import_rows`; `cycle_applicants` references the same `import_row_id`. Related tenant rows use composite organization-safe FKs to `people`, identifiers, names, participations, cycles and pinned `rule_versions`.
- Files/classes: deterministic normalization and blocking-key services; candidate generator/scorer with reason codes; confidence-band enum; chunked matching job; match-review policy/screen; plain PHP `EligibilityEvaluator` and result/reason value objects; rule validation; factories/tests and translations.
- Candidate retrieval uses tenant-scoped authoritative-identifier indexes and normalized-name blocking keys. Persist evidence, band and reason codes. AI may suggest candidates/text cleanup only; it never merges identities or decides eligibility.

## Acceptance criteria and tests

- **AC-03.1** An exact match on an owner-designated authoritative identifier can be auto-linked only within the same organization — feature/service tests for identifier type, duplicate conflict, unique ownership, and cross-tenant non-match.
- **AC-03.2** Name normalization handles Arabic diacritics, order variants, Latin transliteration and Arabic/Western digits reproducibly — table-driven tests with synthetic names.
- **AC-03.3** Blocking avoids all-pairs comparison and candidate work is chunkable/retry-safe — service/job tests for block boundaries, bounded candidate set and idempotent retry.
- **AC-03.4** Name-only/uncertain candidates never silently merge: medium cases require authorized human review, and low cases remain separate — tests for no-auto-merge, threshold boundaries, visible reasons and review authorization. Numeric thresholds are owner-configured and are not set here.
- **AC-03.5** Eligibility enforces the active-project limit, evaluates the waiting period at the new cycle's start date, and counts completed benefits only; withdrawals/noncompletion remain visible but do not trigger the waiting period — deterministic tests at status/date boundaries.
- **AC-03.6** Team applicants evaluate each member and flag any ineligible/unresolved member for human handling without auto-reject, replacement, or exception — tests for member flags and no automatic resolution.
- **AC-03.7** Same inputs, pinned version and evaluation date yield the same eligibility/reasons — repeat-run equality tests.
- **AC-03.8** Correcting a wrong match preserves the prior match/review/audit record and does not silently move or merge ledger history — feature tests for correction authorization and history.

## Tenant-isolation checklist

- All match tables and cycle applicant/member tables include required tenant FK and tenant-prefixed indexes/uniqueness; relationships use composite tenant-safe FKs.
- Candidate queries constrain `organization_id` in addition to global scopes; avoid raw cross-tenant joins.
- Match and eligibility jobs carry organization/cycle IDs and rebuild context explicitly.
- Test cross-tenant identity non-discovery, review authorization, automatic ownership and job isolation.

## RTL/bilingual checklist

- Translate confidence bands, reasons, review actions, eligibility outcomes and validation text identically in both language files.
- Match screen supports Arabic/English names and mixed scripts using bidi isolation; locale sets `lang`/`dir` and logical table alignment.
- Test both locales and assert human-readable translated reasons without raw keys.

## Rollback, risks and open questions

- Pause matching jobs; retain candidate/review/audit history and mark the run superseded. Reverse schema only if no cycles depend on it. Correct through an authorized, audited rematch; never silently overwrite a prior identity link or decision.
- Risks: false identity merge can deprive an applicant; transliteration bias and poor identifiers; changing thresholds after a run.
- MVP-blocking for automatic matching: owner must identify authoritative ID types and approve the automatic-vs-review policy. Conservative proposal: exact authoritative ID matches may auto-link; name-only/uncertain matches go to human review; conflicting identifiers fail closed. No numeric thresholds are set.
- MVP-blocking for team cases: owner decides the eventual team-member response; until then, flag for human review with no automatic replacement/rejection/exception.
- Spec-level: name normalization/transliteration, blocking keys, confidence calculation and numeric threshold storage; define date/month boundary semantics and how active statuses map to the brief's lifecycle. No unresolved team policy is invented.
- The brief already defines accepted withdrawals as reopening a seat and noncompletion/withdrawal as not counting toward the waiting period; PLAN-05 handles ledger/replacement history, so this is not an open product question.
