# PLAN-01: Foundation (Draft)

## Goal and scope

Establish authenticated organization membership, basic roles, per-user locale, and the shared Arabic/English RTL/LTR shell. This is phase (a) of the [master plan](MASTER-PLAN.md). Implement only after owner approval and approved design/spec inputs.

## Tables, files and classes

- Tables: `organizations`; `users` with locale; `organization_user` membership and role/permission assignment. Framework auth/session and permission tables as required by the already approved stack.
- Files/classes: auth routes/controllers or existing Laravel auth flow, organization model/policy, membership model, tenant-context middleware/service, shared `BelongsToOrganization` trait/global scope for tenant models, locale middleware, Blade layout/components, `lang/en` and `lang/ar` keys.
- Reuse existing Laravel auth/components before adding. Use approved Figma frames/tokens; no new visual design assumptions.

## Acceptance criteria and tests

- **AC-01.1** Unauthenticated access redirects through the chosen auth flow; authenticated users only see organizations they belong to — feature tests for access and guessed organization IDs.
- **AC-01.2** Role permissions gate admin, reviewer, data-entry and view-only actions — policy/feature tests for allowed and denied actions.
- **AC-01.3** User locale persists and sets `<html lang>` plus `dir=rtl/ltr` — render layout tests in Arabic and English, assert no raw translation keys.
- **AC-01.4** Organization creation/membership is scoped and validated — feature tests for membership isolation and role assignment.

## Tenant-isolation checklist

- Organizations are root records; membership is the only path to tenant context.
- Never infer tenant from a submitted ID alone; authorize membership on every request.
- Tenant trait establishes scope and assigns current organization on create; missing context fails closed.
- Factories accept organization; test cross-org read/update/delete denial and automatic ownership.

## RTL/bilingual checklist

- Identical translation keys in Arabic and English for auth, navigation, roles, validation, empty states and locale controls.
- Locale controls `lang`/`dir`; use logical Tailwind utilities and approved design tokens/fonts.
- Test mirrored layout and directional icons in both locales; no raw strings or hardcoded UI copy.

## Rollback, risks and open questions

- Rollback reversible migrations and auth/layout additions in reverse dependency order; preserve account data if users exist and use a planned data migration for membership/locale changes.
- Risks: users belonging to multiple organizations, privilege escalation via stale tenant context, Figma direction gaps.
- Open: public platform name, launch market/hosting/law, pilot identity. Role names above are MVP assumptions from the brief and need owner confirmation; exact Figma designs remain input.
