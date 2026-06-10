# Master Delivery Plan

## Purpose And Authority

This document is the authoritative delivery plan for Money Manager. It defines
how product scope is selected, sequenced, implemented, reviewed, and released.

The project must not silently diverge from this plan. When implementation
reveals a better approach or a missing capability, update the relevant
documentation through the change-control process before or in the same pull
request as the implementation.

## Sources Of Truth

Use the following documents in this precedence order:

1. `docs/STRICT_RULES.md`: non-negotiable safety and engineering rules.
2. `docs/AI_WORKFLOW.md`: mandatory assistant startup and execution protocol.
3. `docs/MASTER_PLAN.md`: delivery sequence, gates, and scope governance.
4. `docs/FEATURE_CATALOG.md`: authoritative feature inventory and phase mapping.
5. `docs/PRODUCT_SCOPE.md`: product goals, target users, and release boundaries.
6. `docs/ARCHITECTURE.md`: technical design and domain invariants.
7. `docs/CODING_STANDARDS.md`: implementation quality standards.
8. `docs/PROGRESS.md`: current execution status and completed work.
9. `docs/WORKLOG.md`: latest daily checkpoints and session handoff state.

When two documents conflict, stop implementation and resolve the documentation
conflict first. Never choose whichever rule is easier.

## Product Strategy

The project will deliver an original web-based personal finance manager that
covers the practical workflows of Money Manager Expense & Budget without
copying its branding, copywriting, screenshots, visual design, or source code.

Delivery targets:

- `MVP`: reliable daily finance tracking through Phase 4 plus the required
  Phase 8 release gates.
- `Parity Release`: all approved `Parity` capabilities through Phase 7 plus the
  Phase 8 parity audit.
- `Extended Releases`: collaboration, sync, PWA, OCR, and other capabilities
  marked `Extended`.

## Planning Principles

- Financial correctness takes priority over delivery speed.
- Build vertical slices that are usable, authorized, tested, and documented.
- Establish domain foundations before UI convenience features that depend on
  them.
- Do not implement later-phase features by weakening earlier-phase invariants.
- Keep one source of truth for balances: posted double-entry ledger entries.
- Prefer explicit scope decisions over hidden assumptions.
- Treat migrations, APIs, exports, and financial states as long-lived
  contracts.

## Delivery Lifecycle

Every feature follows this lifecycle:

1. `Planned`: cataloged, but no implementation branch exists.
2. `Ready`: dependencies, acceptance criteria, and design decisions are clear.
3. `In Progress`: active implementation exists on a feature branch.
4. `In Review`: PR is open and all required documentation is included.
5. `Done`: merged, acceptance criteria met, tests pass, and progress updated.
6. `Deferred`: intentionally postponed with a documented reason.
7. `Blocked`: cannot progress until a named decision or dependency is resolved.

Only `docs/PROGRESS.md` records lifecycle status. Status changes must happen in
the same PR that causes the change.

## Scope Change Control

No new feature, phase move, or material behavior change may be implemented
silently.

Required process:

1. Identify the affected feature IDs and phase.
2. Explain the user value, dependency, technical impact, migration impact,
   security impact, and delivery impact.
3. Update `docs/FEATURE_CATALOG.md` when scope or phase mapping changes.
4. Update this document when delivery gates or sequencing change.
5. Update `docs/ARCHITECTURE.md` when domain boundaries or invariants change.
6. Update `docs/PROGRESS.md` with the decision and resulting status.
7. Obtain user approval before dependency, infrastructure, or material product
   scope changes.

Small implementation details that do not change behavior or architecture do not
require a scope-change entry.

## Phase Overview

| Phase | Name                                          | Primary Outcome                                        | Release Boundary |
| ----- | --------------------------------------------- | ------------------------------------------------------ | ---------------- |
| 0     | Engineering Foundation                        | Stable project, CI, and governance                     | Complete         |
| 1     | Identity, Workspace, And Financial Setup      | User can securely configure personal finance structure | Internal alpha   |
| 2     | Ledger And Core Transactions                  | User can accurately record financial activity          | Core alpha       |
| 3     | Daily Use And Transaction Productivity        | Daily tracking is efficient and navigable              | Private beta     |
| 4     | Budgets, Goals, Statistics, And Data Exchange | Complete first-release feature set                     | MVP candidate    |
| 5     | Cards, Debt, Assets, And Multi-Currency       | Advanced financial account support                     | Advanced beta    |
| 6     | Automation, Attachments, Backup, And Restore  | Repetitive workflows and data portability              | Parity candidate |
| 7     | Collaboration, Sync, PWA, And Customization   | Multi-device and shared web experience                 | Extended release |
| 8     | Production Hardening And Parity Acceptance    | Prove release quality and close known gaps             | Release gate     |

## Estimation And Capacity Assumptions

Estimates are planning ranges, not delivery promises. They assume:

- One experienced full-stack engineer working primarily on this project.
- Product decisions and reviews are available without long blocking delays.
- No major dependency, provider, or infrastructure migration is introduced.
- Each feature includes backend, frontend, authorization, tests, documentation,
  review fixes, and CI.
- Security and financial-integrity work is not reduced to meet a date.

| Phase   | Relative Size | One-Engineer Planning Range    | Main Dependency                               |
| ------- | ------------- | ------------------------------ | --------------------------------------------- |
| Phase 0 | Small         | Complete                       | None                                          |
| Phase 1 | Large         | 5-8 weeks                      | Phase 0                                       |
| Phase 2 | Extra Large   | 8-12 weeks                     | Stable Phase 1 financial setup                |
| Phase 3 | Large         | 5-8 weeks                      | Stable transaction APIs and queries           |
| Phase 4 | Extra Large   | 7-11 weeks                     | Stable ledger and period calculations         |
| Phase 5 | Extra Large   | 7-11 weeks                     | Mature core ledger                            |
| Phase 6 | Extra Large   | 7-11 weeks                     | Stable jobs, files, and transaction contracts |
| Phase 7 | Extra Large   | 9-14 weeks                     | Mature authorization and conflict contracts   |
| Phase 8 | Large         | 3-6 weeks per release boundary | Target release scope complete                 |

Expected one-engineer range:

- MVP candidate through Phase 4: approximately 25-39 weeks after Phase 0.
- MVP release including Phase 8 gates: approximately 28-45 weeks.
- Full approved parity and extended scope: approximately 58-87 weeks.

Actual velocity must be recalculated from completed PR cycle time after each
phase. Never hide quality or scope reductions inside an estimate update.

## Delivery Cadence

- Plan work in one- or two-week iterations.
- Keep only one high-risk financial write feature actively changing at a time.
- Review progress, blockers, risk, and catalog changes at least once per
  iteration.
- Review phase exit gates before starting substantial work in the next phase.
- Perform a fresh reference-feature audit before parity-release planning.
- Re-estimate remaining phases after each completed phase using actual cycle
  time and defect data.

## Dependency Rules

- Phase 1 workspace isolation is mandatory before financial records.
- Phase 2 ledger invariants are mandatory before balances, budgets, reports,
  cards, automation, import, or sync.
- Phase 3 query contracts are mandatory before broad report customization.
- Phase 4 period calculations are reused by card cycles and recurring rules.
- Phase 5 advanced financial semantics must not be approximated in earlier
  phases.
- Phase 6 jobs and restore flows require established idempotency contracts.
- Phase 7 offline and sync work cannot begin before conflict rules are approved.
- Phase 8 gates run at every release boundary, not only at project completion.

## Primary Risk Register

| Risk                                         | Impact                           | Mitigation                                                                      | Review Point              |
| -------------------------------------------- | -------------------------------- | ------------------------------------------------------------------------------- | ------------------------- |
| Incorrect ledger or balance logic            | Critical financial corruption    | Immutable double-entry design, constraints, reconciliation, high-priority tests | Every Phase 2+ PR         |
| Cross-workspace data exposure                | Critical privacy breach          | Scoped context, policies, route binding, denial tests                           | Every owned-resource PR   |
| Scope expansion from reference-app discovery | Schedule and design drift        | Catalog baseline, change control, parity audit                                  | Every iteration and P8-09 |
| Period boundary errors                       | Incorrect budgets and reports    | Shared timezone-aware period objects and boundary tests                         | Phases 1, 3, 4, 5, 6      |
| Duplicate jobs or imports                    | Incorrect financial records      | Idempotency keys, overlap protection, retry tests                               | Phases 4 and 6            |
| Multi-currency precision loss                | Incorrect balances               | Decimal rates, original/base values, reconciliation                             | Phase 5                   |
| Unsafe backup or restore                     | Data loss or duplication         | Versioned format, preview, transactional restore, rehearsal                     | Phases 6 and 8            |
| Offline or sync conflicts                    | Silent financial inconsistency   | Offline drafts only, optimistic concurrency, explicit conflicts                 | Phase 7                   |
| Large PRs and hidden coupling                | Review defects and slow delivery | Vertical slices, feature IDs, phase gates, PR size discipline                   | Every PR                  |
| Dependency or provider changes               | Security and schedule risk       | Explicit approval, pinned actions, audits, documented migration                 | Every dependency change   |

## Phase 0 - Engineering Foundation

### Objective

Create a reliable development environment and prevent unsafe delivery
practices before feature development begins.

### Scope

- Feature IDs: `P0-01` through `P0-06`.
- Laravel/Vue foundation, PostgreSQL, Redis, CI, quality tools, browser smoke
  tests, contribution rules, and authoritative project documentation.

### Planned Work

1. Establish current framework stack and local services.
2. Establish formatting, static analysis, unit, feature, and browser checks.
3. Establish strict Git, PR, authorship, and documentation rules.
4. Establish product scope, architecture, feature catalog, master plan, and
   progress tracker.

### Exit Gate

- `composer ci:check` and browser CI pass from a clean checkout.
- All mandatory project documents exist and agree.
- Changes cannot reach `main` without a reviewed PR.

## Phase 1 - Identity, Workspace, And Financial Setup

### Objective

Allow a user to securely create a private finance workspace and configure the
reference data required by all later financial activity.

### Scope

- Feature IDs: `P1-01` through `P1-34`.
- Existing roadmap mapping: `F-003` through `F-006`, and `F-021`.

### Recommended PR Sequence

1. `P1-01` to `P1-08`: finish and verify authentication/security journeys.
2. `P1-09` to `P1-11`: workspace model, membership, context middleware, and
   isolation policies.
3. `P1-12` to `P1-18`: preferences, locale, timezone, and custom period rules.
4. `P1-19` to `P1-25`: account groups, account types, opening balance contract,
   visibility, ordering, and archive.
5. `P1-26` to `P1-33`: categories, subcategories, merchants, tags, application
   lock, and starter presets.
6. `P1-34`: branded public landing page replacing the starter-kit welcome
   screen.

### Architecture Work

- Create workspace ownership and membership contracts.
- Establish active-workspace resolution and scoped route binding.
- Establish enums for account and category types.
- Establish reusable ordering, visibility, and archive behavior.
- Do not calculate opening balance outside the ledger contract; the UI may be
  implemented now, but posting integrates with Phase 2.

### Required Tests

- Authentication and sensitive-action flows.
- Automatic personal workspace creation.
- Cross-workspace access denial for every new owned model.
- Preference boundary tests for timezone and custom month start.
- Account/category validation, ordering, visibility, and archive behavior.

### Exit Gate

- A verified user can configure their complete financial structure.
- No workspace-owned record is accessible from another workspace.
- Phase 2 ledger implementation can use stable account/category contracts.

## Phase 2 - Ledger And Core Transactions

### Objective

Implement the financial engine and the minimum reliable transaction workflows.
This is the highest-risk phase.

### Scope

- Feature IDs: `P2-01` through `P2-24`.
- Existing roadmap mapping: `F-007`, `F-008`, and the balance foundation of
  `F-010`.

### Recommended PR Sequence

1. `P2-01` to `P2-10`: ledger schema, enums, data objects, posting, reversal,
   audit, and balance services.
2. `P2-11` and `P2-12`: income and expense vertical slices.
3. `P2-13` to `P2-16`: transfer, fee, withdrawal, and settlement workflows.
4. `P2-17` to `P2-20`: transaction metadata.
5. `P2-21` to `P2-24`: split, calculator, drafts, and duplication.

### Architecture Work

- Implement `PostTransaction`, `ReverseTransaction`, and
  `CalculateAccountBalance`.
- Use immutable posted entries and explicit reversal links.
- Add database constraints that protect balance and ownership contracts where
  PostgreSQL can enforce them.
- Define locking and idempotency strategy before exposing write endpoints.
- Keep transaction forms separate from ledger posting data objects.

### Required Tests

- Balanced and rejected-unbalanced postings.
- Income, expense, transfer, transfer fee, and split transaction entries.
- Atomic rollback on any posting failure.
- Concurrent writes and duplicate submission protection.
- Immutability, reversal, replacement, audit, and cross-workspace denial.
- Property-style tests for core amount and balance invariants where practical.

### Exit Gate

- All supported posted transactions balance to zero.
- Account balances exactly match posted ledger entries.
- Financial correction never mutates posted history.
- Core posting paths have explicit concurrency and authorization coverage.

## Phase 3 - Daily Use And Transaction Productivity

### Objective

Turn the reliable transaction engine into an efficient daily money-management
experience.

### Scope

- Feature IDs: `P3-01` through `P3-21`.
- Existing roadmap mapping: `F-009`, `F-010`, and `F-013`.

### Recommended PR Sequence

1. `P3-01` to `P3-06`: daily, calendar, weekly, monthly, summary, and memo
   navigation.
2. `P3-07` to `P3-12`: search, filters, sorting, pagination, detail, and safe
   bulk actions.
3. `P3-13` to `P3-17`: bookmarks, payment profiles, recent values, favorites,
   and form configuration.
4. `P3-18` to `P3-21`: keyboard, responsive, navigation productivity, and
   statistics-inclusion controls.

### Architecture Work

- Build reusable authorized transaction queries.
- Validate sort and filter fields against allowlists.
- Use workspace-local date boundaries without changing stored UTC timestamps.
- Cache only derived, safely invalidated read models.

### Required Tests

- Search and combined-filter correctness.
- Timezone and custom month-boundary grouping.
- Pagination stability and authorization.
- Bookmark/profile reuse without modifying the source transaction.
- Critical desktop and mobile browser journeys.

### Exit Gate

- User can efficiently enter, locate, inspect, and navigate daily records.
- Views agree on totals for the same authorized period and filter set.
- Large transaction histories remain responsive.

## Phase 4 - Budgets, Goals, Statistics, And Data Exchange

### Objective

Complete the first-release product by adding planning, insight, and portable
data workflows.

### Scope

- Feature IDs: `P4-01` through `P4-25`.
- Existing roadmap mapping: `F-011` and `F-012`.

### Recommended PR Sequence

1. `P4-01` to `P4-10`: budget contracts, overrides, pace, trends, and goals.
2. `P4-11` to `P4-19`: summary, category, merchant, account, asset, trend, and
   customizable reporting.
3. `P4-20` to `P4-25`: CSV/Excel export and validated idempotent import.

### Architecture Work

- Implement `CalculateBudgetUsage` using posted ledger data.
- Create shared period-boundary objects for budgets and reports.
- Use deferred Inertia props for expensive report data.
- Queue large exports/imports and authorize again inside jobs.
- Define versioned import/export formats.

### Required Tests

- Budget default, override, and boundary calculations.
- Report totals reconciled against ledger entries.
- Export authorization and content validation.
- Import preview, invalid rows, idempotency, and rollback.
- Performance tests for common dashboards and reports.

### Exit Gate

- MVP feature scope is complete.
- Budgets and reports reconcile with the ledger.
- User can safely export and import supported data.
- Required Phase 8 MVP gates can begin.

## Phase 5 - Cards, Debt, Assets, And Multi-Currency

### Objective

Support advanced personal finance structures without weakening the core ledger.

### Scope

- Feature IDs: `P5-01` through `P5-16`.
- Existing roadmap mapping: `F-016` and `F-017`.

### Recommended PR Sequence

1. `P5-01` to `P5-05`: card liabilities, cycles, outstanding balances, and
   settlement.
2. `P5-06` to `P5-09`: installments, loans, and payoff progress.
3. `P5-10` to `P5-16`: account currency, conversion, base totals, savings, and
   tracked assets.

### Architecture Work

- Model liabilities with explicit sign and display semantics.
- Store exchange rates using precise decimal values.
- Preserve original and base-currency values for auditable conversion.
- Separate installment schedule state from posted payment truth.

### Required Tests

- Billing-cycle and payment-date boundaries.
- Card settlement without duplicate expense.
- Installment schedule and repayment reconciliation.
- Exchange precision, conversion totals, and reversal behavior.

### Exit Gate

- Cards, debt, installments, and supported multi-currency workflows reconcile
  with the core ledger.

## Phase 6 - Automation, Attachments, Backup, And Restore

### Objective

Reduce repetitive work and ensure users can protect and move their data.

### Scope

- Feature IDs: `P6-01` through `P6-16`.
- Existing roadmap mapping: `F-014` and `F-015`.

### Recommended PR Sequence

1. `P6-01` to `P6-06`: recurring rules, automatic transfers, schedules,
   failure handling, and reminders.
2. `P6-07` to `P6-11`: private attachments and optional OCR suggestions.
3. `P6-12` to `P6-16`: backup export, restore preview, safe restore, attachment
   backup, and operational backup policy.

### Architecture Work

- Implement idempotent scheduled generation with overlap protection.
- Keep generated transactions linked to their source rule.
- Store attachments privately and validate ownership on every access.
- Version backup format and make restore transactional and auditable.

### Required Tests

- Repeated scheduler and job retries do not duplicate transactions.
- Schedule boundaries respect workspace timezone.
- Cross-workspace attachment access is denied.
- Restore preview, failure rollback, compatibility, and duplicate protection.

### Exit Gate

- Automated activity is safe to retry.
- User-owned data and attachments can be exported and restored safely.

## Phase 7 - Collaboration, Sync, PWA, And Customization

### Objective

Extend the product from a single-user web application into a shared,
multi-device platform.

### Scope

- Feature IDs: `P7-01` through `P7-16`.
- Existing roadmap mapping: `F-018` and `F-019`.

### Recommended PR Sequence

1. `P7-01` to `P7-03`: invitations, roles, and member audit trail.
2. `P7-04` to `P7-06`: synchronization, conflicts, and real-time refresh.
3. `P7-07` to `P7-09`: PWA, offline drafts, and desktop experience.
4. `P7-10` to `P7-16`: theme, accessibility, notifications, multiple books,
   display preferences, and controlled data reset.

### Architecture Work

- Expand workspace authorization without weakening personal-workspace rules.
- Define optimistic concurrency and conflict contracts before offline sync.
- Permit offline drafts only; never post ledger entries offline.
- Keep server-side financial truth authoritative.

### Required Tests

- Role matrix and member lifecycle.
- Conflict detection and stale-write rejection.
- Offline draft synchronization and duplicate protection.
- Accessibility and supported responsive browser journeys.

### Exit Gate

- Shared and multi-device usage preserves authorization and ledger integrity.
- Offline behavior cannot silently create posted financial conflicts.

## Phase 8 - Production Hardening And Parity Acceptance

### Objective

Prove that a release is secure, correct, observable, recoverable, performant,
and aligned with its declared feature level.

### Scope

- Feature IDs: `P8-01` through `P8-10`.
- This phase runs as release gates after Phase 4 and again before parity and
  extended releases.

### Workstreams

1. Security and privacy review.
2. Query and frontend performance review.
3. Concurrency, retry, queue, and scheduler audit.
4. Browser, responsive, localization, and accessibility acceptance.
5. Monitoring, backup, recovery, and operational runbooks.
6. Feature-catalog parity audit and release sign-off.

### Exit Gate

- No unresolved critical or high-severity security issue.
- Financial and workspace invariants pass the full suite.
- Required performance targets are met.
- Backup restoration has been rehearsed.
- Every required catalog item is `Done` or explicitly deferred with approval.

## Cross-Phase Workstreams

The following work applies continuously:

### Security

- Authorization, rate limits, private files, dependency audits, secret
  handling, and audit logs.

### Data Integrity

- Database constraints, migrations, backups, idempotency, reversibility, and
  ledger reconciliation.

### User Experience

- Responsive layout, accessibility, localization, empty states, loading states,
  and actionable errors.

### Quality Engineering

- Pest, Vitest, Playwright, Larastan, Pint, ESLint, Prettier, audits, and
  production builds.

### Operations

- Horizon, scheduler, monitoring, error tracking, backup, restore, and release
  runbooks.

## Feature Planning Template

Before implementation starts, add or confirm:

```markdown
## Feature: Pn-nn Capability Name

- User outcome:
- In scope:
- Out of scope:
- Dependencies:
- Architecture decisions:
- Data and migration impact:
- Authorization and security impact:
- Acceptance criteria:
- Required tests:
- Required documentation:
- Rollout or compatibility concerns:
```

## Pull Request Planning Rules

- Prefer one independently reviewable vertical slice per PR.
- A PR must identify its feature IDs in the description.
- Avoid combining multiple phases in one PR.
- Foundation PRs may prepare contracts, but must not claim later feature
  completion.
- A feature is not complete because its UI exists; persistence, authorization,
  validation, tests, and documentation are required.
- Large features must be split by stable contracts, not arbitrary file groups.

## Release Rules

### MVP Release

Requires:

- All `MVP` items needed through Phase 4 are `Done`.
- Applicable Phase 8 gates are `Done`.
- No unresolved critical financial, authorization, security, or recovery issue.

### Parity Release

Requires:

- All approved `Parity` items are `Done` or explicitly deferred with user
  approval.
- `P8-09` reference-feature parity audit is complete.

### Extended Release

Requires:

- Selected `Extended` items have explicit release scope.
- Their operational and security implications pass Phase 8 gates.

## Progress Reporting

At the end of each completed feature:

1. Update feature status in `docs/PROGRESS.md`.
2. Add a completion entry with outcomes, decisions, tests, documentation,
   follow-ups, and PR.
3. Update the current phase status and exit-gate checklist.
4. Update catalog or architecture only when scope or design changed.
5. Never mark a phase complete until every required exit gate passes.

For daily and session-level execution:

1. Record meaningful checkpoints in `docs/WORKLOG.md`.
2. Keep the worklog operational and append-only; do not use it to redefine
   approved scope or official feature status.
3. Checkpoint before pausing, before final responses for non-trivial repository
   work, and early when execution budget appears low.
