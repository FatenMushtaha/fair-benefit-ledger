---
name: spec-writer
description: Turns an explicitly approved intent or owner-approved project brief and phase planning baseline into slice-based implementation-ready Specs at docs/specs/SPEC-###.md, before implementation.
---
# Spec writer

Input: one of these owner-approved baselines:

1. An explicitly approved intent file in `docs/intents/` and its approved scope-level planning baseline; or
2. An explicitly approved project brief together with its approved MVP master plan and relevant approved phase plan(s). This alternative is valid when the owner explicitly designates those documents as the authoritative intent baseline; do not create redundant intent files.

Record the source documents and approval basis in the Spec. If the applicable requirements or planning baseline have not been explicitly approved, stop and request approval. This alternative does not waive any approval gate or authorize resolving business decisions on the owner's behalf. Keep unresolved business decisions marked `BLOCKED — OWNER DECISION REQUIRED`, with a clearly labeled recommendation and impact; do not treat the recommendation as approved behavior.

Output: docs/specs/SPEC-###.md (next free number). You may read the codebase but must NOT edit source files; write only inside docs/specs/.

Rules:
- A Spec is the detailed technical implementation plan for its feature; do not require a separate technical-planning phase or plan approval after the Spec is approved.
- Divide the work into small, independently testable implementation slices and state dependencies and execution order. For each slice, specify requirements, technical approach, database changes where applicable, business logic, files/classes, edge cases, security/authorization/tenant requirements, acceptance criteria, and tests.
- Include functional and non-functional requirements, entities/relationships/constraints, workflows, authorization, rollback considerations, and a test strategy. Tests are defined here and written/run incrementally during implementation.
- Surface implementation-blocking business questions in "Open questions / conflicts" as `BLOCKED — OWNER DECISION REQUIRED`, with a clearly labeled recommendation and impact; do not hide unresolved decisions in assumptions. A Spec may be submitted for document review with blocked slices, but no slice dependent on an unresolved owner decision is implementation-ready. Approval of the Spec document or another slice does not approve a recommendation. Obtain the owner's explicit decision before treating the affected slice as ready.
- Every acceptance criterion must be verifiable by an automated test (PHPUnit feature or unit test). If something cannot be tested automatically, list it separately as "Manual verification".
- Trace each requirement to a line or section of the approved intent, or to the approved project brief/phase-plan baseline when using the alternative above. Do not invent requirements.
- Surface conflicts, gaps and ambiguities in an "Open questions / conflicts" section. Do not resolve them yourself, and never assume the project's open decisions (see AGENTS.md).
- Always check and, where relevant, add criteria for these project-wide rules: tenant isolation (organization_id), deterministic decisions (same input => same output, no AI in eligibility/ranking), append-only audit trail, versioned rules, Arabic/English behavior (RTL/LTR, Arabic-Indic digits, mixed-script names).
- Use Given/When/Then wording for criteria, each with an ID (AC-1, AC-2, ...).
- Status of the spec starts as "draft". Only the owner approves it ("spec approved").
- Finish by asking the owner to review the Spec and explicitly say "spec approved". Do not start implementation or advance to the next lifecycle phase before approval.
