# PLAN-04: Ranking and Review (Draft)

## Goal and scope

Compute explainable deterministic scores and ordered outcomes, allocate the available quantity, form a waiting list, and let authorized humans approve/reject/record exceptions with reasons. Covers phases (f) and (g), after eligibility and identity resolution.

## Tables, files and classes

- Tables: immutable `rule_versions`; `applicant_scores`; `cycle_results`; append-only `decisions` and `audit_events`; required `cycles`, applicant/member, match-review data.
- Files/classes: rule configuration validator; plain PHP scoring, tie-break, quota/cutoff/allocation services and immutable result objects; simulation action; status/outcome enums; cycle run job; review policies and Livewire screens; translated criterion/reason labels.
- Fixed sequence: hard eligibility filters, weighted criteria, optional quotas, configured tie-breakers, quantity cutoff and ordered waitlist. Persist criterion breakdown, missing/unverified inputs, rule version, rank and human-readable reason snapshot. No AI ranking.

## Acceptance criteria and tests

- **AC-04.1** Invalid weights, conflicting rules or unavailable criterion data block a run with a clear validation report — service tests for each validation case.
- **AC-04.2** Scores are deterministic and retain criterion breakdown and missing/unverified markers — unit tests for normalization, weighting, rounding and repeat-run identity.
- **AC-04.3** Quotas and tie-breakers follow configured order and produce stable ranks — tests for quota boundaries and equal-score deterministic ordering.
- **AC-04.4** Cutoff accepts no more than available quantity and orders the remaining eligible applicants on a waitlist — allocation tests for zero, exact and excess capacity.
- **AC-04.5** Results pin rule version, input/evaluation snapshot, and reasons — feature tests for persisted provenance and simulation without side effects.
- **AC-04.6** Reviewer can approve, reject or grant reasoned exception only within their organization/permission; previous decisions remain auditable — feature/policy tests for allowed, denied and append-only correction.
- **AC-04.7** Borderline and uncertain match cases appear in review queue; updates recalculate cascade allocation only through a deterministic new run — workflow tests for rerun and waitlist movement.

## Tenant-isolation checklist

- Every score/result/decision has non-null `organization_id`; unique constraints and indexes include tenant and cycle/applicant.
- Score, allocation and review jobs use explicit tenant IDs; aggregate/raw queries filter tenant manually.
- Policies verify organization membership and role on cycle, applicant and decision IDs.
- Test two-organization isolation for ranking reads, mutations, aggregates, jobs and audit output.

## RTL/bilingual checklist

- Translate criterion names, score reasons, statuses, review actions and validation in both languages; export-facing labels use locale keys.
- Review/results screens render both directions with logical spacing/alignment, bidi-safe names/IDs and localized numbers/dates.
- Test `lang`/`dir`, no raw keys, direction-aware table ordering and reason readability in Arabic and English.

## Rollback, risks and open questions

- Freeze the cycle and retain scored snapshots/decisions; correct via a new pinned rule version and rerun before approval. Do not delete audit/decision history. Roll back schema only when unreferenced.
- Risks: policy errors, unverified data treated as zero, unstable tie-breaking, reviewer override affecting downstream allocation.
- Open: pilot's 4-6 criteria, weights, quotas and tie-break order; automation mode; missing-data policy; team-member rule; appeals and exception authority.
