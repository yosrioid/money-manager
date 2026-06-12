# Git Workflow

## Branching Model

Use short-lived branches created from an up-to-date `main`.

`main` is protected by workflow policy:

- Never commit directly to `main`.
- Never push directly to `main`.
- All changes must be committed on a short-lived non-`main` branch.
- Changes may reach `main` only through a reviewed pull request.

Branch format:

```text
<type>/<issue-or-scope>-<short-description>
```

Examples:

```text
feat/phase-3
fix/budget-month-boundary
docs/engineering-standards
chore/upgrade-vue
```

Product implementation work uses one branch per phase (e.g. `feat/phase-3`).
Each feature ID in the phase is implemented as its own commit on that branch.
Engineering-only, bug-fix, docs, and chore work may still use a short-lived
scope-specific branch as before.

Pull requests must identify all approved feature IDs from
`docs/FEATURE_CATALOG.md` that the PR covers. Include the IDs in the PR body.

Supported types:

- `feat`: new user-facing behavior
- `fix`: bug fix
- `refactor`: internal change without intended behavior change
- `docs`: documentation only
- `test`: tests only
- `chore`: tooling, dependency, or maintenance work
- `perf`: performance improvement
- `ci`: continuous integration changes

## Commit Standard

Use Conventional Commits:

```text
<type>(<scope>): <imperative summary>
```

Examples:

```text
feat(ledger): post balanced expense transactions
fix(budgets): respect workspace timezone at month boundary
test(accounts): cover cross-workspace access denial
docs(progress): mark account management complete
```

Rules:

- Create a commit only after the user explicitly requests it.
- Never create a commit while checked out on `main`.
- Write summaries in English.
- Use imperative mood: `add`, `fix`, `prevent`, not `added` or `fixes`.
- Keep the summary concise, ideally at most 72 characters.
- Keep one logical concern per commit.
- Do not commit secrets, `.env`, generated build output, or unrelated changes.
- Commit authorship must represent the human developer only.
- Never add Codex, Claude, ChatGPT, an AI assistant, or another automated tool
  as an author, co-author, signer, or attribution.
- Never add `Co-authored-by`, `Generated-by`, or similar AI attribution
  trailers or messages.
- Explain the reason and important trade-offs in the body when the summary is
  insufficient.
- Mark breaking changes with `BREAKING CHANGE:` in the footer.

Commit body example:

```text
feat(ledger): post balanced expense transactions

Create the transaction and its ledger entries atomically so account balances
cannot observe a partially posted transaction.

Refs: #42
```

## Before Committing

1. Confirm the user explicitly requested a commit.
2. Confirm the current branch is not `main`.
3. Review `git diff` and remove accidental changes.
4. Run relevant tests and quality checks.
5. Update documentation when behavior or architecture changes.
6. Update `docs/PROGRESS.md` when a feature or milestone is completed.
7. Stage only files belonging to the commit.

Recommended inspection:

```bash
git status --short
git diff
git diff --cached
```

## Push Workflow

Push only after the user explicitly requests it.

1. Confirm the current branch is not `main`.
2. Rebase the branch on the latest `main`.
3. Resolve conflicts and rerun relevant tests.
4. Push the non-`main` branch.
5. Open a pull request once the branch's phase (or scope, for non-phase
   branches) is ready for review. Pushing a phase branch mid-phase does not by
   itself open or update a pull request.

```bash
git fetch origin
git rebase origin/main
git push -u origin feat/ledger-post-transactions
```

After a branch is shared, prefer:

```bash
git push --force-with-lease
```

Never use plain `--force` on shared branches.
Never push directly to `main`, including with `--force-with-lease`.

## Pull Request Format

Title must follow Conventional Commit style:

```text
feat(ledger): post income, expense, and transfer transactions
```

Pull request body:

```markdown
## Summary

- Explain the user-visible or engineering outcome.
- Explain important implementation choices.

## Feature IDs

- `Pn-nn`, `Pn-nn`, ... (all feature IDs delivered as commits on this phase
  branch)

## Changes

- List focused changes.

## Verification

- [ ] Relevant backend tests pass
- [ ] Relevant frontend tests pass
- [ ] Static analysis and formatting pass
- [ ] Production build passes

## Financial Integrity

- [ ] Ledger entries remain balanced
- [ ] Workspace isolation is covered
- [ ] Concurrent writes are handled where applicable
- [ ] No financial history is destructively modified

## Documentation

- [ ] Documentation updated
- [ ] `docs/PROGRESS.md` updated when applicable
- [ ] `docs/WORKLOG.md` contains the latest applicable checkpoint
- [ ] Scope and phase mapping remain consistent with `docs/MASTER_PLAN.md`

## Screenshots

Include screenshots or recordings for UI changes.

## Related Issues

Closes #...
```

## Pull Request Size And Review

- Never merge a pull request without the user's explicit request.
- One pull request per phase, opened once the phase's exit gate is met; review
  it commit by commit, since each feature ID is its own commit (vertical
  slice) on the phase branch.
- Require at least one review before merging.
- Resolve all review threads before merging.
- CI must pass before merging.
- Use squash merge unless preserving separate commits adds clear value.
- Delete merged branches.

## Release Tags And Publication

- Follow `docs/RELEASE_PROCESS.md` for every release, tag, version, or
  prerelease request.
- Record release state in `docs/RELEASE_PROGRESS.md`.
- Create annotated tags only from the confirmed up-to-date `main` commit.
- Never create, push, move, or delete a tag without explicit user approval.
- Never publish, modify, or remove a GitHub release without explicit user
  approval.
- A release-preparation commit or pull request does not authorize tag creation
  or release publication.

## Hotfixes

Use `fix/<scope>-<description>` from `main`. Add a regression test, keep the
change minimal, and update progress or release notes when the fix changes known
project status.
