---
name: tenant-isolation
description: Rules that keep each organization's data isolated (organization_id, global scope, queues, exports, tests). Use whenever adding or changing a model, migration, query, job, report or export.
---
# Tenant isolation

Each organization's data is fully isolated and owned by it. A leak between organizations is the worst bug this project can have.

- Single database; every tenant table has a non-null, indexed organization_id foreign key. Unique constraints and composite indexes must include organization_id when uniqueness is per organization.
- Every tenant model uses the shared trait (e.g. BelongsToOrganization) that applies a global scope and sets organization_id automatically on create. Check existing code first and reuse the trait; create it only if it does not exist yet.
- Never query tenant data without the scope. Do not use withoutGlobalScopes() on tenant models except in clearly named, audited, tested code paths (e.g. system maintenance), and explain why in the PR.
- Queued jobs, imports, matching runs, exports and scheduled reports must carry the organization id explicitly and re-establish the tenant context when they run; never rely on the session or the logged-in user inside a job.
- Raw queries and joins (reports, aggregates, exports) must filter by organization_id manually; global scopes do not apply to them.
- The audit log is also tenant-scoped and append-only (no updates or deletes).
- Policies/permissions: a user may only act on records of their own organization, even if they guess an ID or URL.
- No cross-organization comparison or shared registry of people. This is a later, consent-based feature and must not be built implicitly.
- Every new tenant model needs: a factory that accepts an organization, and a feature test proving that (1) organization A cannot read, update or delete organization B's records, (2) new records get the current organization automatically, (3) jobs/exports only touch the right organization's data.
- Test data must be fake. Never use real people's data.
