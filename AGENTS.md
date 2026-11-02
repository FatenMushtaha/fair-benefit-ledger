# Project: fair-benefit-ledger
Bilingual (Arabic/English) SaaS that helps organizations with limited opportunities/resources (starting with incubators and student programs) find out who already benefited and decide who gets priority, using rules each organization defines. Automates import, cleaning, name matching, eligibility, scoring, ranking and reports; humans decide doubtful cases and approve. Full description: docs/project-brief.md (read it before any domain work).

## Stack (decided)
- Laravel 12, PHP 8.2+, local dev on XAMPP, MySQL/MariaDB (DB_CONNECTION=mysql). Avoid DB-specific features (no MySQL-8-only or Postgres-only syntax) so we can migrate later.
- Frontend: Blade + Livewire + Tailwind (Vite) inside this same app. No separate SPA, no API-first for the MVP. UI designs come from Figma (screenshots + design tokens per screen); implement them faithfully, do not invent a design.
- Bilingual with full RTL/LTR: logical Tailwind utilities (ms-/me-/ps-/pe-, rtl:/ltr:), Laravel lang files for all UI strings (no hardcoded text), per-user locale.
- Queues: database driver for now; imports and matching run as chunked queued jobs with progress tracking.
- Tests: PHPUnit feature tests mostly, using factories.

## Architecture rules
- Multi-tenancy: single database, organization_id on every tenant table, enforced by a global scope + trait. Tests must prove isolation.
- Domain logic (eligibility, scoring, ranking, tie-breakers, cut-off, waiting list) lives in plain PHP classes under app/Services or app/Actions, with no dependency on HTTP, Livewire or AI. Must be deterministic (same input => same output).
- AI/LLM only for suggestions (column mapping, similar names, text cleaning); never decides eligibility or ranking.
- Rules are versioned; every Cycle stores the rule version it ran with.
- Audit trail is append-only (no updates/deletes) and records who/what/when/reason.
- Name matching: normalized name columns + blocking keys, never all-against-all; confidence bands (high/medium/low) with configurable thresholds; medium goes to human review.
- Use Enums (TitleCase keys) for statuses.

## Pre-approved
- New folders: app/Services, app/Actions, app/Enums, app/Jobs, app/Support, docs/
- Packages (install without asking): livewire/livewire, spatie/laravel-permission, openspout/openspout or maatwebsite/excel (pick one, explain why), league/csv. Anything else: ask first.
- docs/project-brief.md may be edited when project decisions change.

## Confirmed Plan 01 decisions
- A user may belong to multiple organizations and must select an active organization.
- Initial roles are Admin, Reviewer, Data Entry, and Viewer. The permission matrix is not approved; do not infer role capabilities.
- Figma designs are not yet available. Screens, reusable components, and design tokens must be prepared and approved before frontend implementation. Do not invent UI decisions.

## Open decisions (never assume answers)
Platform name; launch markets, data protection laws and hosting; pricing; team-member ineligibility policy; first pilot organization and its rules; whether the default waiting period (12 months) differs by sector.

## MVP scope boundaries
- XLSX and CSV import/export are in scope. PDF export remains an open question and is not MVP scope.
- Quotas, billing, cross-organization matching, and unrelated future modules are out of scope.
- Security, authorization, tenant isolation, auditability, matching safeguards, validation, rollback, and appropriate testing remain mandatory.
- Project-brief sections 16, 18, and 19 are reference material, not additional MVP feature scope. Risk and security safeguards still apply where relevant.

## Current status
The MVP master plan and five phase plans are drafts under owner review in PR #6 on `docs/mvp-plan`. Specifications, UI/UX designs, and implementation are not approved. Preserve PR #6 and its branch unless the owner explicitly directs otherwise.

## Development lifecycle
Follow this sequence for each feature or approved Spec slice:

1. **Planning:** review `docs/project-brief.md`, `AGENTS.md`, `docs/progress.md`, and applicable skills. Use `intent-interview` to clarify requirements, scope, dependencies, architecture constraints, risks, assumptions, milestones, and open decisions. Create or review the high-level plan/intent. Obtain explicit owner planning approval before specifications.
2. **Technical Specifications (Specs):** after planning approval, use `spec-writer` to turn the approved intent and phase plan into the implementation-ready Spec. Specs are the detailed technical implementation plans; do not add a mandatory technical-planning phase after Spec approval. Divide work into small, independently testable slices. Each slice defines requirements, approach, schema changes, business logic, files/classes, edge cases, acceptance criteria, security and tenant rules, and tests. Resolve implementation-blocking questions and obtain explicit Spec approval before implementation.
3. **UI/UX Design:** prepare/review Figma screens, reusable components, and design tokens. Cover Arabic/English, RTL/LTR, responsive layouts, accessibility, validation, loading, empty, error, and success states. Obtain explicit design approval before frontend implementation. For backend-only slices, record why design is not applicable.
4. **Incremental Implementation + Testing:** implement one approved Spec slice at a time. Follow the approved Spec, design, project conventions, and relevant skills. Write and run tests throughout development. Preserve organization isolation, authorization, auditability, matching safeguards, rollback support, and deterministic domain logic. Do not add unrelated scope or advance to the next slice without explicit owner approval.
5. **Final QA & Review:** verify every acceptance criterion; run applicable integration, security, authorization, tenant-isolation, bilingual/RTL, and regression tests; run Pint and relevant PHPUnit tests. Review the complete diff, quality, architecture, scope, and documentation; check for secrets and personal data. Fix failures before reporting evidence, risks, and limitations.
6. **Owner Approval:** request the separate Git/GitHub approvals listed below only after QA. Update `docs/progress.md` at owner-approved milestones.
7. **Merge:** after PR verification, ask for explicit merge approval. Never merge automatically.

`plan-feature` is optional during initial planning or Spec preparation. It is not a separate required phase after Specs and must not change an approved product scope. If used, carry useful technical detail into the high-level plan or the Spec before Spec approval; do not maintain a second, post-Spec implementation plan.

## Session start
- Read docs/progress.md at the start of every session to know what is planned, designed, implemented and approved.

## Git & GitHub workflow
- If `gh` is not found on PATH, use the full path: & 'C:\Program Files\GitHub CLI\gh.exe'.
- Before any new feature or slice, read `docs/progress.md` and check `git status`. If the previous work is unapproved or the tree is dirty, stop and resolve that state with the owner before proceeding; do not overwrite or discard changes. Ask whether the previous implementation/QA milestone is approved, and make clear that this does not authorize any Git action. A yes does not replace the separate commit, push, PR, or merge approvals below.
- Keep approval gates separate and ask for explicit yes/no approval before each action: (1) commit, (2) push, (3) create/open a PR, and (4) merge. A yes for one action does not authorize another. Never force-push, rewrite history, or merge without the applicable explicit approval.
- After opening a PR, run the verification checklist in `.agents/skills/feature-done/SKILL.md` and report the result before asking for merge approval.
- The GitHub repo is PUBLIC. Never commit secrets, .env files, API keys, real personal data, or real applicant/beneficiary data. Use only fake or anonymized data in seeders, factories, tests and docs.
- Never work directly on `main`.
- One feature = one branch named `feature/<short-name>`. Design, planning or docs-only work uses `design/<name>` or `docs/<name>`.
- Commit in small logical steps using Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `refactor:`, `chore:`).
- Before each commit run `vendor/bin/pint --dirty --format agent` and the relevant tests.
- Never work directly on `main`. Use `feature/<short-name>` for implementation and `design/<name>` or `docs/<name>` for design, planning, or documentation work.
- Before each commit run `vendor/bin/pint --dirty --format agent` and relevant tests when applicable. For docs-only changes, run appropriate diff/consistency checks; do not claim application tests were run if they were not.
- After each approved milestone, update `docs/progress.md` with accurate status, branch, PR link when available, date, and concise decisions. After merge, record completion and verify the updated main branch.
- At the end of each planning, Spec, and design phase, present the artifact and ask for that phase's explicit approval. At the end of each implementation slice, report tests/acceptance evidence and wait for explicit approval before advancing to the next slice.
- Once final QA passes, ask separately for commit approval, then after committing ask for push approval, then after pushing ask for PR creation approval. After PR creation, verify it and ask separately for merge approval. Do not combine these gates into a single approval request.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.2. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

## Project skills
Custom skills live in `.agents/skills/`. Use `intent-interview` during Planning and `spec-writer` during Technical Specifications. `plan-feature` is optional only during initial planning or Spec preparation, as described above; it is not a required post-Spec phase. Specs live in `docs/specs/` and contain the detailed, slice-based implementation plan. Keep intents in `docs/intents/`. Apply `testing-best-practices` when defining test strategy or coverage, and `laravel-best-practices` when writing Laravel backend code. Always apply `rtl-bilingual-ui` for frontend/UI work and `tenant-isolation` for any organization-scoped model, query, job, report, or export. Use `feature-done` for final QA, PR verification, and the separate approval gates. Only the owner can approve an intent/planning baseline, Spec, design, implementation slice, commit, push, PR, or merge. Never skip a gate.
