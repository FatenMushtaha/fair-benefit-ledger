# MVP Policy Baseline

**Recorded:** 2026-11-02
**Status:** Owner-approved MVP policy, authorization baseline, and five Specs; implementation slice approval remains separate.
**Scope:** Existing MVP Specs and the approved end-to-end distribution workflow only.

This record consolidates the owner's approval of the MVP policy recommendations and clarifications, including the final permission matrix. The owner has approved all five Specs and authorized their merge after the stated verification gates. Implementation remains prohibited until a specific slice is separately approved.

## Approved policy

- **Authentication and membership:** use first-party session authentication and organization-invited memberships; no public self-service organization creation. A user may belong to multiple organizations and selects a validated active organization.
- **Roles:** one role per organization membership; a user may have different roles across organizations. The [owner-approved least-privilege matrix](../specs/SPEC-01-foundation.md#owner-approved-least-privilege-permission-matrix) is the sole authorization source. Admin manages organization settings/membership, programs/cycles, imports, match/ranking reviews, reports, personal-data exports, and audit logs. Reviewer reviews identity matches, eligibility, rankings, rejections, and exceptions. Data Entry uploads/maps/validates/corrects source data and sees only necessary operational records. Viewer reads only approved restricted reports/lists, with no raw files or unrestricted personal data. Only a distinct authorized Admin may final-approve an acceptance. Every operation enforces server-side permission checks and tenant isolation.
- **Matching:** only exact matches on organization-designated authoritative identifiers may auto-link. Name candidates always require human review. Missing identifier configuration blocks automatic linking.
- **Eligibility:** mixed-eligibility teams require human review. `Accepted` and `Active` count for the one-active-project limit. Only `Completed` participation counts as a completed benefit for waiting-period eligibility. Withdrawals and noncompletion remain visible but do not count as completed benefits. Use the 12-month incubator waiting period; other sector periods require explicit configuration.
- **Ranking:** configure criteria and weights per organization/pilot; block incomplete configurations. Order equal scores by registration time ascending, then immutable applicant ID ascending. Require human final approval for every acceptance.
- **Review and final approval:** record rejection/exception reasons; prohibit self-approval. Final approval must be by an authorized person other than the person who made the underlying review decision. Exceptions do not post to the ledger.
- **Ledger and replacement:** an independently approved acceptance may create one participation-history record. Participation status is distinct from completed-benefit eligibility. A withdrawal/correction preserves history; capacity replacement requires a new allocation and a new independent approval before posting.
- **Reports and exports:** support private XLSX/CSV only, with an approved field allowlist and configurable expiry. PDF, quotas, billing, and cross-organization matching remain outside MVP scope.
- **Data safety:** synthetic data only until jurisdiction/hosting, encryption, and retention/deletion safeguards are approved.
- **Design baseline:** the existing Figma file is the provisional MVP visual baseline. Derive responsive and RTL/LTR layouts from the approved design system; do not require duplicate frames for every locale or viewport. Inspect only affected screens and genuinely missing critical interactions before their UI slices.

## Remaining checkpoints

- Resolve which approved role may change participation status for withdrawal or ledger correction before SPEC-05 Slice 2; the approved matrix does not assign this capability.
- Enter and review the first pilot's required import fields, authoritative identifier types, source/date formats, size/row limits, and quality thresholds before processing a batch. Missing values block the batch in `NeedsConfiguration`.
- Enter and review the pilot's 4–6 ranking criteria, source mappings, weights, and missing-data policy before its first ranking run. Missing required inputs block the run.
- Approve report audiences/field allowlists and configure export expiry before generating reports or exports.
- Approve jurisdiction/hosting and live-data encryption/retention/deletion safeguards before any real personal data is used.

## Scope and approval gates

This policy baseline adds no MVP features. Do not implement until the owner-approved Specs are merged and the next slice is explicitly authorized. The approved matrix is fully documented in SPEC-01; unresolved per-pilot settings and live-data safeguards remain gate conditions in the relevant Specs.
