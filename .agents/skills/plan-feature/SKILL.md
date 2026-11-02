---
name: plan-feature
description: Optional planning aid for reviewing/refining a high-level plan or preparing a draft Spec. It is not a required phase after Spec approval.
---
# Plan feature

Use only when it helps during initial planning or Spec preparation. The owner must have approved the feature's scope-level planning baseline first. It may review a draft Spec or phase plan; it must not be used as a separate stage after final Spec approval.

Output: a draft refinement to be incorporated into the existing high-level plan or the Spec being prepared. Do not create a second detailed implementation plan, a post-Spec plan artifact, or a separate plan-approval gate. Write only planning/specification documentation. Do NOT edit source code, migrations or config while planning. Do not change owner-approved product scope.

The plan must include:
1. Summary of the smallest change that satisfies the spec.
2. Reuse first: existing classes, Blade/Livewire components, queue config, and Laravel features before adding anything new. Do not propose a package outside the approved list in AGENTS.md; if one seems necessary, flag it as a question for the owner.
3. Schema changes: tables, key columns, indexes, organization_id handling. Any schema change needs the owner's approval before implementation.
4. Files/classes to create or change, following the architecture rules in AGENTS.md (domain logic in plain PHP classes under app/Services or app/Actions, with no HTTP/Livewire/AI dependency).
5. Test list mapped to acceptance criteria (AC-x -> test name). Follow the testing-best-practices skill.
6. Bilingual/RTL and tenant-isolation checklist (see the rtl-bilingual-ui and tenant-isolation skills).
7. Rollback note: how to undo this feature safely (migration rollback, data considerations).
8. Risks and open questions.

Fold applicable refinements into the high-level plan or draft Spec and present them at that artifact's existing approval gate. This skill does not approve scope, approve a Spec, or authorize implementation. Implementation remains blocked until the owner explicitly says "spec approved".
