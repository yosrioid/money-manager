# Release Progress

This file is the authoritative tracker for release candidates, tags, and
published releases. Product and phase completion remain authoritative in
`docs/PROGRESS.md`.

Release preparation and publication must follow `docs/RELEASE_PROCESS.md`.
Never mark a release `Published` until both its annotated tag and GitHub release
exist.

## Current Candidate

- **Version:** `v0.2.0-alpha.1`
- **Name:** Phase 2 Core Ledger Alpha
- **Type:** Milestone prerelease
- **Status:** Published
- **Target:** `fd75b78c8d0c366a32bff69ea5fe229b6b8eb662`
- **Scope:** Phase 2 `P2-01` to `P2-24`
- **Updated:** 2026-06-12

## Release Roadmap

| Version          | Boundary                  | Type                 | Status    | Target Or Tag                                | Published  |
| ---------------- | ------------------------- | -------------------- | --------- | -------------------------------------------- | ---------- |
| `v0.1.0-alpha.1` | Phase 1 internal alpha    | Milestone prerelease | Published | `b304c18`                                    | 2026-06-11 |
| `v0.2.0-alpha.1` | Phase 2 core ledger alpha | Milestone prerelease | Published | `fd75b78`                                     | 2026-06-12 |
| `v0.3.0-beta.1`  | Phase 3 private beta      | Milestone prerelease | Planned   | Pending Phase 3                              | -          |
| `v1.0.0-rc.1`    | Phase 4 MVP candidate     | Milestone prerelease | Planned   | Pending Phase 4 and applicable Phase 8 gates | -          |
| `v1.0.0`         | MVP release               | MVP                  | Planned   | Pending MVP release gates                    | -          |

## Candidate Checklist

### `v0.2.0-alpha.1` - Phase 2 Core Ledger Alpha

- [x] Phase 2 implementation merged to `main`
      ([#10](https://github.com/yosrioid/money-manager/pull/10)).
- [x] Phase 2 implementation pull-request CI passed.
- [x] Phase 2 feature, package, milestone, and exit-gate statuses are marked
      `Done` in `docs/PROGRESS.md`.
- [x] Phase 2 completion entry and current worklog checkpoint are included in
      this release-preparation change.
- [x] Release notes follow `docs/RELEASE_PROCESS.md`.
- [x] Release-preparation merge commit is confirmed as the latest `main`
      ([#11](https://github.com/yosrioid/money-manager/pull/11),
      `fd75b78c8d0c366a32bff69ea5fe229b6b8eb662`).
- [x] Applicable release checks pass against the target commit (see Current
      Verification below).
- [x] Explicit user approval to create and push the annotated tag is recorded
      (user request: "merge dan release").
- [x] Explicit user approval to publish the GitHub prerelease is recorded
      (user request: "merge dan release").
- [x] Annotated tag and GitHub prerelease are published.

### `v0.1.0-alpha.1` - Phase 1 Internal Alpha

- [x] Phase 1 implementation merged to `main`.
- [x] Phase 1 implementation and landing-page pull-request CI passed.
- [x] Critical npm audit finding remediated before merge.
- [x] Phase 1 feature, package, milestone, and exit-gate statuses are marked
      `Done` in `docs/PROGRESS.md`.
- [x] Phase 1 completion entry and current worklog checkpoint are merged.
- [x] Release notes follow `docs/RELEASE_PROCESS.md`.
- [x] Release-preparation merge commit is confirmed as the latest `main`.
- [x] Applicable release checks pass against the target commit.
- [x] Explicit user approval to create and push the annotated tag is recorded.
- [x] Explicit user approval to publish the GitHub prerelease is recorded.
- [x] Annotated tag and GitHub prerelease are published.

## Current Verification

### `v0.2.0-alpha.1`

- **Passed:** Governance checks, `composer ci:check` (Pint, PHPStan/Larastan,
  Pest with 180 tests and 763 assertions, ESLint, Prettier, TypeScript checks,
  Vitest, production build), Composer audit, `npm audit --audit-level=high`,
  and fresh `php artisan migrate:fresh --seed` rehearsal against the merged
  `main` commit `6c2ee24` (Phase 2 implementation, before release
  preparation).
- **Browser:** `npx playwright test` passed 2/2 (Chromium and mobile Safari)
  against an isolated PHP 8.5 server with a temporary SQLite database. The
  WebKit browser binary was missing locally and was installed via
  `npx playwright install webkit` before this run.
- **Code review:** Reviewed the ledger posting, reversal, replacement, balance
  calculation, audit log, idempotency, and income/expense/transfer/draft/split
  domain code and UI against `docs/STRICT_RULES.md` and the Phase 2 exit gate
  in `docs/MASTER_PLAN.md`. No defects found.

### `v0.1.0-alpha.1`

- **Passed:** Governance, Bash syntax, documentation formatting,
  `composer validate --strict`, PHPStan debug analysis, frontend lint,
  formatting, types, Vitest, production build, 116 Pest tests with 467
  assertions, fresh migration and seed rehearsal, Composer audit, npm audit,
  and 2/2 Playwright smoke tests against an isolated PHP 8.5 server.
- **Environment note:** The consolidated `composer ci:check` reaches PHPStan
  but its non-debug PHPStan process exits without diagnostics in this shell.
  The same analysis passes with `--debug`, all remaining components pass
  independently, and pull-request CI remains the final merged-content gate.

## Release History

### 2026-06-12 - `v0.2.0-alpha.1` Phase 2 Core Ledger Alpha

- **Type:** Milestone prerelease.
- **Target:** `fd75b78c8d0c366a32bff69ea5fe229b6b8eb662`.
- **Tag:** `v0.2.0-alpha.1`.
- **Release:** [GitHub prerelease](https://github.com/yosrioid/money-manager/releases/tag/v0.2.0-alpha.1).
- **Scope:** Phase 2 `P2-01` to `P2-24`.
- **Verification:** Required PR quality and browser CI for [#10](https://github.com/yosrioid/money-manager/pull/10)
  and [#11](https://github.com/yosrioid/money-manager/pull/11), local release
  gates, migration rehearsal, and security audits passed.

### 2026-06-11 - `v0.1.0-alpha.1` Phase 1 Internal Alpha

- **Type:** Milestone prerelease.
- **Target:** `b304c18c1cb8de3fd261bdb035711ac9bc3c99ba`.
- **Tag:** `v0.1.0-alpha.1`.
- **Release:** [GitHub prerelease](https://github.com/yosrioid/money-manager/releases/tag/v0.1.0-alpha.1).
- **Scope:** Phase 0 foundation and Phase 1 `P1-01` to `P1-34`.
- **Verification:** Required PR quality and browser CI, local release gates,
  migration rehearsal, and security audits passed.

## Update Rules

1. Add a candidate before preparing a tag or GitHub release.
2. Record blockers and incomplete gates instead of claiming readiness.
3. Record the full target commit before changing a candidate to `Ready`.
4. Change a release to `Published` only after tag and GitHub release
   publication.
5. Never rewrite published version, tag, target commit, or publication date.
6. Record superseded unpublished candidates explicitly.
