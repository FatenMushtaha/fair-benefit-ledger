---
name: feature-done
description: Runs the project's finish-a-feature routine (checks, commit, push, Pull Request, progress update, next branch). Use when the owner says "feature approved", or before starting any new feature if the previous one is not approved yet.
---
# Feature done

Follow the "Git & GitHub workflow" in AGENTS.md. This skill is the step-by-step version.

Before starting ANY new feature: read docs/progress.md and run `git status`. If the previous feature is not approved/merged, or there are uncommitted changes, STOP and ask: "The previous feature is not approved yet. Do you want to approve it, commit, push and open a Pull Request before we start the next one?" If the owner says no, record it in docs/progress.md as "not approved" and continue.

When the owner says "feature approved" (or answers yes to the question above):
1. Confirm you are on a feature branch, never main.
2. Run `vendor/bin/pint --dirty --format agent`, then the narrowest relevant tests (`php artisan test --compact`, with a file path or --filter). If anything fails, stop and report; do not commit.
3. PUBLIC REPO CHECK: confirm no .env, secrets, API keys, or real personal/applicant data are in the changes (only fake or anonymized data in seeders, factories, tests, docs).
4. Commit in small logical steps using Conventional Commits.
5. Push the branch and open a Pull Request with `gh pr create`: summary of what was built, tests run, schema changes, and any open questions.
6. Show the PR link and WAIT for the owner's explicit confirmation to merge. Never merge, force-push or rewrite history without it.
7. After the merge: update docs/progress.md (status, branch, PR link, date, key decisions; keep it short and link to docs/intents, docs/specs, docs/plans), then switch to updated main.
8. Ask whether to create the next feature branch from main; do not start the next feature on your own.

## Approval flow (I only answer yes/no)
1. When the feature is finished (code written, tests pass, Pint clean), ask: "This feature looks done. Approve, commit, push and open a Pull Request?" Wait for yes.
2. On yes: commit, push, `gh pr create`, then show the PR link.
3. Second verification (do it yourself, on the PR, before asking me anything else):
   a. `gh pr view`: OPEN, not draft, mergeable, no conflicts (never resolve conflicts yourself; tell me).
   b. If stacked on another PR, make sure its base is correct and the diff has only its own changes.
   c. `gh pr checks`: all pass if CI exists.
   d. `gh pr diff`: PUBLIC REPO CHECK, no .env, secrets, keys, tokens, personal paths, or real personal/applicant data.
   e. Run Pint and `php artisan test --compact` on the branch (say so if no tests exist yet).
   f. docs/progress.md is updated for this feature.
   g. Tenant isolation and RTL/bilingual checklists satisfied if the feature touched models or UI.
4. Report the checklist as a short pass/fail list. If anything fails, stop and tell me; do not ask to merge.
5. If everything passes, ask: "Everything checks out. Merge this PR into main?" Wait for yes.
6. On yes: `gh pr merge --merge --delete-branch`, then `git checkout main && git pull`, verify `git status` is clean and the full tests pass on main, update docs/progress.md (status, PR link, date, decisions) through a small PR or ask me how to land it.
7. Ask: "Start the next feature?" and do not start until I say yes.
If I say no at any gate, record "not approved" in docs/progress.md and stop at that gate.
