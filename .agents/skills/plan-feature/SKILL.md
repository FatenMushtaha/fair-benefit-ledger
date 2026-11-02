---
name: plan-feature
description: Writes an implementation plan from an approved spec at docs/plans/PLAN-###.md, preferring the smallest change. Use after the owner says "spec approved" and before writing any code.
---
# Plan feature

Input: an approved spec in docs/specs/. If it is not approved, stop and ask.

Output: docs/plans/PLAN-###.md. Write only inside docs/plans/. Do NOT edit source code, migrations or config while planning.

The plan must include:
1. Summary of the smallest change that satisfies the spec.
2. Reuse first: existing classes, Blade/Livewire components, queue config, and Laravel features before adding anything new. Do not propose a package outside the approved list in AGENTS.md; if one seems necessary, flag it as a question for the owner.
3. Schema changes: tables, key columns, indexes, organization_id handling. Any schema change needs the owner's approval before implementation.
4. Files/classes to create or change, following the architecture rules in AGENTS.md (domain logic in plain PHP classes under app/Services or app/Actions, with no HTTP/Livewire/AI dependency).
5. Test list mapped to acceptance criteria (AC-x -> test name). Follow the testing-best-practices skill.
6. Bilingual/RTL and tenant-isolation checklist (see the rtl-bilingual-ui and tenant-isolation skills).
7. Rollback note: how to undo this feature safely (migration rollback, data considerations).
8. Risks and open questions.

Finish by asking: "Do you want me to save this as a commit/PR?" and wait. Do not start implementation until the owner says "plan approved".
