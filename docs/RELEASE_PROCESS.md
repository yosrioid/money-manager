# Release Process

## Purpose

This document defines the mandatory versioning, preparation, release-note, tag,
and publication format for Money Manager.

Whenever a user requests a release, tag, version, prerelease, or release
preparation, follow this document together with `docs/STRICT_RULES.md`,
`docs/MASTER_PLAN.md`, `docs/GIT_WORKFLOW.md`, and
`docs/RELEASE_PROGRESS.md`.

## Release Types

| Type                 | Purpose                                                             | Example          |
| -------------------- | ------------------------------------------------------------------- | ---------------- |
| Milestone prerelease | Internal validation after a completed phase or major milestone      | `v0.1.0-alpha.1` |
| MVP release          | First production release after Phase 4 and applicable Phase 8 gates | `v1.0.0`         |
| Parity release       | Approved parity scope and parity audit complete                     | `v2.0.0`         |
| Extended release     | Approved extended scope and operational gates complete              | `v3.0.0`         |
| Patch release        | Backward-compatible fixes to a published release                    | `v1.0.1`         |

Milestone prereleases are optional and do not replace the release gates in
`docs/MASTER_PLAN.md`.

## Versioning And Tags

- Use Semantic Versioning.
- Prefix Git tags with `v`.
- Use prerelease identifiers such as `alpha.1`, `beta.1`, or `rc.1`.
- Create annotated tags only from an up-to-date `main` commit.
- Never move, overwrite, or recreate a published tag.
- Never tag a dirty worktree, unmerged branch, or commit with failing required
  checks.
- A version is not published until its tag and GitHub release both exist.

Recommended milestone mapping:

| Milestone              | Suggested Version |
| ---------------------- | ----------------- |
| Phase 1 internal alpha | `v0.1.0-alpha.1`  |
| Phase 2 internal alpha | `v0.2.0-alpha.1`  |
| Phase 3 private beta   | `v0.3.0-beta.1`   |
| Phase 4 MVP candidate  | `v1.0.0-rc.1`     |

## Release Status

Use these statuses in `docs/RELEASE_PROGRESS.md`:

- `Planned`: target and intended scope are recorded.
- `Preparing`: release documentation or required gates are being completed.
- `Ready`: target commit and gates are confirmed; explicit publication
  approval is still required.
- `Published`: annotated tag and GitHub release are published.
- `Blocked`: a named gate or decision prevents publication.
- `Superseded`: replaced by another release candidate without publication.

## Required Release Workflow

1. Confirm the requested release type, version, and approved scope.
2. Read `docs/RELEASE_PROGRESS.md` and update the candidate to `Preparing`.
3. Confirm all included features and required phase or release gates are
   complete in `docs/PROGRESS.md`.
4. Confirm the target is an up-to-date `main` commit with required CI checks
   passing.
5. Run all applicable quality, security, migration, browser, and governance
   checks.
6. Prepare release notes using the mandatory format below.
7. Record the target commit, verification evidence, known limitations, and
   approval state in `docs/RELEASE_PROGRESS.md`.
8. Merge the release-preparation documentation through a reviewed pull request.
9. Obtain explicit user approval before creating or pushing the tag.
10. Obtain explicit user approval before publishing the GitHub release.
11. Create and push the annotated tag from the confirmed `main` commit.
12. Publish the GitHub release using the approved release notes.
13. Update `docs/RELEASE_PROGRESS.md` to `Published` through a follow-up pull
    request.

Tag creation and GitHub release publication are separate approval-controlled
actions. Approval to prepare or document a release does not authorize either
action.

## Mandatory Release Note Format

Use this exact section order. Remove a section only when it is explicitly
marked optional.

```markdown
# <version> - <release name>

## Release Summary

- **Release type:** Milestone prerelease | MVP | Parity | Extended | Patch
- **Status:** Prerelease | Stable
- **Published:** YYYY-MM-DD
- **Target commit:** `<full commit SHA>`
- **Scope:** Phases, delivery packages, and feature IDs

## Highlights

- User-visible outcome.
- Important engineering or operational outcome.

## Included Scope

- `Pn-nn` or `F-nnn`: capability and outcome.

## Financial Integrity

- Ledger, money, workspace-isolation, authorization, and audit guarantees.
- State `Not applicable` only when the release contains no financial behavior.

## Security

- Security fixes, reviews, audits, and unresolved risks.

## Verification

- Required CI checks and local quality gates with results.
- Migration or rollback rehearsal results when applicable.

## Database And Upgrade Notes

- Required migrations, configuration changes, compatibility notes, and operator
  actions.
- State `None` when no action is required.

## Known Limitations

- Explicitly unresolved limitations, deferred scope, or follow-up work.
- State `None` when there are no known limitations.

## Rollback

- Supported rollback approach and data-safety constraints.

## Documentation

- Progress, worklog, release tracker, and relevant operator documentation.

## Pull Requests

- `#123` - Description.
```

## Release Readiness Checklist

- [ ] Version, type, target commit, and scope are recorded.
- [ ] Included feature and phase statuses are accurate in `docs/PROGRESS.md`.
- [ ] Required CI and local gates pass.
- [ ] Security and dependency audits pass or blockers are documented.
- [ ] Financial integrity and workspace isolation implications are reviewed.
- [ ] Database, upgrade, and rollback notes are complete.
- [ ] Known limitations are explicit.
- [ ] Release notes follow the mandatory format.
- [ ] Release preparation is merged to `main`.
- [ ] Explicit tag approval is recorded.
- [ ] Explicit GitHub release approval is recorded.
- [ ] Tag and GitHub release point to the confirmed `main` commit.
