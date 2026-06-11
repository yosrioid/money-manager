# Release Progress

This file is the authoritative tracker for release candidates, tags, and
published releases. Product and phase completion remain authoritative in
`docs/PROGRESS.md`.

Release preparation and publication must follow `docs/RELEASE_PROCESS.md`.
Never mark a release `Published` until both its annotated tag and GitHub release
exist.

## Current Candidate

- **Version:** `v0.1.0-alpha.1`
- **Name:** Phase 1 Internal Alpha
- **Type:** Milestone prerelease
- **Status:** Planned
- **Target:** Latest approved `main` commit after Phase 1 closure documentation
- **Scope:** Phase 0 foundation and Phase 1 `P1-01` to `P1-34`
- **Updated:** 2026-06-11

## Release Roadmap

| Version          | Boundary                  | Type                 | Status  | Target Or Tag                                | Published |
| ---------------- | ------------------------- | -------------------- | ------- | -------------------------------------------- | --------- |
| `v0.1.0-alpha.1` | Phase 1 internal alpha    | Milestone prerelease | Planned | Pending Phase 1 closure documentation        | -         |
| `v0.2.0-alpha.1` | Phase 2 core ledger alpha | Milestone prerelease | Planned | Pending Phase 2                              | -         |
| `v0.3.0-beta.1`  | Phase 3 private beta      | Milestone prerelease | Planned | Pending Phase 3                              | -         |
| `v1.0.0-rc.1`    | Phase 4 MVP candidate     | Milestone prerelease | Planned | Pending Phase 4 and applicable Phase 8 gates | -         |
| `v1.0.0`         | MVP release               | MVP                  | Planned | Pending MVP release gates                    | -         |

## Candidate Checklist

### `v0.1.0-alpha.1` - Phase 1 Internal Alpha

- [x] Phase 1 implementation merged to `main`.
- [x] Phase 1 implementation and landing-page pull-request CI passed.
- [x] Critical npm audit finding remediated before merge.
- [ ] Phase 1 feature, package, milestone, and exit-gate statuses are marked
      `Done` in `docs/PROGRESS.md`.
- [ ] Phase 1 completion entry and current worklog checkpoint are merged.
- [ ] Release notes follow `docs/RELEASE_PROCESS.md`.
- [ ] Target `main` commit is recorded after release preparation is merged.
- [ ] Applicable release checks pass against the target commit.
- [ ] Explicit user approval to create and push the annotated tag is recorded.
- [ ] Explicit user approval to publish the GitHub prerelease is recorded.
- [ ] Annotated tag and GitHub prerelease are published.

## Release History

No releases have been published.

## Update Rules

1. Add a candidate before preparing a tag or GitHub release.
2. Record blockers and incomplete gates instead of claiming readiness.
3. Record the full target commit before changing a candidate to `Ready`.
4. Change a release to `Published` only after tag and GitHub release
   publication.
5. Never rewrite published version, tag, target commit, or publication date.
6. Record superseded unpublished candidates explicitly.
