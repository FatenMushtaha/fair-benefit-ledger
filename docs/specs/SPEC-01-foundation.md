# SPEC-01 — Foundation

**Status:** draft — owner approval required

**Planning baseline:** owner-approved [project brief](../project-brief.md) §17, [MVP master plan](../plans/MASTER-PLAN.md), and [Plan 01](../plans/PLAN-01-foundation.md). These documents are the authoritative intent baseline under the approved `spec-writer` exception.
**Scope:** authentication, organizations and memberships, active organization, authorization, per-user locale, and the approved-design-dependent bilingual shell. No implementation is authorized by this Spec draft.

## Section 17 traceability

| Section 17 requirement | Specification coverage |
|---|---|
| Authentication and basic roles | Slices 1 and 3 (role capabilities remain owner-blocked) |
| Arabic/English interface and RTL/LTR | Slices 4–5 (front-end implementation waits for approved Figma) |
| Tenant security boundary supporting the MVP | Slices 1–3 |

**Plan acceptance trace:** PLAN-01 AC-01.1 → Slice 1; AC-01.2 → Slice 2; AC-01.3 → Slice 3; AC-01.4 → Slices 4–5; AC-01.5 → Slices 1–2.

**Technical refinement:** initialize the single append-only audit table in this foundation phase so organization/membership/security changes are auditable from first use; SPEC-02 adds domain event types to the same table.

## Requirements and non-functional rules

- A user may belong to multiple organizations and must explicitly select an active organization. Every switch and request revalidates current membership and fails closed on missing, stale, disabled, or guessed context.
- Initial role keys are Admin, Reviewer, Data Entry, Viewer. **Capabilities and whether memberships support one or multiple roles are BLOCKED — OWNER DECISION REQUIRED.**
- Authentication/account-provisioning flow is **BLOCKED — OWNER DECISION REQUIRED**. Recommendation: first-party session authentication with organization-invited memberships; this reduces unapproved organization creation but requires manual onboarding.
- Tenant data is inaccessible without a validated active organization. Organization membership is the only way to enter tenant context.
- Locale is per-user Arabic or English; rendered documents set `lang` and `dir`. All UI strings have matching `lang/en` and `lang/ar` keys. Frontend layout is blocked until approved Figma screens/tokens exist.
- Tenant tables always include non-null `organization_id`; policies, scoped relations, constraints, jobs and raw queries all enforce the same boundary. Audit records are append-only.

## Cross-Spec execution order

1. SPEC-01 establishes authentication, active tenant context, audit base and locale; authorization-dependent actions wait for the owner-approved matrix.
2. SPEC-02 adds tenant-safe domain schema and import staging; it depends on SPEC-01 tenant context.
3. SPEC-03 consumes valid staged rows and adds matching/eligibility snapshots; it depends on SPEC-02.
4. SPEC-04 scores only SPEC-03 eligible applicants and persists immutable allocation runs.
5. SPEC-05 posts only valid SPEC-04 decisions, then provides tenant-scoped ledger reports/exports.

Frontend slices in any Spec remain blocked until their Figma designs are approved. A blocked policy slice does not prevent independent technical slices from proceeding.

## Ordered implementation slices

### Slice 1 — Authentication, organization and membership foundation

- **Goal/scope:** create root organizations and users, secure sign-in/out, and represent many-to-many memberships. **BLOCKED — OWNER DECISION REQUIRED** for authentication/account provisioning and invitation/assignment flow; do not choose the recommendation without approval.
- **Dependencies:** none; this is the foundation for all later Specs.
- **Tables/constraints:** global `users`; global `organizations`; `organization_user(organization_id,user_id,role_key,status,created_at,updated_at)`, unique `(organization_id,user_id)`, FKs to organization and user. Whether `role_key` is singular or membership roles are many-to-many is blocked; schema must follow the approved answer. Create the tenant-scoped append-only `audit_events(organization_id,actor_id,event_type,subject_type,subject_id,reason,payload,occurred_at,correlation_id)` base here so membership/security changes are auditable before domain imports exist.
- **Classes/routes/components:** auth provider/routes and login/logout requests; `User`, `Organization`, `OrganizationMembership`; membership assignment service/policy. Exact auth mechanism follows owner decision.
- **Behavior:** unauthenticated protected requests redirect/respond according to the selected first-party auth flow; membership is created only by an authorized provisioning action; deactivated membership is not usable. Membership creation, revocation and role changes append audit events in the same transaction. No public self-service organization creation unless explicitly approved.
- **Authorization/tenant isolation:** never infer organization from an untrusted submitted ID; membership mutation must be authorized. Tenant access is unavailable until a membership and active context are both verified.
- **Edge/error handling:** duplicate membership returns a validation conflict; unknown, disabled or revoked accounts receive safe generic auth errors; provisioning failure leaves no partial membership.
- **Acceptance/tests:**
  - **AC-01.1** Given an unauthenticated user, when a protected route is requested, then access is denied/redirected by the approved auth flow; feature tests cover unauthenticated access and logout/session invalidation.
  - **AC-01.2** Given no approved provisioning permission, when a user submits membership creation, then no organization or membership is created; feature tests cover allowed and denied provisioning after the owner-approved flow is encoded.
  - **AC-01.3** Given two active memberships, when membership records are loaded, then each is organization-scoped and unique; database tests cover duplicate and cross-organization references.
  - **AC-01.4** Given membership creation, revocation or role change, when it commits, then one append-only tenant audit event records actor, subject, time and reason; feature tests cover atomicity and denied mutation.
- **Definition of Done:** owner decision recorded; auth/provisioning matches it; membership constraints and authorization tests pass; fake data only; no tenant route is reachable without authentication. Runtime DB credentials may insert/select audit rows but cannot update/delete them; schema migrations use a separate privileged connection.

### Slice 2 — Active organization context and tenant boundary

- **Goal/scope:** let an authenticated member select/switch an active organization and enforce that context on requests.
- **Dependencies:** Slice 1 and its approved membership/auth design.
- **Tables/constraints:** no additional tenant tables. Store active organization in the authenticated server-side session; never treat a user-supplied org ID or session value as proof of membership.
- **Classes/routes/components:** `SetActiveOrganization` action, active-organization middleware/context service, named `POST /active-organization` route, organization selector endpoint/component after design approval, shared `BelongsToOrganization` trait/global scope.
- **Behavior:** switch accepts an organization identifier, verifies authenticated user + current active membership, then replaces session context; every tenant request repeats this check. Missing/stale context fails closed and prompts re-selection through the approved UI.
- **Authorization/tenant isolation:** tenant models assign the validated organization on create; scoped read/update/delete reject cross-org IDs. Queue jobs receive explicit organization IDs and re-establish scope rather than depending on session.
- **Edge/error handling:** revoked membership invalidates existing session context; unknown/non-member IDs return a non-disclosing denial; session fixation protections follow the framework auth flow.
- **Acceptance/tests:**
  - **AC-01.5** Given a member of organizations A and B, when they select either, then subsequent tenant requests use only that organization; feature tests cover switching and current membership.
  - **AC-01.6** Given a guessed, stale, revoked, or non-member organization ID, when selection or a tenant request occurs, then it fails closed and no tenant data is returned or mutated; tests cover read/write/delete denial and session revocation.
  - **AC-01.7** Given a tenant model is created under validated context, when persisted, then its organization ID is assigned from context and cannot be overridden by request input; feature tests verify ownership.
- **Definition of Done:** two-organization isolation tests pass for requests and creation; middleware and global scope fail closed; no controller/job relies on ambient context without explicit revalidation.

### Slice 3 — Organization authorization and role policy

- **Goal/scope:** enforce the owner-approved role-permission matrix for every action, with membership and active organization checks.
- **Dependencies:** Slice 1 membership; Slice 2 active context. **BLOCKED — OWNER DECISION REQUIRED** until the permission matrix, person-level visibility and one/multiple-role model are approved.
- **Tables/constraints:** membership role assignment as decided by owner; if separate permissions are adopted, assignments must be scoped to organization. No role capability may be seeded before approval.
- **Classes/routes/components:** policies/gates for organization and membership; centralized authorization mapping; endpoint middleware where appropriate. No UI permission controls before approved design.
- **Behavior:** authorization evaluates authenticated user + active organization + current membership + approved role/capability on each action; denial is default. Do not infer capabilities from role names.
- **Authorization/tenant isolation:** guessed cross-tenant IDs deny even when the caller has a valid role elsewhere; jobs/downloads re-check explicit tenant authorization where user-facing.
- **Edge/error handling:** missing mapping, unknown role or missing context denies; role changes take effect on the next request; audit security-relevant role changes.
- **Acceptance/tests:**
  - **AC-01.8** Given an owner-approved matrix, when each role attempts each defined action within its organization, then only approved actions succeed; policy/feature tests cover every matrix cell and negative case.
  - **AC-01.9** Given an unknown role, absent active org, or cross-org identifier, when an action is attempted, then it is denied; tests cover fail-closed behavior.
- **Definition of Done:** owner-approved matrix is the single policy source; all matrix rows have tests; no role capability is inferred or silently granted.

### Slice 4 — Locale persistence and request localization

- **Goal/scope:** store and apply each user's Arabic/English locale.
- **Dependencies:** Slice 1 user/auth model.
- **Tables/constraints:** `users.locale`, constrained in application to `ar` or `en`; fallback is `en` for null/invalid legacy values until user selects a locale.
- **Classes/routes/components:** locale preference action/route, locale middleware, paired Laravel language files `lang/ar` and `lang/en`.
- **Behavior:** persist validated locale choice; authenticated requests use the stored locale; public/auth screens use the defined fallback. Normalize Arabic-Indic digits before parsing numeric/date inputs in later Specs.
- **Authorization/tenant isolation:** a user can change only their own locale; locale never changes tenant context.
- **Edge/error handling:** invalid locale is rejected without changing preference; missing translation keys are found by tests/linting and never intentionally shown.
- **Acceptance/tests:**
  - **AC-01.10** Given locale `ar` or `en`, when the user's page is rendered, then language and direction metadata are `ar/rtl` or `en/ltr` respectively; feature tests cover both and fallback.
  - **AC-01.11** Given an invalid locale value, when submitted, then it is rejected and the previous setting remains; feature test covers persistence.
- **Definition of Done:** user-scoped locale persists, paired keys exist for strings introduced in this slice, and render tests assert language/direction.

### Slice 5 — Approved bilingual shell and foundation screens

- **Goal/scope:** implement only owner-approved Figma frames/tokens for sign-in, organization selection, locale, and shell.
- **Dependencies:** Slices 1–4; **BLOCKED — OWNER DECISION REQUIRED** until approved Figma screens/tokens and accessibility/responsive states are supplied.
- **Tables/constraints:** none.
- **Classes/routes/components:** Blade layout and reusable Livewire/Blade components exactly matching approved designs; translation keys in paired language files; logical Tailwind utilities.
- **Behavior:** set HTML `lang`/`dir`; adapt only approved screen states for responsive, loading, empty, validation, error and success behavior. Do not invent screens, flows, colors, fonts or role-visible actions.
- **Authorization/tenant isolation:** organization selector lists only current memberships; actions revalidate server-side regardless of UI visibility.
- **Edge/error handling:** missing membership has an approved empty/error state; mixed-script names/IDs use `<bdi>` or `dir="auto"`; directional icons follow direction.
- **Acceptance/tests:**
  - **AC-01.12** Given approved designs and locale `ar` or `en`, when each screen renders, then it matches the corresponding design contract, direction and translation keys; feature tests cover both locales and no raw keys.
  - **AC-01.13** Given a user lacks a membership, when organization selection renders, then no tenant data is disclosed and the approved empty/error state appears; feature test covers the boundary.
- **Definition of Done:** design approval recorded; responsive/accessibility states reviewed; Arabic and English render tests pass; no unapproved UI behavior exists.

## Cross-slice rollback and test strategy

- Use reversible schema changes before account data exists; after memberships exist, preserve users and migrate membership data deliberately. Never delete audit events. Revoke active sessions when membership is revoked.
- PHPUnit feature tests with fake organizations/users cover authentication, membership, tenant boundaries, authorization matrix and bilingual rendering. Add unit tests only for isolated policy/value logic. Run tests during each implementation slice and regression tests across prior slices.
- Manual verification: compare implemented screen states against approved Figma; accessibility and responsive behavior require design review in both directions.

## Open questions / conflicts

- **BLOCKED — OWNER DECISION REQUIRED:** authentication/account creation/recovery and membership invitation/assignment flow. Recommendation: first-party session auth and invited memberships.
- **BLOCKED — OWNER DECISION REQUIRED:** role-permission matrix, person-level visibility, and one vs multiple roles per membership. Recommendation: use the least-privilege proposal in [Plan 01](../plans/PLAN-01-foundation.md)—Admin manages organization membership/settings; Reviewer decides assigned/current cases with reasons; Data Entry imports/corrects source rows; Viewer reads approved lists/reports without raw-file access—and one role per membership. This keeps policy small but limits task separation; approve/revise each capability and visibility before enforcement.
- **BLOCKED — OWNER DECISION REQUIRED:** Figma screens/tokens before Slice 5 frontend implementation.
- **BLOCKED before live personal data:** jurisdiction/hosting, encryption and retention/deletion policy.
