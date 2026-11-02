# PLAN-01: Foundation (Draft)

## Goal and scope

Establish authentication, multi-organization membership, validated active-organization selection, organization-scoped authorization, preliminary roles, per-user locale, and the Arabic/English RTL/LTR shell. A user can belong to multiple organizations. This is phase (a) of the [master plan](MASTER-PLAN.md). Figma screens/tokens must be approved before frontend implementation.

## Tables, files and classes

- Tables: global `organizations` and `users` (with locale); `organization_user` with unique `(organization_id,user_id)`, membership status, and organization-scoped `role_key`. A user has one initial role per organization in this conservative proposal; confirm whether multiple roles are needed before implementation. Do not store a single `active_organization_id` on `users` because users can belong to several organizations; active-context persistence is a Spec-level choice.
- Files/classes: authentication flow using existing framework facilities where possible, organization/membership models and policies, active-organization selector, request context/middleware, shared `BelongsToOrganization` trait/global scope, locale middleware, Blade layout/components, `lang/en` and `lang/ar` keys.
- Repository evidence: current code is a Laravel scaffold with no login routes or installed auth/permission package. The proposed first-party session login with organization-invited memberships is conservative, not approved; see open decisions.

## Proposed minimum role matrix — requires owner approval

These are least-privilege recommendations, not confirmed permissions. Every permission is limited to the user's active organization.

| Role | Proposed minimum access |
|---|---|
| **Admin** | Manage organization membership, role assignments and organization settings; access org data, imports, cycles, rules, review decisions, ledger, exports/reports and audit history. |
| **Reviewer** | Read assigned/current cycle cases, match evidence, eligibility, scores and reasons; approve, reject or grant an exception only with a reason. No membership, role, or organization-setting management. |
| **Data Entry** | Upload org files, map columns, correct invalid source rows before evaluation, and view import-quality status. No ranking/eligibility overrides, final decisions, role management, or org settings. |
| **Viewer** | Read-only access to approved lists and essential reports. No raw-file downloads, edits, decisions, membership changes, or settings. Whether person-level fields are visible remains for owner approval. |

Owner must approve or revise each role's data visibility and actions before authorization implementation. Do not seed or enforce this proposed matrix as policy before approval.

## Acceptance criteria and tests

- **AC-01.1** Unauthenticated requests require authentication; the selected account-provisioning flow cannot create an organization membership without an authorized invitation/assignment — feature tests for unauthenticated access and unauthorized membership creation.
- **AC-01.2** A user can be a member of organizations A and B, select either active organization, and switch only to a current membership; a guessed, revoked, stale, or non-member organization fails closed — feature tests for selection, switching, request authorization, and stale session context.
- **AC-01.3** Authorization combines membership, active organization, and the owner-approved role matrix; cross-org IDs are denied even when guessed — policy tests for every role/action boundary and negative cases.
- **AC-01.4** User locale persists and sets `<html lang>` plus `dir=rtl/ltr` — render layout tests in Arabic and English, asserting no raw translation keys.
- **AC-01.5** Organization creation and membership assignment are authorized and scoped — feature tests for membership isolation, role assignment, and cross-tenant read/update/delete denial.

## Tenant-isolation checklist

- Organizations are root records; membership is the only path to tenant context.
- The user must explicitly select an active organization; validate that membership on every switch and request. Never trust a submitted org ID or stale session alone.
- Authorization always checks active organization + membership + approved role; no active context or missing membership fails closed.
- Tenant trait establishes scope and assigns current organization on create. Tenant-to-tenant FKs include `organization_id` on both sides.
- Factories accept organization; test A cannot read/update/delete B records and new tenant records receive the active organization.

## RTL/bilingual checklist

- Identical translation keys in Arabic and English for auth, organization selector, role labels, validation, empty states and locale controls.
- Locale controls `lang`/`dir`; use logical Tailwind utilities and owner-approved Figma tokens/fonts.
- Frontend implementation waits for approved Figma; tests cover both directions, responsive/accessibility states, and directional icons without raw strings or keys.

## Rollback, risks and open questions

- Rollback reversible migrations and auth/layout additions in reverse dependency order; preserve account data if users exist and use a planned data migration for membership/locale changes.
- Risks: cross-org access through stale active-org context, privilege escalation, and accidental leakage while switching organizations.
- MVP-blocking before affected implementation: approve the proposed role matrix, whether memberships allow one or multiple roles, and the authentication/account-provisioning flow. Conservative auth proposal: first-party session login plus organization-invited memberships; this limits unauthorized org creation but requires manual onboarding. No auth implementation/package is present in the scaffold.
- Frontend blocker: approved Figma screens/tokens are not available; do not invent UI. Backend-only work can proceed after Specs with design marked N/A where applicable.
- Other decisions (launch jurisdiction/hosting, retention rules, platform name, pilot identity) block live-data use or pilot configuration as applicable, not this planning review.
