---
name: intent-interview
description: Interviews the project owner about ONE new feature or change and writes an intent file in her own words. Use when she describes a new feature, problem or change request before any spec exists.
---
# Intent interview

Scope: one feature or change at a time. This is NOT a whole-project requirements interview; the project is already described in docs/project-brief.md.

1. Read AGENTS.md, docs/project-brief.md, docs/progress.md, and docs/glossary.md if it exists. Do not ask about anything already decided there (stack, architecture rules). Never assume answers to the "Open decisions" listed in AGENTS.md; if a question depends on one, record it under Open questions.
2. Conduct the interview in the language the owner writes in (Arabic if she writes Arabic). Ask ONE question at a time and wait for the answer. If an answer is vague, ask one targeted follow-up. Stop after at most 8 questions.
3. Cover, as relevant: who uses this feature, the problem it solves, the normal flow step by step, edge cases and exceptions, what a reviewer must be able to see or explain, what is explicitly out of scope, and which MVP item from docs/project-brief.md it belongs to.
4. Write docs/intents/<slug>.md using the template in this skill folder (template.md), in the owner's own words. Do not add requirements she did not state.
5. End the file with "Open questions" and "Status: draft". NEVER mark it approved; only the owner approves.
6. Finish by telling her where the file is and asking her to review it and say "intent approved" when ready. Do not start a spec or any code.
