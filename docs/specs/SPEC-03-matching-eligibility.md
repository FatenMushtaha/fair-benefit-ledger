# SPEC-03 — Matching & Eligibility

**Status:** draft — owner approval required

**Planning baseline:** owner-approved [project brief](../project-brief.md) §§7–10, 17; [MVP master plan](../plans/MASTER-PLAN.md); [Plan 03](../plans/PLAN-03-matching-eligibility.md).
**Scope:** organization-local identity candidates with explainable confidence and human review, then deterministic eligibility evaluation for each cycle applicant. No cross-organization lookup or automatic uncertain identity merge.

## Section 17 traceability

| Section 17 requirement | Specification coverage |
|---|---|
| Identifier/name matching (Arabic/English) with confidence | Slices 1–2 |
| Active-project limit, waiting period, completed-only eligibility | Slice 3 |
| Review of doubtful cases; audit trail | Slices 2 and 4 |

**Plan acceptance trace:** PLAN-03 AC-03.1 → Slice 2; AC-03.2–03.3 → Slice 1; AC-03.4 and AC-03.8 → Slices 2 and 4; AC-03.5–03.7 → Slice 3.

**Technical refinement:** append-only `eligibility_evaluations` snapshots preserve provenance on input changes; this prevents overwriting prior evaluations and adds no user-facing feature.

## Requirements and contracts

- Matching operates only on valid staged rows from SPEC-02 and people/identifier/name records in the same organization. Use indexed blocking; never all-pairs comparison.
- Exact matches on owner-designated authoritative identifier types may be eligible for automatic linking only after the owner approves that policy. Name-only/uncertain matches never silently merge; medium confidence is reviewed, low confidence stays separate. Numeric thresholds and the automatic-link policy are **BLOCKED — OWNER DECISION REQUIRED**.
- Persist evidence, method, confidence band/score, threshold/config version, reason codes and human review history. AI can suggest candidates/text cleanup only; it does not merge, determine eligibility or rank.
- Eligibility applies active-project limit, waiting period as of cycle start date, completed-only benefit rule, and team members individually. Withdrawals/noncompletion remain visible but do not count as completed benefits. Team ineligible/unresolved cases are flagged for human handling without automatic rejection/replacement/exception.
- Every evaluation is deterministic for identical normalized inputs, date and pinned rule version. Never silently convert missing/unverified data to eligible.

## Ordered implementation slices

### Slice 1 — Deterministic identity normalization and blocking

- **Goal/scope:** normalize identifiers/names and generate a bounded same-tenant candidate set from confirmed import rows.
- **Dependencies:** SPEC-01 tenant context; SPEC-02 Slices 1–4 and valid-row handoff.
- **Tables/constraints:** use `people`, `person_identifiers`, `person_names`, `import_rows`; add `match_candidates(org,import_row_id,person_id,method,confidence_score?,confidence_band,reason_codes,blocking_key,rule_version_id,created_at)` unique `(org,import_row_id,person_id)`, composite tenant FKs, indexes `(org,blocking_key)` and `(org,confidence_band)`. `cycle_applicants(org,cycle_id,import_row_id,person_id?,status,verified_data_flags,registered_at)` unique `(org,cycle_id,import_row_id)` is added for cycle intake.
- **Classes/routes/components:** `IdentityNormalizer`, `IdentifierNormalizer`, `NameNormalizer`, `BlockingKeyGenerator`, `CandidateGenerator`, `MatchCandidate` model, `MatchConfidenceBand` (`High`, `Medium`, `Low`) enum, `GenerateMatchCandidates` queued job. No UI in this slice.
- **Behavior:** preserve source strings and create versioned keys by trimming/collapsing Unicode whitespace, case-folding Latin, removing Arabic diacritics/tatweel, and mapping Arabic/Persian digits to Western digits. Generate a token-order-independent key by sorting normalized tokens. Generate a transliteration key with the configured ICU transliterator `Any-Latin; Latin-ASCII; Lower()`; persist normalizer/transliterator version with the candidate run. Confirm `ext-intl` is available before implementation; if unavailable, block this behavior and request dependency approval instead of silently substituting a different algorithm. Query exact owner-configured identifier indexes and name blocking keys only inside tenant scope; cap candidate result set by explicit config, never compare every person to every row. No candidate is linked in this slice.
- **Authorization/tenant isolation:** job payload carries organization, batch and cycle IDs; query filters tenant explicitly; candidate FK has matching organization ID; a tenant mismatch fails closed.
- **Edge/error handling:** conflicting authoritative IDs produce a conflict marker, not a candidate auto-link; empty/unparseable names produce no name candidates; a candidate cap is reported rather than silently truncating without status.
- **Acceptance/tests:**
  - **AC-03.1** Given Arabic/English/mixed names and Arabic/Western digits, when normalized repeatedly, then output is deterministic and the source value is unchanged; table-driven unit tests cover diacritics, tatweel, whitespace, digits, token-order variants and ICU transliteration outputs.
  - **AC-03.2** Given a staged row and people in two organizations, when candidate generation runs for one organization, then only that tenant's indexed blocks are searched and no cross-tenant candidate is returned; job/feature tests verify.
  - **AC-03.3** Given a large candidate set, when blocking runs, then the query is bounded and no all-pairs comparison occurs; service tests assert candidate bound and block lookup behavior.
- **Definition of Done:** deterministic normalizers have synthetic fixtures, candidate search is indexed and tenant-bound, retries are idempotent, and no identity link is written.

### Slice 2 — Confidence, match resolution and human review

- **Goal/scope:** classify match candidates and record safe auto-link or human-reviewed outcomes.
- **Dependencies:** Slice 1; SPEC-01 approved authorization matrix. **BLOCKED — OWNER DECISION REQUIRED** for authoritative identifier types, automatic-link policy, confidence thresholds, and reviewer permissions.
- **Tables/constraints:** `match_reviews(org,import_row_id,candidate_id?,reviewer_id,outcome,reason,reviewed_at)` with tenant-safe FKs and indexes `(org,import_row_id)` and `(org,reviewer_id,reviewed_at)`; append-only review history. Candidate rows retain method, band, reason, score formula version and threshold/configuration version.
- **Classes/routes/components:** `MatchConfidenceClassifier`, `ResolveMatch` action, `RecordMatchReview` action, match policy, named review/resolve routes, `MatchReview` Livewire screen only after approved Figma, translated reason keys.
- **Behavior:** classify only using owner-approved thresholds/configuration. If policy is absent, retain candidates as `NeedsReview` and block auto-resolution. **Candidate-score recommendation pending owner approval:** transliterate and normalize aliases as in Slice 1; if either key is empty, score `0`; otherwise compute each name similarity as `round_half_up(10,000 × (1 − levenshtein(a,b)/max(length(a),length(b)))`; compare both full normalized strings and token-sorted strings and retain the maximum score plus source pair/evidence. Treat this as a similarity score, not a probability; supporting identifiers are shown separately and do not change the score absent approved weights. If the owner does not approve this formula, record only evidence and require human review. Exact authoritative ID can auto-link only when owner-approved; medium requires human review; low remains separate. Human choice must select an existing same-tenant candidate or explicitly create a separate identity; no merge/delete of prior person rows. Corrections append superseding history and update current link through an audited action.
- **Authorization/tenant isolation:** reviewer actions require active-organization membership and owner-approved capability; guessed cross-tenant candidate/import IDs deny. All candidate joins include organization ID.
- **Edge/error handling:** conflicting IDs, no candidates, stale concurrent review, duplicate candidate, invalid outcome or missing reason are rejected/flagged without mutating identity; uncertain case stays unresolved.
- **Acceptance/tests:**
  - **AC-03.4** Given thresholds or authoritative-ID policy are absent, when resolution runs, then no candidate auto-links and the row remains review-required; service tests verify fail-closed behavior.
  - **AC-03.5** Given an owner-approved policy and confidence band, when a candidate falls in each band, then only approved high-confidence policy may link and medium requires authorized human review while low stays separate; tests cover boundaries with supplied values.
  - **AC-03.6** Given an authorized reviewer resolves a same-tenant row with a reason, when saved, then a new append-only review/audit event is written and prior history remains; feature tests cover missing reason, unauthorized user and guessed foreign ID.
- **Definition of Done:** no silent uncertain merge; threshold/policy are versioned; every decision has explainable evidence and durable history.

### Slice 3 — Eligibility evaluator and versioned rule inputs

- **Goal/scope:** compute a deterministic eligibility result and reason codes from resolved applicant identity, participations and pinned rule version.
- **Dependencies:** SPEC-02 core schema; SPEC-04 ranking consumes results. Matching resolution can be independently completed before eligibility runs.
- **Tables/constraints:** `rule_versions` stores eligibility configuration; `cycles.rule_version_id` pins it; append-only `eligibility_evaluations(org,cycle_id,cycle_applicant_id,rule_version_id,evaluation_date,input_fingerprint,status,reasons,created_at)` is unique `(org,cycle_applicant_id,rule_version_id,input_fingerprint)` and has composite FKs ensuring the applicant and rule version belong to the same organization/cycle. `cycle_applicants.current_eligibility_evaluation_id` references the current snapshot with `(org,cycle_id)` consistency. No duplicate benefit ledger writes here.
- **Classes/routes/components:** plain PHP `EligibilityEvaluator`, input/result value objects, `EligibilityRuleValidator`, `EvaluateCycleEligibility` queued job, eligibility enums/reason keys. No HTTP/Livewire dependency in domain logic.
- **Behavior:** evaluate only resolved identities. Use the calendar date `cycle_start_date`; the eligible threshold is `completion_end_date + configured calendar months`, with month-end clamped to the last valid day of the target month, and eligibility begins when cycle start is on or after that threshold. Count only completed benefits; use the completed participation's `end_date`; missing end date is `NeedsReview`. Withdrawals/noncompletion remain visible but do not count as completed benefit. The incubator template starts at the brief's 12-month default; other sector values require explicit configuration. Enforce max active projects using the owner-approved lifecycle mapping. If required status/team configuration is absent or data is unverified, emit `NeedsReview`/blocked result rather than guess. Freeze evaluation date, inputs and pinned rule version in an immutable `eligibility_evaluations` row; update the current pointer only after successful evaluation.
- **Authorization/tenant isolation:** job carries organization/cycle IDs and re-establishes scope; it reads only same-tenant participations and rule version; only authorized user can start/review results.
- **Edge/error handling:** missing cycle start date, unknown status, conflicting participation dates, missing identity, expired/mismatched rule version and unresolved team member prevent automatic pass and produce reason codes; same cycle rerun cannot overwrite historical evaluation snapshots.
- **Acceptance/tests:**
  - **AC-03.7** Given same resolved inputs, pinned rule version and evaluation date, when eligibility runs twice, then status and reasons are identical; unit tests compare full outputs.
  - **AC-03.8** Given completed, withdrawn and noncompleted participations, when waiting-period eligibility is evaluated, then only completed benefits with an end date count, and the threshold is inclusive at the calendar-month anniversary with month-end clamping; tests cover day-before, exact boundary, day-after and month-end cases.
  - **AC-03.9** Given active-status mapping, sector wait period or team handling is missing, when evaluation runs, then no silent eligible result is produced and a human-review reason is stored; service tests cover missing config and unresolved member.
  - **AC-03.10** Given a cycle and a foreign-tenant participation/rule ID, when evaluation is requested, then no foreign data is read and the job fails closed; job tests cover tenant mismatch.
- **Definition of Done:** evaluator is framework-independent and deterministic; rules are versioned; active count/wait period/complete-only behavior is automated-test covered for approved inputs.

### Slice 4 — Eligibility review queue and corrections

- **Goal/scope:** expose unresolved matches and eligibility/team flags to authorized humans, then rerun only affected evaluation under a pinned version.
- **Dependencies:** Slices 2–3 and approved role matrix; Figma for screen implementation. **BLOCKED — OWNER DECISION REQUIRED** for team-member response and reviewer permissions.
- **Tables/constraints:** append-only match reviews, audit events, and evaluation snapshots; current cycle applicant state references latest evaluation while prior runs remain immutable.
- **Classes/routes/components:** review query service, eligibility policy, named queue/detail/review routes, Livewire case table/detail only after approved designs, audited correction action.
- **Behavior:** queue sorts/paginates by explicit deterministic keys; shows evidence, status and reasons; human can record the allowed reviewed outcome only after owner policy is approved. Changing identity/data triggers a new evaluation snapshot; it never silently edits old result or ledger.
- **Authorization/tenant isolation:** same active-org and role checks on list, detail and mutation; query joins apply tenant predicate.
- **Edge/error handling:** stale review form detects changed version and asks reload; missing reason, unsupported team action or unverified inputs cannot resolve automatically.
- **Acceptance/tests:**
  - **AC-03.11** Given a doubtful match or unresolved eligibility case, when the authorized review queue is queried, then only current-tenant cases and explanation evidence are returned; feature tests cover both locales and tenant denial.
  - **AC-03.12** Given a correction, when a new evaluation is triggered, then the prior result/audit history remains and only the new snapshot becomes current; workflow tests verify history and idempotency.
- **Definition of Done:** UI waits for approved Figma; reviewed state changes are authorized, reasoned and append-only; reruns preserve prior evidence.

## Rollback and test strategy

- Pause candidate/evaluation jobs before rollback. Preserve match review and evaluation history; reverse mistaken identity links with audited superseding actions, never destructive merge/delete. Roll back schema only when no dependent cycle exists.
- PHPUnit unit tests cover normalization, confidence boundaries and evaluator determinism; feature/job tests cover tenant isolation, authorization, retry, review history and queue behavior. Use only synthetic names/identifiers.
- Manual verification: human reviewers confirm reason explanations on approved bilingual screens; no real personal data in fixtures.

## Open questions / conflicts

- **BLOCKED — OWNER DECISION REQUIRED:** authoritative ID types and whether any exact ID match can auto-link; confidence thresholds, candidate-score formula and reviewer roles. Recommendation: only exact owner-designated ID may auto-link; name/uncertain cases stay human-reviewed, with the transparent similarity formula in Slice 2 and no numerical band thresholds supplied.
- **BLOCKED — OWNER DECISION REQUIRED:** how to resolve team applicants when one member is ineligible. Recommendation: flag for human review without automatic rejection, replacement or exception.
- **BLOCKED — OWNER DECISION REQUIRED:** active-status mapping for the one-project limit and whether the 12-month waiting period changes by sector. Recommendation: count `Accepted` and `Active` as simultaneous participation; count only `Completed` toward the waiting period; use the documented 12-month incubator default only for incubator rules and require explicit sector configuration elsewhere. This errs toward preventing overlap but may defer applicants; do not enable the mapping until approved.
- **Spec-level:** Unicode normalization/transliteration version and index/candidate-bound configuration; these technical details cannot change the owner's matching/eligibility policy.
