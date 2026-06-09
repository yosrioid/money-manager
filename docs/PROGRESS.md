# Feature Progress

This file is the project implementation tracker and must be updated in the same
pull request that completes a feature or milestone.

## Status Values

- `Planned`: scope is known but implementation has not started.
- `In Progress`: active implementation exists on a branch.
- `Blocked`: implementation cannot continue without an external decision or
  dependency.
- `Done`: acceptance criteria, tests, review requirements, and documentation are
  complete.

## Current Milestone

- **Milestone:** Authentication and personal workspace
- **Status:** Planned
- **Updated:** 2026-06-09

## Roadmap

| ID | Feature | Status | Completed | Notes |
|---|---|---|---|---|
| F-001 | Laravel and Vue project scaffolding | Done | 2026-06-09 | Laravel 13 Vue starter kit |
| F-002 | CI, linting, static analysis, and test baseline | Done | 2026-06-09 | Quality gates established |
| F-003 | Authentication and personal workspace | Planned | - | Include email verification and 2FA |
| F-004 | Currencies and workspace preferences | Planned | - | IDR default and workspace timezone |
| F-005 | Account groups and accounts | Planned | - | Cash, bank, e-wallet, asset, liability |
| F-006 | Categories and subcategories | Planned | - | Income and expense categories |
| F-007 | Double-entry ledger foundation | Planned | - | Highest-risk core module |
| F-008 | Income, expense, and transfer flows | Planned | - | Includes authorization and validation |
| F-009 | Transaction history, search, and filters | Planned | - | Paginated and workspace-scoped |
| F-010 | Dashboard and account balances | Planned | - | Derived from posted ledger entries |
| F-011 | Monthly category budgets | Planned | - | Actual versus budget |
| F-012 | Reports and CSV export | Planned | - | Monthly summary and category breakdown |
| F-013 | Transaction templates and bookmarks | Planned | - | Fast manual entry |
| F-014 | Recurring transactions | Planned | - | Idempotent scheduled generation |
| F-015 | Attachments and receipt storage | Planned | - | Private object storage |
| F-016 | Credit cards and installment plans | Planned | - | Post-MVP |
| F-017 | Multi-currency transactions | Planned | - | Post-MVP |
| F-018 | Shared workspaces | Planned | - | Post-MVP |
| F-019 | PWA installation and offline drafts | Planned | - | Post-MVP |

## Completed Features

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
- **PR:** Not available.

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
  with SQLite and in-memory application services.
- **Tests:** `composer validate --strict`, `composer ci:check`, `npm run build`,
  and the Chromium Playwright smoke test passed.
- **Git:** Changes remain uncommitted on `docs/git-safety-rules`.

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

## Decision Log

### 2026-06-09 - Start With A Modular Monolith

Laravel and Vue will be deployed as a modular monolith. A separate API,
microservices, and offline-first synchronization are deferred until justified by
product requirements.

### 2026-06-09 - Use Double-Entry Bookkeeping

All financial transactions will use balanced ledger entries. Account balances
will be derived from posted entries rather than maintained as independent
mutable values.
