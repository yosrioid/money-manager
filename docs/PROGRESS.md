# Feature Progress

This file is the project implementation tracker and must be updated in the same
pull request that completes a feature or milestone.

Scope and phase definitions live in `docs/FEATURE_CATALOG.md` and
`docs/MASTER_PLAN.md`. This file records execution status and must not redefine
or silently change approved scope.

Daily progress, partial work, blockers, and session handoffs are recorded in
`docs/WORKLOG.md`. Do not use daily worklog entries as proof that a feature or
phase is complete.

## Status Values

- `Planned`: scope is known but implementation has not started.
- `Ready`: dependencies, acceptance criteria, and design decisions are clear.
- `In Progress`: active implementation exists on a branch.
- `In Review`: implementation is complete and a pull request is under review.
- `Blocked`: implementation cannot continue without an external decision or
  dependency.
- `Deferred`: implementation is intentionally postponed with a documented
  reason.
- `Done`: acceptance criteria, tests, review requirements, and documentation are
  complete.

## Current Milestone

- **Phase:** Phase 3 - Daily Use And Transaction Productivity
- **Milestone:** History and navigation through `P3-06`
- **Status:** In Progress
- **Updated:** 2026-06-12

## Phase Status

| Phase   | Name                                          | Status    | Exit Gate                             |
| ------- | --------------------------------------------- | --------- | ------------------------------------- |
| Phase 0 | Engineering Foundation                        | Done      | Foundation and governance established |
| Phase 1 | Identity, Workspace, And Financial Setup      | Done      | Secure financial structure ready      |
| Phase 2 | Ledger And Core Transactions                  | Done      | Balanced transaction engine ready     |
| Phase 3 | Daily Use And Transaction Productivity        | In Progress | Daily tracking experience ready     |
| Phase 4 | Budgets, Goals, Statistics, And Data Exchange | Planned   | MVP feature scope ready               |
| Phase 5 | Cards, Debt, Assets, And Multi-Currency       | Planned   | Advanced finance workflows ready      |
| Phase 6 | Automation, Attachments, Backup, And Restore  | Planned   | Automation and portability ready      |
| Phase 7 | Collaboration, Sync, PWA, And Customization   | Planned   | Extended platform ready               |
| Phase 8 | Production Hardening And Parity Acceptance    | Planned   | Applicable release gates passed       |

## Delivery Package Roadmap

These packages are planning containers. Detailed scope and acceptance summaries
are defined by the mapped IDs in `docs/FEATURE_CATALOG.md`.

| ID    | Feature                                         | Catalog Mapping    | Status    | Completed  |
| ----- | ----------------------------------------------- | ------------------ | --------- | ---------- |
| F-001 | Laravel and Vue project scaffolding             | `P0-01`, `P0-02`   | Done      | 2026-06-09 |
| F-002 | CI, linting, static analysis, and test baseline | `P0-03` to `P0-06` | Done      | 2026-06-09 |
| F-003 | Authentication and personal workspace           | `P1-01` to `P1-11` | Done      | 2026-06-11 |
| F-004 | Currencies and workspace preferences            | `P1-12` to `P1-18` | Done      | 2026-06-11 |
| F-005 | Account groups and accounts                     | `P1-19` to `P1-25` | Done      | 2026-06-11 |
| F-006 | Categories and reference data                   | `P1-26` to `P1-33` | Done      | 2026-06-11 |
| F-007 | Double-entry ledger foundation                  | `P2-01` to `P2-10` | Done      | 2026-06-12 |
| F-008 | Core transaction flows                          | `P2-11` to `P2-24` | Done      | 2026-06-12 |
| F-009 | Transaction history and navigation              | `P3-01` to `P3-12` | In Progress | -        |
| F-010 | Dashboard and fast-entry workflows              | `P3-13` to `P3-21` | Planned   | -          |
| F-011 | Budgets and goals                               | `P4-01` to `P4-10` | Planned   | -          |
| F-012 | Statistics, reports, import, and export         | `P4-11` to `P4-25` | Planned   | -          |
| F-013 | Cards, debt, and installments                   | `P5-01` to `P5-09` | Planned   | -          |
| F-014 | Assets and multi-currency                       | `P5-10` to `P5-16` | Planned   | -          |
| F-015 | Recurring and scheduled transactions            | `P6-01` to `P6-06` | Planned   | -          |
| F-016 | Attachments and receipts                        | `P6-07` to `P6-11` | Planned   | -          |
| F-017 | Backup and restore                              | `P6-12` to `P6-16` | Planned   | -          |
| F-018 | Collaboration and synchronization               | `P7-01` to `P7-06` | Planned   | -          |
| F-019 | PWA, desktop experience, and customization      | `P7-07` to `P7-16` | Planned   | -          |
| F-020 | Production hardening and parity acceptance      | `P8-01` to `P8-10` | Planned   | -          |
| F-021 | Public landing page                             | `P1-34`            | Done      | 2026-06-11 |

## Catalog Status Summary

Feature-level status is tracked independently from delivery-package status. A
package marked `In Progress` or `Done` does not automatically change every
mapped feature ID.

| Catalog Range      | Status   | Notes                                                        |
| ------------------ | -------- | ------------------------------------------------------------ |
| `P0-01` to `P0-06` | Done     | Engineering foundation and governance baseline               |
| `P1-01` to `P1-34` | Done     | Phase 1 exit gate passed                                     |
| `P2-01` to `P2-24` | Done     | Phase 2 exit gate passed                                     |
| `P3-01` to `P3-03` | In Progress | Daily transaction history, calendar view, and weekly view implemented on `feat/phase-3` |
| `P3-04` to `P8-10` | Planned  | See phase sequence and dependencies in `docs/MASTER_PLAN.md` |
| `D-01` to `D-06`   | Deferred | Requires explicit scope approval                             |

## Active Feature Overrides

Add a row whenever an individual feature leaves its catalog-range default.
Keep the row through completion so partial package progress remains visible.

| Feature ID         | Status    | Branch Or PR                                             | Notes                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| ------------------ | --------- | -------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `P1-01` to `P1-08` | Done      | `main`                                                   | Authentication and sensitive-action journeys implemented and tested                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| `P1-09` to `P1-11` | Done      | [#4](https://github.com/yosrioid/money-manager/pull/4)   | Personal workspace, active context, and isolation merged                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| `P1-12` to `P1-33` | Done      | [#5](https://github.com/yosrioid/money-manager/pull/5)   | Financial setup, reference data, opening balances, application lock, and tests merged                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| `P1-34`            | Done      | [#6](https://github.com/yosrioid/money-manager/pull/6)   | Branded public landing page merged                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `P2-01` to `P2-10` | Done      | [#10](https://github.com/yosrioid/money-manager/pull/10) | All 3 slices of `F-007` implemented: lifecycle status enum (Voided, Reversed, Replaced) with transition guard; posted-only balance calculation and balance-at-date; immutable generic `audit_logs` table, `AuditLog` model, `AuditAction` enum, and `RecordAuditLog` service; account-locking (`LockAccountsForPosting`), `ReverseTransaction`, and `ReplaceTransaction` domain actions wired to the audit log. Correction flows preserve category references and reject cross-workspace replacement references.                                                                                                        |
| `P2-11` to `P2-24` | Done      | [#10](https://github.com/yosrioid/money-manager/pull/10) | Core income, expense, transfer, metadata, split, calculator, draft, and duplication flows are implemented. Split entries remain balanced across one account leg and multiple category legs. Safe integer arithmetic expressions are supported without float or evaluation. Incomplete drafts and duplicated transactions persist structured input without ledger entries, can be resumed and updated, and become inactive after successful posting. Posting records an audit log, rejects archived financial references, and uses a workspace-scoped idempotency key that rejects conflicting payloads; request plus domain validation protect workspace, type, amount, currency, and metadata invariants. |
| `P3-01`            | In Progress | `feat/phase-3`                                        | Added a `transactions.index` page listing posted transactions (including reversed and replaced) grouped by workspace-local date, derived from `occurred_at` converted to the workspace timezone. Drafts are excluded (no `posted_at`). Paginated at 30 per page and linked from the sidebar. Implemented on the Phase 3 branch; a pull request will be opened once Phase 3 is complete.                                                                                                                                                                                                                          |
| `P3-02`            | In Progress | `feat/phase-3`                                        | Added a `transactions.calendar` page showing a month grid with per-day posted income, expense, net totals (per currency), and record counts, derived from `occurred_at` converted to the workspace timezone. Added a `TransactionViewNav` switcher shared between the daily and calendar views. Implemented on the Phase 3 branch; a pull request will be opened once Phase 3 is complete.                                                                                                                                                                                                                       |
| `P3-03`            | In Progress | `feat/phase-3`                                        | Added a `transactions.weekly` page showing a 7-day grid with per-day posted income, expense, net totals (per currency), record counts, and a weekly aggregate total, plus a `transactions.day` drill-down page listing transactions for a single workspace-local date. Extracted the shared aggregation logic into `SummarizeTransactionPeriod` (`forMonth`/`forWeek`) and shared transaction-row transformation into `TransactionController::transformTransaction()`. Added "Weekly" to `TransactionViewNav`. Implemented on the Phase 3 branch; a pull request will be opened once Phase 3 is complete.                                                                                                                                                                                                                       |

## Completed Features

### 2026-06-12 - Phase 2 Ledger And Core Transactions

- **Outcome:** Completed `F-007` and `F-008` (`P2-01` to `P2-24`), delivering
  the double-entry ledger foundation and the core income, expense, transfer,
  metadata, split, calculator, draft, and duplication transaction flows.
- **Key decisions:** Account balances derive only from posted ledger entries;
  posted entries and audit log records are immutable; corrections use
  auditable reversal and replacement transactions instead of mutating posted
  history; ledger posting uses workspace-scoped idempotency keys with row
  locking on affected accounts.
- **Tests:** Merged PR #10 CI passed. Post-merge verification on `main`
  (`6c2ee24`) ran `composer ci:check` with 180 Pest tests and 763 assertions,
  PHPStan/Larastan, Pint, ESLint, Prettier, TypeScript checks, Vitest, and a
  production build; Composer and npm (`--audit-level=high`) audits reported no
  advisories; `npx playwright test` passed 2/2 on Chromium and mobile Safari
  against an isolated PHP 8.5 server; `php artisan migrate:fresh --seed`
  rehearsed cleanly.
- **Documentation:** Updated `docs/PROGRESS.md`, `docs/WORKLOG.md`,
  `docs/RELEASE_PROGRESS.md`, and added the `v0.2.0-alpha.1` release-note
  draft.
- **Follow-up:** Prepare and publish the Phase 2 internal alpha
  (`v0.2.0-alpha.1`), then begin Phase 3 daily-use and transaction-productivity
  planning.
- **PR:** [#10](https://github.com/yosrioid/money-manager/pull/10).

### 2026-06-11 - Phase 1 Identity, Workspace, And Financial Setup

- **Outcome:** Completed `F-003` through `F-006` and `F-021`, covering secure
  identity journeys, personal workspace isolation, preferences, account and
  reference-data management, immutable ledger-backed opening balances,
  application lock, starter presets, and the branded public landing page.
- **Key decisions:** Phase 1 includes only the minimum ledger slice required for
  balanced opening balances. General transaction posting and reversal remain
  Phase 2.
- **Tests:** Merged pull requests passed required quality and browser CI.
  Phase 1 acceptance verification recorded 116 Pest tests with 467 assertions,
  PHPStan, frontend checks, production build, dependency audits, governance,
  and Playwright smoke tests.
- **Documentation:** Updated `docs/PROGRESS.md`, `docs/WORKLOG.md`,
  `docs/RELEASE_PROGRESS.md`, and the `v0.1.0-alpha.1` release notes.
- **Follow-up:** Publish the Phase 1 internal alpha, then begin Phase 2 ledger
  foundation planning.
- **PR:** [#4](https://github.com/yosrioid/money-manager/pull/4),
  [#5](https://github.com/yosrioid/money-manager/pull/5), and
  [#6](https://github.com/yosrioid/money-manager/pull/6).

### 2026-06-09 - F-002 CI, Linting, Static Analysis, And Test Baseline

- **Outcome:** Added a consolidated GitHub Actions workflow, Larastan level 6,
  Pint, ESLint, Prettier, Vitest, Playwright, and production-build quality
  gates.
- **Key decisions:** PHP tooling runs without parallel worker sockets so it
  remains deterministic in restricted and CI environments.
- **Tests:** `composer test`, `composer analyse`, `npm run lint:check`,
  `npm run format:check`, `npm run types:check`, `npm run test:unit`,
  `npm run test:e2e`, and `npm run build`.
- **Documentation:** Updated `README.md` and `docs/PROGRESS.md`.
- **Follow-up:** None.
- **PR:** [#2](https://github.com/yosrioid/money-manager/pull/2).

### 2026-06-09 - F-001 Laravel And Vue Project Scaffolding

- **Outcome:** Installed Laravel 13 with the official Vue starter kit,
  authentication foundation, passkeys, Inertia 3, Vue 3, TypeScript, Tailwind
  CSS 4, Horizon, ECharts, and Laravel Boost.
- **Key decisions:** PostgreSQL is the application database; Redis is reserved
  for Horizon and production queue/cache workloads.
- **Tests:** Laravel starter-kit tests and production frontend build passed.
- **Documentation:** Updated `README.md` and `docs/PROGRESS.md`.
- **Follow-up:** None.
- **PR:** Not available.

## Maintenance Log

### 2026-06-09 - Stabilize GitHub Actions CI

- **Outcome:** Consolidated three overlapping workflows into one CI workflow
  with quality and browser jobs.
- **Fixes:** Aligned the declared PHP minimum with the Symfony 8 lockfile,
  classified aliased frontend imports consistently, and isolated browser tests
  with SQLite and in-memory application services. CI generates ignored
  Wayfinder TypeScript modules before frontend type-checking and builds the
  Vite manifest before Laravel feature tests.
- **Tests:** `composer validate --strict`, `composer ci:check`, `npm run build`,
  and the Chromium Playwright smoke test passed.
- **Git:** Merged through [PR #2](https://github.com/yosrioid/money-manager/pull/2).

### 2026-06-09 - Establish Authoritative Delivery Plan

- **Outcome:** Defined the authoritative feature inventory, phase plan, release
  boundaries, delivery gates, and scope-change process.
- **Key decisions:** Every implementation must map to an approved feature ID;
  `docs/MASTER_PLAN.md` controls delivery sequence and
  `docs/FEATURE_CATALOG.md` controls scope.
- **Tests:** `bash scripts/check-governance.sh`, documentation consistency, and
  formatting checks.
- **Documentation:** Added `docs/MASTER_PLAN.md` and
  `docs/FEATURE_CATALOG.md`; added universal and tool-specific AI startup
  instructions; aligned all governing project documents.
- **Follow-up:** Begin Phase 1 with `P1-01` to `P1-11`.
- **PR:** Not available.

## Required Completion Entry

When a feature becomes `Done`, add an entry at the top of this section:

```markdown
### YYYY-MM-DD - F-XXX Feature Name

- **Outcome:** Describe the completed user or engineering capability.
- **Key decisions:** Record important implementation decisions.
- **Tests:** List the test suites or commands that passed.
- **Documentation:** List updated documents.
- **Follow-up:** List remaining non-blocking work, or `None`.
- **PR:** `#123` or link when available.
```

## Progress Update Rules

1. Change the roadmap row to `In Progress` when implementation begins.
2. Change it to `Done` only after the Definition of Done is satisfied.
3. Add the completion date and a completion entry.
4. Record follow-up work explicitly instead of silently leaving partial scope.
5. Add newly discovered features to the roadmap with a new stable ID.
6. Never rewrite historical completion entries except to correct factual
   mistakes.
7. Never redefine scope in this file; update `docs/FEATURE_CATALOG.md` through
   the master-plan scope-change process.
8. Update the phase status only when its master-plan exit gate changes state.
9. Use `docs/WORKLOG.md`, not this file, for checkpoints that do not change
   official feature, package, phase, or milestone status.

## Decision Log

### 2026-06-09 - Start With A Modular Monolith

Laravel and Vue will be deployed as a modular monolith. A separate API,
microservices, and offline-first synchronization are deferred until justified by
product requirements.

### 2026-06-09 - Use Double-Entry Bookkeeping

All financial transactions will use balanced ledger entries. Account balances
will be derived from posted entries rather than maintained as independent
mutable values.

### 2026-06-09 - Use Cataloged Scope And Phase Gates

All implementation must map to approved feature IDs in
`docs/FEATURE_CATALOG.md`. Delivery sequence, scope changes, and release gates
follow `docs/MASTER_PLAN.md`; deviations must be documented and approved rather
than implemented silently.
