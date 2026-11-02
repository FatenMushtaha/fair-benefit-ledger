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

## Open decisions (never assume answers)
Platform name; launch markets, data protection laws and hosting; pricing; team-member ineligibility policy; first pilot organization and its rules; whether the default waiting period (12 months) differs by sector.

## Current status
Idea/design stage. No planning or implementation has started yet. Wait for explicit instructions before planning or coding.

## Session start
- Read docs/progress.md at the start of every session to know what is planned, designed, implemented and approved.

## Git & GitHub workflow
- Before starting ANY new feature or task, check docs/progress.md and git status. If the previous feature is not marked approved/merged, or there are uncommitted changes, STOP and ask me: "The previous feature is not approved yet. Do you want to approve it, commit, push and open a Pull Request before we start the next one?" Do not start the next feature until I answer. If I say yes, run the "feature approved" steps (including updating docs/progress.md). If I say no, note it in docs/progress.md as "not approved" and then continue.
- The GitHub repo is PUBLIC. Never commit secrets, .env files, API keys, real personal data, or real applicant/beneficiary data. Use only fake or anonymized data in seeders, factories, tests and docs.
- Never work directly on `main`.
- One feature = one branch named `feature/<short-name>`. Design, planning or docs-only work uses `design/<name>` or `docs/<name>`.
- Commit in small logical steps using Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `refactor:`, `chore:`).
- Before each commit run `vendor/bin/pint --dirty --format agent` and the relevant tests.
- When I say "feature approved": 1) make sure tests pass, 2) commit everything, 3) push the branch, 4) open a Pull Request with `gh pr create` summarizing what was built, 5) wait for my confirmation, then merge into `main`, 6) update docs/progress.md (status, branch, PR link, date, key decisions), 7) create the next branch from the updated `main`.
- Never force-push, rewrite history, or merge without my explicit confirmation.
- When you finish implementing a feature (code written, tests pass, Pint clean), do NOT wait for me to remember. Proactively ask me: "This feature looks done. Do you want to approve it, commit, push and open a Pull Request?" Do not push or merge until I answer yes.
- Also ask me at the end of any design or planning step: "Do you want me to save this as a commit/PR?"

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
Custom skills live in .agents/skills/. Use them in this workflow: intent-interview -> (owner: "intent approved") -> spec-writer -> (owner: "spec approved") -> plan-feature -> (owner: "plan approved") -> implement with tests -> feature-done (when the owner says "feature approved"). Always apply rtl-bilingual-ui for any UI work and tenant-isolation for any model, query, job or export. Intents live in docs/intents/, specs in docs/specs/, plans in docs/plans/. Never skip an approval gate.
