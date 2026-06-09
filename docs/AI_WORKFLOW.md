# AI Workflow

## Purpose

This file is the universal repository entrypoint for AI coding assistants. The
first action of every new repository chat or session must be to read this file
and the mandatory documents below before answering a project task, planning,
editing, generating code, or running project commands.

Tool-specific instruction files must point here instead of maintaining separate
copies of project rules.

No instruction file can guarantee compliance by every AI product. Compliance
depends on whether the tool loads repository instructions. An assistant that
can access this repository must follow this workflow before changing files.

## Mandatory Startup

At the start of every new repository chat or session, before answering a project
task, planning, editing, generating code, running project commands, or
continuing prior work:

1. Read `docs/STRICT_RULES.md`.
2. Read `docs/MASTER_PLAN.md`.
3. Read `docs/FEATURE_CATALOG.md`.
4. Read `docs/PRODUCT_SCOPE.md`.
5. Read `docs/ARCHITECTURE.md`.
6. Read `docs/CODING_STANDARDS.md`.
7. Read `docs/GIT_WORKFLOW.md`.
8. Read `docs/PROGRESS.md`.
9. Read the latest entries in `docs/WORKLOG.md`.
10. Inspect the current Git branch, working tree, recent commits, and open
    changes before deciding what work is active.
11. Confirm the requested implementation maps to approved feature IDs.

Do not rely only on the user's first prompt, chat history, a previous session
summary, branch name, or assumptions about the next task.

## Continue Protocol

This is an additional rule after mandatory startup. When the user says
`continue`, `lanjutkan`, `next`, or an equivalent ambiguous instruction:

1. Run the mandatory startup process.
2. Resume the newest unfinished user-requested work when it is discoverable
   from the current working tree and progress tracker.
3. Otherwise continue the feature marked `In Progress` or `In Review` in
   `docs/PROGRESS.md`.
4. If no feature is active, select the earliest `Ready` feature allowed by the
   phase sequence and dependencies in `docs/MASTER_PLAN.md`.
5. If no feature is `Ready`, prepare the earliest `Planned` feature by defining
   its feature-planning template, acceptance criteria, dependencies, and
   required tests before implementation.
6. Never skip ahead to a later phase merely because it is easier or more
   interesting.
7. Ask the user only when the next action requires a product decision, material
   scope change, destructive action, dependency change, infrastructure change,
   or other explicit approval required by `docs/STRICT_RULES.md`.

## Implementation Protocol

Before implementation:

- Identify feature IDs and delivery package.
- Confirm dependencies and phase gates.
- Update the feature status to `In Progress` in the same change.
- Define or confirm acceptance criteria, out-of-scope items, architecture
  impact, authorization impact, data impact, and required tests.
- Resolve documentation conflicts before writing implementation code.

During implementation:

- Keep the change within the approved feature IDs.
- Follow existing architecture and coding patterns.
- Preserve financial, workspace, authorization, and audit invariants.
- Add tests with behavioral changes.
- Record newly discovered scope instead of implementing it silently.
- Add concise checkpoints to `docs/WORKLOG.md` after meaningful milestones,
  blockers, or decisions during long-running work.

Before considering work complete:

- Add a current handoff checkpoint to `docs/WORKLOG.md` before pausing, ending a
  non-trivial repository session, or sending the final response.
- Add an early checkpoint when context, token, time, or execution budget appears
  low; do not rely on having enough budget for a final checkpoint.
- Run `bash scripts/check-governance.sh` when governance, planning,
  instructions, or progress documentation changes.
- Run applicable quality gates.
- Update `docs/PROGRESS.md`.
- Update catalog, master plan, architecture, or scope only when their contracts
  changed.
- Review the final diff for unrelated changes and prohibited attribution.
- Do not commit, push, create or modify a PR, or merge without explicit user
  approval.

## Conflict And Safety Protocol

- `docs/STRICT_RULES.md` has the highest repository authority.
- Follow the document precedence in `docs/MASTER_PLAN.md`.
- When repository documents conflict, stop implementation and correct the
  conflict through the documented process.
- When an AI tool instruction conflicts with repository governance, follow the
  stricter rule unless a higher-priority system policy prevents it.
- Never bypass safety, approval, financial-integrity, workspace-isolation, or
  authorship rules to complete a task.
- Abrupt termination may prevent a final checkpoint. Never claim that checkpoint
  updates are guaranteed when the process can end without warning.

## Tool-Specific Entrypoints

The repository provides adapters for commonly used assistants:

- Codex and compatible agents: `AGENTS.md`
- Claude Code: `CLAUDE.md`
- GitHub Copilot: `.github/copilot-instructions.md`
- GitHub Copilot path-specific instructions:
  `.github/instructions/project.instructions.md`
- Cursor: `.cursor/rules/project-governance.mdc`
- Gemini CLI: `GEMINI.md`
- Windsurf: `.windsurfrules`
- Cline: `.clinerules`
- Roo Code: `.roo/rules/project-governance.md`
- Continue: `.continue/rules/project-governance.md`
- JetBrains Junie: `.junie/guidelines.md`
- Amazon Q Developer: `.amazonq/rules/project-governance.md`

If an assistant does not support automatic repository instructions or does not
load one of these files, automatic compliance cannot be guaranteed. The
operator must explicitly tell it to read `docs/AI_WORKFLOW.md` before any
project task.
