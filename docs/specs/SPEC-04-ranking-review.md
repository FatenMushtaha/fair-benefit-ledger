# SPEC-04 — Ranking & Review

**Status:** owner-approved — implementation still requires slice approval

**Planning baseline:** owner-approved [project brief](../project-brief.md) §§9, 11, 17; [MVP master plan](../plans/MASTER-PLAN.md); [Plan 04](../plans/PLAN-04-ranking-review.md).
**Scope:** validate a versioned 4–6-criterion weighted rule set, deterministically score eligible applicants, allocate a fixed capacity and stable waitlist, and record authorized reasoned human decisions. No quotas, advanced ranking, or implementation are included.

## Section 17 traceability

| Section 17 requirement | Specification coverage |
|---|---|
| Simple priority with 4–6 weighted criteria | Slices 1–2 |
| Available quantity cutoff and waiting list | Slice 3 |
| Review: approve, reject, exception with reason | Slice 4 |
| Audit trail and versioned outcome provenance | Slices 1, 2, 4–5 |

**Plan acceptance trace:** PLAN-04 AC-04.1 → Slice 1; AC-04.2 and AC-04.5 → Slice 2; AC-04.3–04.4 → Slice 3; AC-04.6 → Slice 4; AC-04.7 → Slice 5.

## Requirements and non-functional rules

- Eligibility is a hard filter before scoring. Only applicants with resolved identity and a valid, current eligibility snapshot enter the scoring set. Ineligible, unresolved, or blocked/missing-data applicants cannot be treated as zero or ranked as eligible.
- MVP ranking has 4–6 organization-configured criteria, each with explicit input source, normalization/mapping, weight, and reason label. The configuration must be complete before a run; missing required values block the run. Every acceptance requires a separate human final approval, and the final approver must differ from the person who made the underlying review decision. Reject/exception outcomes require a reason; exceptions do not post to the ledger.
- Domain scoring/ranking/allocation are plain PHP, deterministic, independent from HTTP/Livewire/AI. Persist pinned rule version, input fingerprint, score breakdown, rank, outcome and reasons for every run.
- Accepted allocation never exceeds capacity. Remaining eligible applicants have a stable ordered waitlist. Quotas are excluded. No AI output may influence eligibility, score, rank, cutoff or decision.
- Admin manages ranking configuration and reviews; Reviewer reviews ranking outcomes. Admin and Reviewer may record review decisions under their active-organization permissions. Only an Admin who is a different person from the underlying review decision actor may final-approve an acceptance. Decisions and approvals are append-only. Reallocation creates a new immutable result run; no automated acceptance is enabled.

## Ordered implementation slices

### Slice 1 — Versioned ranking-rule configuration and validation

- **Goal/scope:** represent owner-defined criteria and validate a complete ranking configuration before execution.
- **Dependencies:** SPEC-02 rule sets/versions and cycles; SPEC-03 eligibility contract. Criteria, mappings and weights are supplied in the organization/pilot configuration before the first run; no pilot-specific values are shipped as defaults.
- **Tables/constraints:** immutable `rule_versions` config includes 4–6 criteria with stable keys, input source, owner-defined normalization/mapping, weight, translated label/reason key, tie-break sequence, missing-data policy and automation mode. Cycle pins a version. Schema validation rejects unknown keys and mutation after use.
- **Classes/routes/components:** plain PHP `RankingRuleSchema`, `RankingRuleValidator`, `RuleVersion` model/immutability guard, `CycleRunStatus`, `CycleResultOutcome` and `DecisionOutcome` enums, Admin capability policy from the owner-approved matrix and rule-edit routes/UI matching the provisional Figma baseline.
- **Behavior:** accept only a complete configuration with exactly 4–6 unique criteria and valid nonnegative weights summing to 10,000 basis points; each criterion must define a deterministic mapping from source values to integer points 0–10,000. Criteria meaning, source fields, and weights are configured by the organization; incomplete configurations block publication and runs. Every MVP cycle uses `ManualApprovalRequired`; no automated acceptance mode is available. Rule version increments on every approved configuration change; used versions are immutable.
- **Authorization/tenant isolation:** rule configuration and cycle management require Admin role in the active organization; all rule-set/version queries are tenant-scoped and composite FKs enforce same organization.
- **Edge/error handling:** absent criterion source, invalid mapping, duplicate key, missing-data policy or incomplete owner configuration blocks publication/run with field-level errors; no implicit criterion/weight default.
- **Acceptance/tests:**
  - **AC-04.1** Given a rule configuration with fewer than 4 or more than 6 criteria, invalid weights, duplicated keys or missing mappings, when validation runs, then it is rejected with field-level errors; unit tests cover each invalid boundary.
  - **AC-04.2** Given a complete owner-approved configuration, when a cycle starts, then it pins an immutable version and later edits create a new version; feature tests cover tenant scope and immutability.
  - **AC-04.3** Given an absent missing-data/tie/automation policy, when a run is requested, then validation blocks it rather than guessing; service tests verify fail-closed behavior.
- **Definition of Done:** schema and validator enforce the explicit rule contract; no example criteria or business weights are shipped as defaults; tests cover valid and invalid versions.

### Slice 2 — Deterministic eligible-only scoring and provenance

- **Goal/scope:** calculate criterion breakdown and total for eligible applicants using the pinned rule version.
- **Dependencies:** Slice 1; SPEC-03 resolved identities and valid eligibility snapshots.
- **Tables/constraints:** add `cycle_runs(org,cycle_id,id,rule_version_id,run_sequence,input_fingerprint,status,started_at,finished_at,created_by)` unique `(org,cycle_id,id)` and `(org,cycle_id,run_sequence)`; `applicant_scores(org,cycle_id,cycle_run_id,cycle_applicant_id,criterion_points,weighted_numerator,display_score,missing_flags,reason_snapshot)` unique `(org,cycle_run_id,cycle_applicant_id)`. Composite FKs require run, applicant and pinned rule version to share organization/cycle; tenant-prefixed indexes support run queries. This run key refines the plan's result provenance so reruns cannot overwrite prior outputs.
- **Classes/routes/components:** plain PHP `ScoreApplicant`, score input/result value objects, `RunCycleScoring` action/job, canonical serializer/fingerprint service, score breakdown view model.
- **Behavior:** filter out non-eligible/unresolved applicants before scoring; apply each owner-defined mapping to integer criterion points; compute exact integer weighted numerator `sum(points × weight)` (weights total 10,000), compare exact numerators for rank, and round only the display score to two decimal places using half-up rounding. Missing/unverified data follows the approved policy; absent/unsupported policy blocks the run. Include normalized inputs and rule version in fingerprint.
- **Authorization/tenant isolation:** starting a run requires approved active-org capability; job carries organization/cycle/run IDs and re-establishes scope; all joins explicitly constrain tenant.
- **Edge/error handling:** no eligible applicants produces a valid empty score set; duplicate applicant/input rows reject run; non-finite/out-of-range mapping output blocks run; changed inputs create a new run rather than updating old score rows.
- **Acceptance/tests:**
  - **AC-04.4** Given same eligible inputs, rule version and evaluation date, when scoring runs twice, then criterion points, exact numerator, display score and reasons are identical; unit tests compare full results.
  - **AC-04.5** Given an ineligible/unresolved applicant, when scoring runs, then no eligible score is produced; feature/service tests cover filters.
  - **AC-04.6** Given valid points and weights, when calculated, then exact numerator determines ordering and display rounding does not change order; unit tests cover ties and half-up display boundaries.
  - **AC-04.7** Given changed inputs or a rerun, when results persist, then a new `cycle_runs` record is created and prior scores remain immutable; feature tests verify snapshots.
- **Definition of Done:** scoring uses integer arithmetic and no AI; only eligible records score; snapshot provenance is immutable and deterministic.

### Slice 3 — Stable tie order, capacity cutoff and waiting list

- **Goal/scope:** produce a total order and capacity-bounded accepted recommendation plus ordered waiting list.
- **Dependencies:** Slice 2; approved stable tie-break order and configured capacity for the cycle.
- **Tables/constraints:** `cycle_results(org,cycle_id,cycle_run_id,cycle_applicant_id,rank,outcome,tie_values,cutoff_marker,reason_snapshot)` unique `(org,cycle_run_id,cycle_applicant_id)`, indexes `(org,cycle_run_id,rank)` and `(org,cycle_run_id,outcome)`; composite FKs require run, applicant and cycle to match. `cycles.available_quantity` is nonnegative; `cycles.current_cycle_run_id` references a completed run through `(org,cycle_id,run_id)` so a run from another cycle cannot become current.
- **Classes/routes/components:** plain PHP `RankCycleApplicants`, `AllocateCapacity`, `TieBreakComparator`, immutable allocation result objects; no quota service/table.
- **Behavior:** order eligible applicants by exact weighted numerator descending, registration time ascending, then immutable applicant ID ascending. This fixed order is complete and deterministic. Assign the first `min(eligible_count, capacity)` as the accepted recommendation and the rest to the ordered waiting list; capacity zero accepts none. Persist the reason snapshot and each tie value.
- **Authorization/tenant isolation:** run only in the active organization and read only its cycle/applicants; job tenant IDs explicit; guessed foreign cycle/applicant is denied.
- **Edge/error handling:** negative/null capacity is rejected; no eligible rows returns empty outcomes; duplicate applicant IDs are rejected before ordering.
- **Acceptance/tests:**
  - **AC-04.8** Given valid scores, tie policy and capacity, when allocation runs, then accepted count is at most capacity and remaining eligible applicants are ranked on the waitlist; tests cover zero, exact and excess capacity.
  - **AC-04.9** Given equal scores, when tie-breakers are applied, then result order is stable and every comparison value is persisted; tests cover equal values and the final immutable-ID fallback.
  - **AC-04.10** Given equal scores and registration times, when allocation is requested, then immutable applicant ID completes the stable order; service tests verify the final fallback.
- **Definition of Done:** allocation is deterministic and never over capacity; no quota logic exists; prior run results remain immutable.

### Slice 4 — Review queue and reasoned decisions

- **Goal/scope:** show reviewable results and allow authorized approve, reject or exception actions with reasons.
- **Dependencies:** Slice 3; SPEC-01 owner-approved role matrix. The provisional Figma baseline is approved; inspect the review and independent-approval contexts before coding.
- **Tables/constraints:** append-only `decisions(org,cycle_id,cycle_run_id,cycle_result_id,outcome,reason?,actor_id,decided_at,supersedes_id?)`; append-only `final_approvals(org,decision_id,final_approver_id,approved_at)` with one final approval per accepted decision and tenant-safe composite FKs; append-only `audit_events`. Require a non-empty reason for `Rejected` and `Exception`, validate state transitions, and enforce `final_approver_id != decisions.actor_id` in the service and database-compatible validation.
- **Classes/routes/components:** `CycleReviewPolicy`, `RecordCycleDecision` action, decision request/validator, named result list/detail/decision routes, Livewire review components matching the provisional Figma baseline, translated reason labels.
- **Behavior:** display rank, criterion breakdown, eligibility/match evidence and reason snapshot; allow only matrix-approved transitions. Admin and Reviewer may record review decisions. Review decisions are appended, never overwritten. A human `Accepted` review decision is a recommendation only; it cannot be posted until an authorized Admin separately final-approves it using a different user ID from the decision actor. Admin may not approve an acceptance they reviewed. Rejection and exception require reasons; an exception does not become an accepted outcome or post to the ledger. No automated acceptance is allowed.
- **Authorization/tenant isolation:** Admin and Reviewer may access the review queue and only the case evidence needed for their review actions; only Admin may final-approve and only for another user's review decision. Membership + active org + role capability is checked server-side for every list/detail/mutation; policy rechecks all IDs and tenant composite relationships; unauthorized users cannot access reason/person data.
- **Edge/error handling:** stale result run requires refresh; missing reason/invalid transition/unknown outcome is rejected; unavailable Admin or Reviewer authority fails closed; self-approval and non-Admin final approval are rejected; concurrent decisions use optimistic version check and preserve both attempted audit context and winning decision.
- **Acceptance/tests:**
  - **AC-04.11** Given an authorized Admin or Reviewer and current result, when they record an allowed accept/reject/exception decision, then one new decision and audit event are appended; reject/exception require a non-empty reason; feature tests cover each allowed transition.
  - **AC-04.12** Given an accepted review decision, when a different authorized Admin approves it, then a separate final-approval record is appended; the decision actor cannot approve their own decision; feature tests cover distinct-actor, role and authorization boundaries.
  - **AC-04.13** Given a rejected/exception decision with no reason, an unauthorized role, same-actor approval, stale result or foreign ID, when a decision/approval is attempted, then it is denied without altering result or decision history; policy/feature tests cover all cases.
  - **AC-04.14** Given the provisional Figma baseline and either locale, when the review screen renders, then it shows translated reasons/evidence with correct direction and no raw keys; render tests cover `ar` and `en`.
- **Definition of Done:** approved matrix and relevant screen context are followed; only Admin/Reviewer roles can perform review actions, only a distinct Admin can final-approve; all decisions are reasoned where required, tenant-authorized and append-only; no automated acceptance is enabled.

### Slice 5 — Re-run and allocation history

- **Goal/scope:** recalculate after authorized source/review correction without corrupting prior results or audit history.
- **Dependencies:** Slices 2–4 and SPEC-03 correction events.
- **Tables/constraints:** each rerun creates a new `cycle_runs` key and new score/result rows; `cycles.current_cycle_run_id` moves only after successful completion; decision rows continue to reference the result version they decided. `audit_events` records trigger, actor/reason and previous/current run IDs.
- **Classes/routes/components:** `RecalculateCycle` orchestration action/job, atomic current-run pointer update, optimistic lock, rerun authorization policy.
- **Behavior:** a correction or changed input requires a new run; calculate in staging then atomically mark current when complete; failed/partial run never replaces current; prior scores/results/decisions remain read-only history. A human override cannot silently alter another applicant's allocation; any required cascade uses a new deterministic run.
- **Authorization/tenant isolation:** active-org policy for rerun; all inputs/results same tenant; job payload explicitly contains organization/cycle/run IDs.
- **Edge/error handling:** concurrent reruns serialize per cycle or fail with retryable conflict; incomplete jobs retain failed run diagnostics without becoming current; audit append failure rolls back publication.
- **Acceptance/tests:**
  - **AC-04.15** Given a completed current run and changed source, when recalculation completes, then a new full run becomes current atomically and old decisions/results remain unchanged; workflow tests verify.
  - **AC-04.16** Given a failed or cross-tenant rerun, when it executes, then current run stays unchanged and no foreign result is written; job tests cover failure and isolation.
- **Definition of Done:** current pointer changes only after complete successful run; rerun is idempotent/retry-safe; history remains auditable.

## Rollback and test strategy

- Stop scoring/ranking jobs before schema rollback. Keep completed run snapshots and decisions. Correct via a new run/decision; never delete or rewrite prior outcomes. Roll back run tables only if no decision or ledger references exist.
- PHPUnit unit tests cover configuration validation, fixed-point arithmetic, deterministic order and capacity. Feature/job tests cover tenant isolation, authorization, reason requirements, snapshots, failure/retry and history. All fixtures synthetic.
- Manual verification: compare affected review/approval screens against the provisional Figma baseline in Arabic/English and confirm reviewers can explain scores/reasons before coding the UI slice.

## Open questions / conflicts

- **Configuration checkpoint before the first ranking run:** each pilot must provide its 4–6 criteria, source fields, mappings, weights and missing-data policy. Incomplete configuration blocks the run; the Spec defines no sample criteria or weights.
- **Permission baseline:** only Admin and Reviewer perform ranking review; only a different Admin final-approves an acceptance. Report visibility and personal-data fields remain bounded by the approved allowlist and configured audience.
- **No quotas:** capacity is a single cycle quantity. Quota allocation, advanced ranking and what-if simulation are not in this Spec.
- **Technical refinement:** `cycle_runs` and run-keyed score/result snapshots make plan-required provenance and non-destructive reruns explicit; this adds no product feature.
