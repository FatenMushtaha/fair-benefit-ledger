# PLAN-04: Ranking and Review (Draft)

## Goal and scope

Compute explainable deterministic scores from 4–6 configured weighted criteria, allocate no more than available capacity, form a stable waiting list, and let authorized active-organization reviewers approve/reject/record exceptions with reasons. Covers phases (f) and (g), after eligibility and identity resolution.

## Tables, files and classes

- Tables: immutable `rule_versions`; `applicant_scores`; `cycle_results`; append-only `decisions` and `audit_events`; related cycles, applicants/members and match reviews with composite tenant-safe FKs.
- Files/classes: rule configuration validator; plain PHP scoring, deterministic tie-break and capacity allocation services with immutable results; status/outcome enums; cycle run job; active-org review policies/screens; translated criterion/reason labels. Advanced what-if simulation is deferred.
- Fixed sequence: hard eligibility filters, 4–6 weighted criteria, configured tie-breakers, capacity cutoff and ordered waitlist. Quotas are out of MVP scope. Persist criterion breakdown, missing/unverified inputs, pinned rule version, rank and reason snapshot. No AI ranking.

## Acceptance criteria and tests

- **AC-04.1** A run requires 4–6 criteria and valid configured weights/rules/data; invalid configuration blocks the run with field-level reasons — tests for lower/upper count bounds, invalid weights and unavailable inputs.
- **AC-04.2** Scores are deterministic and retain criterion breakdown plus missing/unverified markers; unverified data is never silently treated as zero — service tests for repeat-run equality and the owner-approved missing-data policy; absent policy blocks the run.
- **AC-04.3** Configured tie-breakers produce a stable total order — tests for equal scores and final deterministic fallback. Exact rounding and tie evaluation are defined in Specs, not assumed here.
- **AC-04.4** Accepted allocation never exceeds available capacity; remaining eligible applicants receive a stable ordered waitlist — tests for zero, exact, and excess capacity.
- **AC-04.5** Results pin rule version, evaluation date/input provenance and reason snapshot — persistence tests.
- **AC-04.6** An authorized reviewer in the active organization can approve, reject or grant an exception only with a reason; unauthorized users cannot; every choice is append-only and auditable — feature/policy tests.
- **AC-04.7** Human changes do not silently rewrite ranks or capacity; a change requiring reallocation creates a deterministic rerun and preserves the prior result/decision history — workflow tests for rerun and waitlist movement.

## Tenant-isolation checklist

- Every score/result/decision has non-null `organization_id`; unique constraints and composite FKs include tenant and cycle/applicant.
- Score, allocation and review jobs use explicit tenant IDs; aggregate/raw queries filter tenant manually.
- Policies verify active organization, membership, and owner-approved role on cycle, applicant and decision IDs; missing context fails closed.
- Test two-organization isolation for ranking reads, mutations, aggregates, jobs and audit output.

## RTL/bilingual checklist

- Translate criterion names, score reasons, statuses, review actions and validation in both languages; export-facing labels use locale keys.
- Review/results screens render both directions with logical spacing/alignment, bidi-safe names/IDs and localized numbers/dates.
- Test `lang`/`dir`, no raw keys, direction-aware table ordering and reason readability in Arabic and English.

## Rollback, risks and open questions

- Freeze the cycle and retain scored snapshots/decisions; correct via an authorized new decision and deterministic rerun under a pinned rule version. Do not delete audit/decision history. Roll back schema only when unreferenced.
- Risks: policy errors, unverified data treated as zero, unstable tie-breaking, reviewer override affecting downstream allocation.
- MVP-blocking for a configured pilot: owner supplies 4–6 criteria, weights, tie-break order, automation/review mode, missing-data policy, and who may approve exceptions. Until a missing-data policy exists, block the run rather than score missing values as zero.
- Spec-level: numeric precision/rounding, tie-break evaluation timing, stable fallback key, state transitions and how reviewer changes trigger allocation reruns.
- Quotas and complex appeal-management are excluded from MVP; no quota fields or appeal system are planned.
