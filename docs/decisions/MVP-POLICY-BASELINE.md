# MVP Policy Baseline

**Recorded:** 2026-11-02  
**Status:** Initial owner-approved policy baseline; detailed authorization matrix and final Spec approval remain pending.  
**Scope:** Existing MVP Specs and the approved end-to-end distribution workflow only.

This record consolidates the owner's approval of the ten MVP policy recommendations and two clarifications. It does not approve the five Specs for implementation. The Specs remain drafts until the owner gives explicit final Spec approval.

## Approved policy

- **Authentication and membership:** use first-party session authentication and organization-invited memberships; no public self-service organization creation. A user may belong to multiple organizations and selects a validated active organization.
- **Roles:** use one role per organization membership: Admin, Reviewer, Data Entry, or Viewer. Admin manages organization settings and membership; Reviewer performs case review; Data Entry handles imports and source corrections; Viewer has restricted read-only access. Do not infer other privileges from role names. A detailed least-privilege matrix is proposed in [SPEC-01](../specs/SPEC-01-foundation.md) and must be confirmed before authorization implementation.
- **Matching:** only exact matches on organization-designated authoritative identifiers may auto-link. Name candidates always require human review. Missing identifier configuration blocks automatic linking.
- **Eligibility:** mixed-eligibility teams require human review. `Accepted` and `Active` count for the one-active-project limit. Only `Completed` participation counts as a completed benefit for waiting-period eligibility. Withdrawals and noncompletion remain visible but do not count as completed benefits. Use the 12-month incubator waiting period; other sector periods require explicit configuration.
- **Ranking:** configure criteria and weights per organization/pilot; block incomplete configurations. Order equal scores by registration time ascending, then immutable applicant ID ascending. Require human final approval for every acceptance.
- **Review and final approval:** record rejection/exception reasons; prohibit self-approval. Final approval must be by an authorized person other than the person who made the underlying review decision. Exceptions do not post to the ledger.
- **Ledger and replacement:** an independently approved acceptance may create one participation-history record. Participation status is distinct from completed-benefit eligibility. A withdrawal/correction preserves history; capacity replacement requires a new allocation and a new independent approval before posting.
- **Reports and exports:** support private XLSX/CSV only, with an approved field allowlist and configurable expiry. PDF, quotas, billing, and cross-organization matching remain outside MVP scope.
- **Data safety:** synthetic data only until jurisdiction/hosting, encryption, and retention/deletion safeguards are approved.
- **Design baseline:** the existing Figma file is the provisional MVP visual baseline. Derive responsive and RTL/LTR layouts from the approved design system; do not require duplicate frames for every locale or viewport. Inspect only affected screens and genuinely missing critical interactions before their UI slices.

## Remaining checkpoints

- Confirm or revise the detailed SPEC-01 permission matrix, including person-level visibility and who may read reports, create/download exports, and view audit history. This is required before SPEC-01 Slice 3 and authorization-dependent actions.
- Enter and review the first pilot's required import fields, authoritative identifier types, source/date formats, size/row limits, and quality thresholds before processing a batch. Missing values block the batch in `NeedsConfiguration`.
- Enter and review the pilot's 4–6 ranking criteria, source mappings, weights, and missing-data policy before its first ranking run. Missing required inputs block the run.
- Approve report audiences/field allowlists and configure export expiry before generating reports or exports.
- Approve jurisdiction/hosting and live-data encryption/retention/deletion safeguards before any real personal data is used.

## Scope and approval gates

This policy baseline adds no MVP features. The five Specs remain drafts on `docs/mvp-specs` in PR #8. Do not implement until the owner separately approves the final Spec set; do not enforce the proposed permission matrix until its detailed confirmation is recorded.
