---
name: feature-done
description: Runs final QA, review, PR verification, and the separately approved Git gates for a completed Spec-driven feature.
---
# Feature done

Follow the lifecycle and Git/GitHub gates in `AGENTS.md`. This skill is the step-by-step final QA and release procedure.

Before starting any feature or Spec slice, read `docs/progress.md` and run `git status`. If the previous feature is not approved/merged, or there are uncommitted changes, stop and use the pre-feature approval question in `AGENTS.md`. Never bypass the owner's instruction. Implement only one approved Spec slice at a time. After completing and testing a slice, report acceptance evidence and wait for explicit owner approval before advancing to the next slice.

When all approved Spec slices are complete, perform final QA and report results before asking to commit:
1. Confirm you are on the intended feature branch, never `main`; inspect the complete diff and verify it matches the approved Spec and design.
2. Validate every acceptance criterion. Run relevant integration, security, authorization, tenant-isolation, bilingual/RTL, and regression tests; run `vendor/bin/pint --dirty --format agent` and relevant PHPUnit tests. Fix failures before proceeding.
3. PUBLIC REPO CHECK: verify no `.env`, secrets, API keys, tokens, personal paths, or real personal/applicant data are included. Use only fake or anonymized test data.
4. Review rollback behavior, tenant boundaries, auditability, deterministic decisions, documentation, and remaining risks/limitations. Update `docs/progress.md` for the owner-approved QA milestone.
5. Ask for explicit commit approval. Only after yes, commit in small Conventional Commit steps. Then ask separately for push approval; only after yes, push the branch. Then ask separately for PR-creation approval; only after yes, open a PR with summary, tests, schema changes, and open questions.
6. Never combine commit, push, PR creation, or merge approvals. A yes at one gate authorizes only that action.

## PR verification and merge gate

After PR creation, run the checklist before asking to merge:

- `gh pr view`: PR is open, not draft, mergeable, and targets the intended base. If stacked, verify base and own diff; never resolve conflicts without owner direction.
- `gh pr checks`: all configured checks pass.
- `gh pr diff`: public-repository check for secrets, keys, tokens, personal paths, and real personal/applicant data.
- Re-run Pint and relevant tests on the PR branch; report if there are no applicable tests.
- `docs/progress.md` accurately reflects approved milestones and PR details.
- Tenant-isolation and RTL/bilingual checklists are complete where applicable.

Report each checklist item as pass/fail with evidence. If a required item fails, stop and report the failure; do not request merge approval yet. If checks pass, ask for explicit merge approval. Never merge automatically. On yes, merge using the approved method, update `docs/progress.md`, switch to updated `main`, and verify the checkout. Ask before creating or starting any subsequent feature branch.

If the owner declines a gate, stop before that action and record the actual milestone state accurately; never imply a later gate was approved or completed.
