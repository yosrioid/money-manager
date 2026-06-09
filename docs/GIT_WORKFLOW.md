# Git Workflow

## Branching Model

Use short-lived branches created from an up-to-date `main`.

Branch format:

```text
<type>/<issue-or-scope>-<short-description>
```

Examples:

```text
feat/ledger-post-transactions
fix/budget-month-boundary
docs/engineering-standards
chore/upgrade-vue
```

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

- Write summaries in English.
- Use imperative mood: `add`, `fix`, `prevent`, not `added` or `fixes`.
- Keep the summary concise, ideally at most 72 characters.
- Keep one logical concern per commit.
- Do not commit secrets, `.env`, generated build output, or unrelated changes.
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

1. Review `git diff` and remove accidental changes.
2. Run relevant tests and quality checks.
3. Update documentation when behavior or architecture changes.
4. Update `docs/PROGRESS.md` when a feature or milestone is completed.
5. Stage only files belonging to the commit.

Recommended inspection:

```bash
git status --short
git diff
git diff --cached
```

## Push Workflow

1. Rebase the branch on the latest `main`.
2. Resolve conflicts and rerun relevant tests.
3. Push the branch.
4. Open a pull request immediately after the branch is ready for review.

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

## Screenshots

Include screenshots or recordings for UI changes.

## Related Issues

Closes #...
```

## Pull Request Size And Review

- Prefer pull requests below roughly 400 changed lines, excluding generated
  files and migrations.
- Split large features into independently safe vertical slices.
- Require at least one review before merging.
- Resolve all review threads before merging.
- CI must pass before merging.
- Use squash merge unless preserving separate commits adds clear value.
- Delete merged branches.

## Hotfixes

Use `fix/<scope>-<description>` from `main`. Add a regression test, keep the
change minimal, and update progress or release notes when the fix changes known
project status.

