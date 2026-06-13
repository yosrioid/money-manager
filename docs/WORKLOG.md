# Daily Worklog And Session Handoff

## Purpose

This append-only worklog records daily progress and durable session handoffs.
It allows a new human or AI session to determine what was attempted, what
changed, what remains unfinished, and what should happen next.

`docs/PROGRESS.md` remains the authoritative feature and phase status tracker.
This file records operational detail and must not silently redefine scope,
feature status, or completion.

## Update Rules

Add a checkpoint:

- after each meaningful implementation or investigation milestone;
- after discovering a blocker, important decision, or failed approach;
- before pausing, switching tasks, or ending a work session;
- before sending the final response for non-trivial repository work;
- before a long-running or risky operation when the current state would be
  difficult to reconstruct;
- when the assistant detects that context, token, time, or execution budget is
  becoming low.

Do not add noise for trivial read-only questions or every individual command.
Combine related activity into concise checkpoints.

Abrupt process termination, tool failure, network loss, or exhausted context
can prevent a final checkpoint. Therefore, checkpoint throughout long work
instead of relying only on an end-of-session update.

## Entry Format

Add new entries at the top of the `Entries` section:

```markdown
### YYYY-MM-DD HH:MM TZ - Short Checkpoint Title

- **Branch:** Current branch.
- **Feature IDs:** Approved IDs, or `Engineering only`.
- **Status:** In progress, blocked, ready for review, or completed.
- **Completed:** Concrete work completed since the previous checkpoint.
- **Verification:** Commands or checks run and their results.
- **Decisions:** Important decisions or `None`.
- **Blockers:** Blocking conditions or `None`.
- **Uncommitted:** Relevant uncommitted or unpushed state.
- **Next:** The exact safest next action.
```

## Entries

### 2026-06-13 01:20 WIB - `P3-08` Advanced Filters

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-08`.
- **Status:** Completed.
- **Completed:** Added `type`, `status`, `category_id`, `account_id`,
  `tag_id`, `from`, and `to` query-string filters to
  `TransactionController::index()`, alongside the existing `q` search. `type`
  and `status` are validated against `TransactionType::tryFrom()` /
  `TransactionStatus::tryFrom()`; `category_id`/`account_id`/`tag_id` must be
  numeric; `from`/`to` must match `YYYY-MM-DD` and are parsed in the
  workspace timezone, then converted to UTC for the `occurred_at` comparison
  (`to` is inclusive of the whole day). Invalid values are silently ignored
  (`null`) rather than erroring. Category/account/tag filters use
  `whereHas('entries', ...)` / `whereHas('tags', ...)`, and all filters
  combine with AND alongside the search's grouped OR clause. The response now
  includes `filters` (the resolved current values) and `filterOptions`
  (`types`, `statuses` — limited to `posted`/`reversed`/`replaced` since
  `draft`/`voided` transactions never have `posted_at` — plus the workspace's
  active `categories`, `accounts`, and `tags`). `transactions/Index.vue` now
  renders selects for type/status/category/account/tag and a from/to date
  range, all submitted together via `router.get(..., { preserveState: true,
  replace: true })`, with a combined "Clear" action and an updated empty-state
  message.
- **Verification:** Added `tests/Feature/TransactionFilterTest.php` (8 tests,
  105 assertions) covering type, category, account, tag, status (combined
  with type for a transfer), date range, filters combined with search, and an
  invalid filter value being ignored. Full suite: 217 Pest tests / 1281
  assertions, PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript
  checks (`vue-tsc`), and a production build all passed.
- **Decisions:** None.
- **Blockers:** None.
- **Uncommitted:** All `P3-08` changes are ready to commit on `feat/phase-3`.
- **Next:** Commit `P3-08`, then continue Phase 3 with `P3-09` (sorting).

### 2026-06-13 00:30 WIB - `P3-07` Search

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-07`.
- **Status:** Completed.
- **Completed:** Added a `q` query-string search to
  `TransactionController::index()`. When non-empty, it filters posted
  transactions where the description or memo contains the term (a
  case-insensitive `like "%{$term}%"`), or a related merchant, account, or
  category name contains the term (`orWhereHas` on `merchant`,
  `entries.account`, and `entries.category`), or — when the term is numeric —
  any entry's absolute `amount` exactly equals it. The search is grouped so
  it combines correctly
  with the existing `whereNotNull('posted_at')` scope, and `withQueryString()`
  preserves `q` across pagination. `transactions/Index.vue` now renders a
  search box (with a clear button) bound to `?q=`, navigated via
  `router.get(..., { preserveState: true, replace: true })`, and shows a
  distinct "No transactions match your search." message when a search yields
  no results.
- **Verification:** Added `tests/Feature/TransactionSearchTest.php` (7 tests,
  82 assertions) covering search by description, memo, merchant, category,
  account, amount, and a no-match case. Full suite: 209 Pest tests / 1176
  assertions, PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript
  checks (`vue-tsc`), and a production build all passed.
- **Decisions:** None.
- **Blockers:** None.
- **Uncommitted:** All `P3-07` changes are ready to commit on `feat/phase-3`.
- **Next:** Commit `P3-07`, then continue Phase 3 with `P3-08` (advanced
  filters).

### 2026-06-12 23:40 WIB - `P3-06` Daily Memo

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-06`.
- **Status:** Completed.
- **Completed:** Added a workspace-scoped `day_notes` table (unique on
  `workspace_id` + `date`, plain `note` text), `DayNote` model with
  `Workspace::dayNotes(): HasMany`, `DayNoteFactory`, `DayNotePolicy`
  (mirrors `TagPolicy`/`MerchantPolicy`), `SaveDayNoteRequest`, and
  `DayNoteController::update`/`destroy` exposed as
  `transactions.day-notes.update` (PUT) and `transactions.day-notes.destroy`
  (DELETE), both keyed by a regex-constrained `{date}` route parameter
  (`\d{4}-\d{2}-\d{2}`) to avoid Eloquent route-model-binding on a
  composite-keyed model. `TransactionController::day()` now passes a
  `note: string | null` prop and `calendar()` passes a `notes: Record<string,
  string>` map for the month. Added a new shadcn-vue `Textarea` component
  (`resources/js/components/ui/textarea`). `transactions/Day.vue` now has a
  "Daily memo" card with a save form (`DayNoteController.update.form()`) and a
  "Remove" action (`DayNoteController.destroy()` via `Link
  method="delete"`). `transactions/Calendar.vue` cells are now links to
  `transactions.day` for that date and show a `StickyNote` icon (with the
  note text as a title/tooltip) when a note exists.
- **Verification:** Added `tests/Feature/TransactionDayNoteTest.php` (5
  tests, 61 assertions) covering create, update (upsert, no duplicate row),
  delete, validation, and workspace isolation. Full suite: 202 Pest tests /
  1094 assertions, PHPStan/Larastan (0 errors), Pint, ESLint, Prettier,
  TypeScript checks (`vue-tsc`), and a production build all passed.
- **Decisions:** Removed the `'date' => 'date'` Eloquent cast from `DayNote`
  — Laravel's `date` cast serializes using the connection's full datetime
  format on write (not `Y-m-d`), which broke `where('date', $dateString)`
  lookups against the plain `date` column. The model now treats `date` as a
  plain `Y-m-d` string throughout, matching how the controller and routes
  already handle it.
- **Blockers:** None.
- **Uncommitted:** All `P3-06` changes are ready to commit on `feat/phase-3`.
- **Next:** Commit `P3-06`, then continue Phase 3 with `P3-07` (search).

### 2026-06-12 22:10 WIB - `P3-05` Summary View

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-05`.
- **Status:** In progress.
- **Completed:** Added `transactions.summary`
  (`TransactionController::summary()`) rendering `transactions/Summary` for a
  workspace-local month (`?month=YYYY-MM`, defaults to the current
  workspace-local month, with previous/next month navigation). Shows period
  income/expense/net totals (per currency) and a record count derived from
  `SummarizeTransactionPeriod::forMonth()`, plus an "account movement" section
  listing each active account's opening balance, closing balance, and net
  change for the period, computed with the existing
  `CalculateAccountBalance::calculateAsOf()`. Added "Summary" to
  `TransactionViewNav`.
- **Verification:** Added `tests/Feature/TransactionSummaryTest.php` (2
  tests, 38 assertions). Full suite: 197 Pest tests / 1033 assertions,
  PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript checks
  (`vue-tsc`), and a production build all passed.
- **Decisions:** The catalog acceptance summary for `P3-05` says the period
  summary "combines budget and account movement," but budgets (`P4-01` to
  `P4-10`) are Phase 4 scope and do not exist yet. This slice implements the
  account-movement half now; budget comparison is recorded as explicit
  follow-up work for when Phase 4 budgets land, per
  `docs/PROGRESS.md`'s "record follow-up work explicitly" rule. Opening
  balance is the posted ledger balance as of the instant before the period
  starts; closing balance is as of the last instant of the period.
- **Blockers:** None.
- **Next:** Continue Phase 3 with `P3-06` (daily memo).

### 2026-06-12 21:45 WIB - `P3-04` Monthly View

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-04`.
- **Status:** In progress.
- **Completed:** Added `SummarizeTransactionPeriod::forYear()`, which calls
  the existing `summarize()` for the full workspace-local year and re-buckets
  the per-day results into per-month income/expense/net/count totals. Added
  `transactions.monthly` (`TransactionController::monthly()`) rendering
  `transactions/Monthly`, a 12-month grid for a workspace-local year
  (`?year=YYYY`, defaults to the current workspace-local year) with
  previous/next year navigation; each month cell links to its
  `transactions.calendar` view. Added "Monthly" to `TransactionViewNav`.
- **Verification:** Added `tests/Feature/TransactionMonthlyTest.php` (3
  tests, 50 assertions). Full suite: 195 Pest tests / 995 assertions,
  PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript checks
  (`vue-tsc`), and a production build all passed.
- **Decisions:** None beyond reusing the existing per-currency
  income/expense/net/count aggregation shape from `SummarizeTransactionPeriod`.
- **Blockers:** None.
- **Next:** Continue Phase 3 with `P3-05` (summary view).

### 2026-06-12 21:15 WIB - `P3-03` Weekly View

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-03`.
- **Status:** In progress.
- **Completed:** Renamed `SummarizeTransactionCalendar` to
  `SummarizeTransactionPeriod` and added `forWeek()` alongside `forMonth()`,
  sharing the same per-currency income/expense/net/count aggregation. Added
  `transactions.weekly` (`TransactionController::weekly()`) rendering
  `transactions/Weekly`, a 7-day grid with per-day totals, a weekly aggregate
  total, and previous/next week navigation (`?week=YYYY-MM-DD`, defaults to
  the current workspace-local week starting Sunday). Added `transactions.day`
  (`TransactionController::day()`) rendering `transactions/Day`, a
  single-date drill-down listing posted transactions for that workspace-local
  date with previous/next day navigation (`?date=YYYY-MM-DD`, defaults to the
  current workspace-local date). Extracted `transformTransaction()` and
  `parseLocalDate()` helpers on `TransactionController`, shared by `index()`,
  `day()`, `weekly()`. Added "Weekly" to `TransactionViewNav`. Each calendar
  cell on `Weekly.vue` links to its `Day.vue` drill-down.
- **Verification:** Added `tests/Feature/TransactionWeeklyTest.php` (5 tests,
  80 assertions). Full suite: 192 Pest tests / 945 assertions,
  PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript checks
  (`vue-tsc`), and a production build all passed.
- **Decisions:** Weeks start on Sunday (workspace-local), matching the
  `Calendar.vue` weekday-label convention. The weekly totals card sums each
  day's income/expense/net per currency across the 7-day window.
- **Blockers:** None.
- **Next:** Continue Phase 3 with `P3-04` (monthly view).

### 2026-06-12 20:30 WIB - `P3-02` Calendar View

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-02`.
- **Status:** In progress.
- **Completed:** Added `transactions.calendar` (`TransactionController::calendar()`)
  rendering `transactions/Calendar`, backed by a new
  `App\Domain\Transactions\SummarizeTransactionCalendar` domain action that
  aggregates posted income, expense, net, and transaction counts per
  workspace-local date for a given month (`?month=YYYY-MM`, defaults to the
  current workspace-local month). Added `resources/js/pages/transactions/Calendar.vue`
  (month grid with previous/next navigation) and a shared
  `TransactionViewNav` component used by both `transactions/Index` and
  `transactions/Calendar` to switch between the daily and calendar views.
- **Verification:** Added `tests/Feature/TransactionCalendarTest.php` (3
  tests, 42 assertions). `composer ci:check` equivalent passed: 187 Pest
  tests / 865 assertions, PHPStan/Larastan (0 errors), Pint, ESLint,
  Prettier, TypeScript checks, and a production build.
- **Decisions:** Aggregates are grouped by currency code (no cross-currency
  summation). Only `income`/`expense` transaction types contribute to
  income/expense/net; the `count` includes all posted transaction types
  (including transfers) for the day.
- **Blockers:** None.
- **Uncommitted:** All `P3-02` changes ready to commit on `feat/phase-3`.
- **Next:** Commit this checkpoint, then continue Phase 3 with `P3-03`
  (weekly view).

### 2026-06-12 19:50 WIB - Phase 3 Branch Consolidation

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-01`.
- **Status:** In progress.
- **Completed:** Per user direction, switched Phase 3 to a single
  branch-per-phase workflow: pull requests are opened only once a phase is
  complete, with individual features committed to the shared phase branch.
  Closed PR #13 (no merge) and the now-superseded
  `feat/p3-daily-transaction-history` branch's PR, created `feat/phase-3` from
  `main`, and cherry-picked the `P3-01` commit
  (`feat(transactions): add daily transaction history (P3-01)`) onto it.
  Updated `docs/PROGRESS.md` so `P3-01` reflects `In Progress` on
  `feat/phase-3` instead of `Done` with a merged PR.
- **Verification:** No code changes since the prior `P3-01` checkpoint;
  `composer ci:check` already passed on that commit.
- **Decisions:** Phase 3 features (`P3-01` onward) will be committed
  sequentially to `feat/phase-3`. A single pull request to `main` will be
  opened once the Phase 3 exit gate in `docs/MASTER_PLAN.md` is met.
- **Blockers:** None.
- **Uncommitted:** `docs/PROGRESS.md` and `docs/WORKLOG.md` updates for this
  checkpoint are pending commit on `feat/phase-3`.
- **Next:** Commit this checkpoint, then continue Phase 3 with `P3-02`.

### 2026-06-12 16:00 WIB - `P3-01` Daily Transaction History

- **Branch:** `feat/p3-daily-transaction-history`.
- **Feature IDs:** `P3-01`.
- **Status:** Ready for review.
- **Completed:** Started Phase 3 with `P3-01` ("Transactions are grouped by
  workspace-local date"), per the recommended PR sequence in
  `docs/MASTER_PLAN.md`. Added `TransactionController::index()` and a
  `transactions.index` route returning posted transactions (eager-loaded
  merchant/tags/entries.account/entries.category), paginated at 30 and
  transformed with a `local_date` computed from `occurred_at` in the
  workspace timezone. Added `resources/js/pages/transactions/Index.vue`
  grouping the paginated rows by `local_date` with pagination controls, and
  added a "Transactions" sidebar nav item.
- **Verification:** Added `tests/Feature/TransactionHistoryTest.php` (4 tests,
  60 assertions). `composer ci:check` passed (184 Pest tests, 823 assertions,
  PHPStan/Larastan, Pint, ESLint, Prettier, TypeScript checks, production
  build).
- **Decisions:** Grouping uses `occurred_at` (the user-entered transaction
  date), not `posted_at`. Transactions with any `posted_at` are shown
  (`Posted`, `Reversed`, `Replaced`); drafts are excluded.
- **Blockers:** None.
- **Uncommitted:** All changes ready to commit on
  `feat/p3-daily-transaction-history`.
- **Next:** Commit, push, open a pull request to `main` for `P3-01`, verify
  CI, then continue Phase 3 with `P3-02` to `P3-06`.

### 2026-06-12 15:10 WIB - `v0.2.0-alpha.1` Phase 2 Core Ledger Alpha Published

- **Branch:** `main`.
- **Feature IDs:** `P2-01` to `P2-24`.
- **Status:** Completed.
- **Completed:** Merged release-preparation pull request
  [#11](https://github.com/yosrioid/money-manager/pull/11) into `main` as
  `fd75b78c8d0c366a32bff69ea5fe229b6b8eb662` (confirmed as the latest `main`
  commit). Created and pushed the annotated tag `v0.2.0-alpha.1` from that
  commit and published the
  [GitHub prerelease](https://github.com/yosrioid/money-manager/releases/tag/v0.2.0-alpha.1).
  Updated `docs/RELEASE_PROGRESS.md` to `Published` with the confirmed target
  SHA and checklist, added a release history entry, and updated
  `docs/releases/v0.2.0-alpha.1.md` with the published date and target commit.
- **Verification:** Tag created from an up-to-date, clean `main` after
  fast-forward merge; GitHub release created successfully against
  `fd75b78c8d0c366a32bff69ea5fe229b6b8eb662`.
- **Decisions:** User explicitly approved both tag creation and GitHub
  prerelease publication ("merge dan release"), satisfying the separate
  approval requirements in `docs/RELEASE_PROCESS.md`.
- **Blockers:** None.
- **Uncommitted:** Documentation updates to `docs/RELEASE_PROGRESS.md`,
  `docs/releases/v0.2.0-alpha.1.md`, and this entry are pending commit on a new
  branch per `docs/GIT_WORKFLOW.md` (no direct commits to `main`).
- **Next:** Commit and push a `docs/release-v0.2.0-alpha.1-published` branch,
  open a pull request to `main` recording the published tag and release, and
  begin Phase 3 planning.

### 2026-06-12 14:45 WIB - `v0.2.0-alpha.1` Phase 2 Release Preparation

- **Branch:** `docs/release-v0.2.0-alpha.1`.
- **Feature IDs:** `P2-01` to `P2-24`.
- **Status:** Ready for review.
- **Completed:** Marked Phase 2 and packages `F-007`/`F-008` `Done` in
  `docs/PROGRESS.md` with a completion entry, opened the `v0.2.0-alpha.1`
  candidate in `docs/RELEASE_PROGRESS.md` as `Preparing` with its checklist
  and verification evidence, and added the `docs/releases/v0.2.0-alpha.1.md`
  release-note draft following the mandatory format.
- **Verification:** Reused the post-merge verification recorded in the prior
  checkpoint (`composer ci:check` with 180 tests/763 assertions, Composer and
  npm audits, Playwright 2/2, migration and seed rehearsal against `main`
  `6c2ee24`). `bash scripts/check-governance.sh` passed on this branch.
- **Decisions:** Target commit is recorded as `6c2ee24` pending confirmation
  that this release-preparation PR's merge commit becomes the new `main` tip,
  per `docs/RELEASE_PROCESS.md` step 9.
- **Blockers:** Tag creation and GitHub prerelease publication remain pending
  explicit user approval, as required by `docs/RELEASE_PROCESS.md` and
  `docs/STRICT_RULES.md`.
- **Uncommitted:** None after this checkpoint; documentation changes are ready
  to commit and push for review.
- **Next:** Commit and push `docs/release-v0.2.0-alpha.1`, open a pull
  request to `main`, verify CI, then request explicit approval for the
  annotated tag and GitHub prerelease once the release-preparation PR is
  merged and confirmed as the latest `main` commit.

### 2026-06-12 14:30 WIB - Post-Merge Phase 2 Verification On `main`

- **Branch:** `main` (verification only; checked out from
  `feat/p2-core-income-expense`).
- **Feature IDs:** `P2-01` to `P2-24`.
- **Status:** Verified, ready for `v0.2.0-alpha.1` candidate decision.
- **Completed:** Confirmed PR #10 (`feat(phase-2): add ledger and core
  transaction flows`) merged to `main` at `6c2ee24` with passing CI. Re-ran the
  full local quality gate against the merged `main` commit and reviewed the
  ledger, posting, reversal, replacement, balance, audit, and core transaction
  domain code plus the income/expense/transfer entry UI against
  `docs/STRICT_RULES.md` and `docs/MASTER_PLAN.md` Phase 2 exit gate.
- **Verification:** `bash scripts/check-governance.sh` passed.
  `composer ci:check` passed (Pint, PHPStan/Larastan, Pest 180 tests / 763
  assertions, frontend lint/format/types, Vitest, production build).
  `composer audit` and `npm audit --audit-level=high` found no advisories.
  `npx playwright test` passed 2/2 against an isolated PHP 8.5 server with a
  temporary SQLite database (WebKit browser binary was missing locally and was
  installed via `npx playwright install webkit`). `php artisan migrate:fresh
  --seed` rehearsed cleanly on a temporary SQLite database.
- **Decisions:** No defects found that block release; Phase 2 (`P2-01` to
  `P2-24`) meets its master-plan exit gate (balanced posting, immutable posted
  entries, reversal/replacement, audit log, idempotency, draft/duplicate
  flows, workspace isolation, archived-reference rejection all covered by
  tests).
- **Blockers:** None. `docs/PROGRESS.md` package rows for `F-007`/`F-008` and
  Phase 2 phase status still read `In Review`; updating them to `Done` and
  preparing the `v0.2.0-alpha.1` milestone candidate per
  `docs/RELEASE_PROCESS.md` requires explicit user direction before edits.
- **Uncommitted:** This checkpoint only; no code changes made during this
  review.
- **Next:** On user instruction, update `docs/PROGRESS.md` (Phase 2 to `Done`,
  `F-007`/`F-008` rows to `Done` with completion entries) and
  `docs/RELEASE_PROGRESS.md` (`v0.2.0-alpha.1` candidate to `Preparing`) before
  any tag or release publication, per the approval-gated release workflow.

### 2026-06-12 13:50 WIB - Phase 2 Review Findings Addressed

- **Branch:** `feat/p2-core-income-expense`.
- **Feature IDs:** `P2-01` to `P2-10`, `P2-23`, `P2-24`.
- **Status:** In review.
- **Completed:** Prevented empty replacement transactions from removing posted
  balances; rejected archived account and category references at request and
  domain boundaries; rejected idempotency-key reuse with conflicting payloads;
  and added authorized draft resume, update, duplicate-to-draft, and
  post-from-draft flows.
- **Verification:** Focused regression suite passed with 29 tests and 146
  assertions. `composer ci:check` passed with 180 tests and 763 assertions;
  `npm run build`, `npm run test:e2e`, `bash scripts/check-governance.sh`,
  `composer audit`, `npm audit --audit-level=high`, and `git diff --check`
  passed.
- **Decisions:** Successful posting from a resumed draft marks the original
  balance-neutral draft as voided. Exact idempotent retries return the original
  transaction, while a changed payload using the same key fails explicitly.
- **Blockers:** None.
- **Uncommitted:** Review-fix implementation, regression tests, and this
  checkpoint remain uncommitted.
- **Next:** Review the final diff, commit and push the review fixes when
  requested, then verify PR #10 CI.

### 2026-06-12 13:35 WIB - Phase 2 Pull Request Opened

- **Branch:** `feat/p2-core-income-expense`.
- **Feature IDs:** `P2-01` to `P2-24`.
- **Status:** In review.
- **Completed:** Opened draft
  [PR #10](https://github.com/yosrioid/money-manager/pull/10) against `main`
  containing the complete Phase 2 ledger and core transaction implementation.
- **Verification:** Local final quality, security, governance, and browser gates
  passed before PR creation. GitHub Actions run
  [#27398888540](https://github.com/yosrioid/money-manager/actions/runs/27398888540)
  passed both quality and browser jobs.
- **Decisions:** Keep the PR as draft until reviewer feedback is resolved and
  merge is explicitly approved.
- **Blockers:** Review and merge approval remain pending.
- **Uncommitted:** This CI checkpoint needs a documentation commit and push.
- **Next:** Push this final CI checkpoint, verify the resulting docs-only CI,
  then hand PR #10 to review.

### 2026-06-12 13:28 WIB - Phase 2 Branch Ready For Pull Request

- **Branch:** `feat/p2-core-income-expense`.
- **Feature IDs:** `P2-01` to `P2-24`.
- **Status:** Ready for review.
- **Completed:** Completed the Phase 2 full review, duplicate-submission
  protection, atomic rollback coverage, and correction-flow category/workspace
  integrity hardening. Confirmed the branch is current with `origin/main`.
- **Verification:** Final `composer ci:check` passed with 173 Pest tests and 716
  assertions plus PHPStan, Pint, frontend lint, Prettier, type checks, Vitest,
  and production build. Governance, `git diff --check`, Composer audit, npm
  high-severity audit, and Playwright on Chromium and mobile Safari passed.
- **Decisions:** Keep Phase 2 and its feature packages `In Progress` until a
  pull request is created, reviewed, and merged.
- **Blockers:** None.
- **Uncommitted:** Final hardening and this checkpoint are ready to commit and
  push.
- **Next:** Commit and push the final hardening checkpoint. The branch will
  then be ready for pull request creation.

### 2026-06-12 13:20 WIB - Phase 2 Full Review Hardening

- **Branch:** `feat/p2-core-income-expense`.
- **Feature IDs:** `P2-01` to `P2-24`.
- **Status:** In progress.
- **Completed:** Audited the full Phase 2 branch against the master-plan exit
  gate. Added a workspace-scoped transaction idempotency key, stable
  create-form key propagation, and replay protection after affected account
  locks are acquired. Added regression coverage proving replayed submissions
  post only once and failures during audit recording roll back all financial
  writes. Fixed correction flows so reversal preserves category references and
  replacement validates and preserves workspace-scoped account/category
  references.
- **Verification:** Sequential full test coverage passes with 173 tests and 716
  assertions. PHPStan debug analysis, Pint, frontend lint, Prettier, type
  checking, Vitest, production build, governance, and `git diff --check`
  passed. Composer and npm security audits found no advisories, and Playwright
  passed on Chromium and mobile Safari. Final `composer ci:check` passed. The
  Composer PHPStan wrapper had intermittently stopped without diagnostics while
  direct PHPStan passed. One parallel verification attempt caused a transient
  missing Vite manifest; sequential build and tests passed.
- **Decisions:** Idempotency keys are unique per workspace and nullable for
  non-HTTP ledger operations. Replayed HTTP submissions return the already
  posted transaction without adding entries or audit records.
- **Blockers:** No pull request exists for this branch. Creating or modifying a
  PR requires explicit user approval.
- **Uncommitted:** Idempotency hardening, regression tests, migration, and this
  checkpoint are uncommitted and unpushed.
- **Next:** Review the final diff, then commit and push only after explicit user
  approval.

### 2026-06-12 11:15 WIB - Final Core Transaction Slice Implemented

- **Branch:** `feat/p2-core-income-expense`.
- **Feature IDs:** `P2-21` to `P2-24`.
- **Status:** In progress.
- **Completed:** Pushed transaction metadata checkpoint `fd28c96`. Added
  balanced multi-category split posting, safe integer arithmetic amount
  expressions, balance-neutral incomplete drafts with structured form data,
  authorized duplication into a new draft, and transaction-form controls for
  split entry, calculator input, and saving drafts.
- **Verification:** `composer ci:check` passed with 168 Pest tests and 697
  assertions plus PHPStan, Pint, frontend lint, Prettier, type checks, Vitest,
  and production build. Focused advanced-entry, transaction, transfer, and
  lifecycle tests pass with 31 tests and 122 assertions. Governance and
  `git diff --check` passed. Playwright smoke coverage passed on Chromium and
  mobile Safari.
- **Decisions:** Drafts store sanitized workspace-scoped input without ledger
  entries. Duplication creates a new draft rather than copying posted entries.
  Calculator division must resolve to a whole minor-unit amount.
- **Blockers:** None.
- **Uncommitted:** `P2-21` to `P2-24` implementation and this checkpoint remain
  uncommitted and unpushed.
- **Next:** Review the final diff, then commit and push the completed F-008
  slice.

### 2026-06-12 10:29 WIB - Phase 2 Transaction Metadata Implemented

- **Branch:** `feat/p2-core-income-expense`.
- **Feature IDs:** `P2-17` to `P2-20`.
- **Status:** In progress.
- **Completed:** Added optional merchant/recipient and memo fields to
  transactions, a workspace-scoped `transaction_tags` pivot, model
  relationships, active-resource validation, and metadata propagation through
  the canonical posting service. Updated the transaction form with
  merchant/recipient, memo, tag selection, and the active workspace timezone.
  Transaction input time is interpreted in the workspace timezone and stored
  in UTC. Committed and pushed the preceding `P2-13` to `P2-16` slice in
  `49ad0c7`.
- **Verification:** `composer ci:check` passed with 163 Pest tests and 673
  assertions plus PHPStan, Pint, frontend lint, Prettier, type checks, Vitest,
  and production build. Focused metadata, transaction, and transfer tests pass
  with 24 tests and 132 assertions. Governance, `git diff --check`, Composer
  audit, npm high-severity audit, and 2/2 Playwright smoke tests passed.
  `migrate:fresh --seed` did not run because the sandbox blocked the PostgreSQL
  connection before any database operation.
- **Decisions:** Merchant/recipient is available only for income and expense.
  Memo and tags are available for all current posted entry flows. Tags and
  merchants must be active and belong to the transaction workspace.
  Draft-effective-time editing remains coupled to `P2-23`; this slice covers
  correct workspace-local entry and UTC persistence.
- **Blockers:** None. The in-app browser was unavailable, but Playwright
  acceptance tests passed outside the restricted sandbox.
- **Uncommitted:** Transaction metadata implementation, migrations, tests,
  progress updates, and this checkpoint are uncommitted and unpushed.
- **Next:** Review and commit the focused `P2-17` to `P2-20` slice when
  requested, then continue `F-008` with `P2-21` to `P2-24`.

### 2026-06-12 10:16 WIB - Phase 2 Transfer Workflows Implemented

- **Branch:** `feat/p2-core-income-expense`.
- **Feature IDs:** `P2-13` to `P2-16`, with focused `P2-08`, `P2-11`, and
  `P2-12` posting-integrity hardening.
- **Status:** In progress.
- **Completed:** Added the balanced same-currency transfer workflow with
  optional fee expense leg, normal bank-to-cash withdrawal and
  bank-to-credit-card settlement support, transfer fields in the existing
  transaction form, and posting audit-log recording. Hardened income/expense
  domain validation so workspace, category type, and positive amount
  invariants do not depend only on HTTP validation. Updated the official Phase
  2 and `F-008` progress status to `In Progress`.
- **Verification:** `composer ci:check` passed with 157 Pest tests and 628
  assertions plus PHPStan, Pint, frontend lint, Prettier, type checks, Vitest,
  and production build. Focused transaction, transfer, and audit tests passed
  with 22 tests and 99 assertions. Governance, `git diff --check`, Composer
  audit, npm high-severity audit, and 2/2 Playwright smoke tests passed.
- **Decisions:** Withdrawal and credit-card settlement remain normal transfers
  per the catalog. A transfer fee is represented by an expense-category leg in
  the same transaction, keeping the fee explicitly linked and the complete
  transaction balanced. Cross-currency transfer remains Phase 5 scope and is
  rejected.
- **Blockers:** The in-app browser was unavailable, but Playwright acceptance
  tests passed outside the restricted sandbox.
- **Uncommitted:** Transfer implementation, tests, progress updates, and this
  checkpoint are uncommitted and unpushed.
- **Next:** Review and commit this focused slice when requested, then continue
  `F-008` with `P2-17` to `P2-20`.

### 2026-06-11 19:00 WIB - Phase 2 Core Income/Expense Entry Implemented

- **Branch:** `feat/p2-core-income-expense` (branched from
  `feat/p2-ledger-foundation`, which is fully implemented but not yet PR'd).
- **Feature IDs:** `P2-11`, `P2-12` (start of `F-008`, `P2-11`-`P2-24`).
- **Status:** In progress.
- **Completed:** Implemented the first slice of `F-008`, balanced income and
  expense posting: added migration adding nullable `category_id` (FK to
  `categories`, restrictOnDelete) to `transaction_entries`, placed after
  `account_id` so a future split transaction can carry multiple category legs
  on one transaction; added `LedgerEntryType::Category`,
  `TransactionType::Income`, `TransactionType::Expense`; added
  `TransactionEntry::category()` relation; added generic
  `App\Domain\Ledger\PostTransaction` (the canonical posting service named in
  `docs/ARCHITECTURE.md`'s service boundaries) which validates workspace
  membership and balance-to-zero, locks affected accounts via
  `LockAccountsForPosting`, and posts a `Draft -> Posted` transaction with its
  entries; added thin `App\Domain\Transactions\RecordIncomeExpense` which
  builds the two balanced entries (Income: account `+amount`/category
  `-amount`; Expense: account `-amount`/category `+amount`) and calls
  `PostTransaction::post()`. Added `TransactionPolicy` (mirrors
  `AccountPolicy`), `StoreTransactionRequest` (validates `type`, workspace
  scoped `account_id`/`category_id`, `amount >= 1`, `description`,
  `occurred_at`, plus a `withValidator` check that the category's `type`
  matches the transaction `type`), `TransactionController` (`create`/`store`),
  and routes `transactions.create`/`transactions.store`. Added
  `resources/js/pages/transactions/CreateTransaction.vue` and
  `resources/js/components/transactions/TransactionForm.vue` (mirroring the
  `AccountForm.vue` conventions, with a type/account/category select, amount,
  occurred-at, and description fields). Added an "Add transaction" button to
  `accounts/Index.vue` so the new page is reachable. Existing actions
  (`PostOpeningBalance`, `ReverseTransaction`, `ReplaceTransaction`) were not
  refactored to use `PostTransaction` — out of scope for this slice.
- **Verification:** New `tests/Feature/TransactionRecordingTest.php` (6 tests)
  covering income/expense posting and balance updates, category/type
  mismatch, cross-workspace account/category rejection, amount validation,
  and the create-page Inertia props. Full suite: 145 tests / 576 assertions
  passed. `vendor/bin/pint --dirty --format agent` (auto-fixed minor style in
  the new controller and test). `composer analyse` (Larastan level 6, 0
  errors — fixed a `notIdentical.alwaysTrue` false positive on
  `Category::$type` using the established `getRawOriginal()->value` pattern).
  `npm run lint:check`, `npm run types:check`, and `npm run build` passed.
  `bash scripts/check-governance.sh` passed.
- **Decisions:** Currency for posted entries is taken from the account's
  `currency_code` (not user-supplied), matching `PostOpeningBalance`. A single
  `transactions/create` page with a type select covers both `P2-11` and
  `P2-12`. After posting, redirect to `accounts.index` (no transaction list
  page exists yet — `F-009` is Phase 3 scope).
- **Blockers:** None.
- **Uncommitted:** All changes on `feat/p2-core-income-expense` are
  uncommitted, pending explicit user approval to commit.
- **Next:** After commit approval, continue `F-008` with `P2-13`-`P2-16`
  (transfer, fee, withdrawal, settlement). `feat/p2-ledger-foundation`
  (`F-007`, 3 commits) also still needs a PR.

### 2026-06-11 18:00 WIB - Phase 2 Ledger Foundation Slice 3 Implemented

- **Branch:** `feat/p2-ledger-foundation`.
- **Feature IDs:** `P2-05`, `P2-07` (part of `F-007`, `P2-01`-`P2-10`).
- **Status:** In progress.
- **Completed:** Implemented Slice 3 of 3 for the Phase 2 ledger foundation:
  added a migration adding nullable self-referencing
  `reverses_transaction_id`/`replaces_transaction_id` columns to
  `transactions`; added matching `Transaction` relations (`reverses`,
  `reversal`, `replaces`, `replacement`); added
  `App\Domain\Ledger\LockAccountsForPosting` (sorted `lockForUpdate` over
  affected accounts); added `App\Domain\Ledger\ReverseTransaction` (creates a
  posted reversal transaction with negated entries, marks the original
  `Reversed`, records a `TransactionReversed` audit log) and
  `App\Domain\Ledger\ReplaceTransaction` (creates a posted replacement
  transaction with caller-supplied balanced entries, marks the original
  `Replaced`, records a `TransactionReplaced` audit log). Both run inside
  `DB::transaction()` with workspace-membership authorization.
- **Fixes:** Discovered and fixed a balance-calculation bug introduced by
  Slice 1: filtering ledger entries by `transaction.status === Posted`
  excluded a reversed transaction's original entries (which must remain
  counted, offset by the reversal's negated entries) and double-counted a
  replaced transaction's original entries alongside its replacement.
  `Account::postedLedgerEntries()` and
  `CalculateAccountBalance::calculateAsOf()` now filter by
  `posted_at IS NOT NULL AND status != Replaced`.
- **Verification:** New `tests/Feature/ReverseTransactionTest.php` (4 tests)
  and `tests/Feature/ReplaceTransactionTest.php` (4 tests) pass. Full suite:
  139 tests, 541 assertions pass. `vendor/bin/pint --dirty --format agent`,
  `composer analyse` (Larastan level 6), and `bash scripts/check-governance.sh`
  pass.
- **Decisions:** The reversal/replacement link is stored only on the new
  transaction, not the original, to keep the original's status transition
  status-only per the Slice 1 guard. The new transaction reuses the
  original's `type` and `currency_code`; `TransactionType` is not extended
  (new types are F-008 scope).
- **Blockers:** None.
- **Uncommitted:** All Slice 3 changes are uncommitted on
  `feat/p2-ledger-foundation`, pending user review and explicit commit
  approval.
- **Next:** All 3 slices of `F-007` are implemented. After commit approval,
  open a pull request to `main` for review.

### 2026-06-11 17:15 WIB - Phase 2 Ledger Foundation Slice 2 Implemented

- **Branch:** `feat/p2-ledger-foundation`.
- **Feature IDs:** `P2-08` (part of `F-007`, `P2-01`-`P2-10`).
- **Status:** In progress.
- **Completed:** Implemented Slice 2 of 3 for the Phase 2 ledger foundation:
  added the generic `audit_logs` table migration (`workspace_id`, `actor_id`,
  `action`, `subject_type`, `subject_id`, `metadata`, `created_at`, no
  `updated_at`) per `docs/ARCHITECTURE.md`; added the immutable `AuditLog`
  model (`UPDATED_AT = null`, `metadata` array cast, `action` enum cast,
  `save()`/`delete()` guards); added `AuditAction` enum (`TransactionPosted`,
  `TransactionVoided`, `TransactionReversed`, `TransactionReplaced`); added
  `App\Domain\Audit\RecordAuditLog` service; added
  `Workspace::auditLogs(): HasMany`.
- **Verification:** New `tests/Feature/AuditLogTest.php` (4 tests) passes.
  Full suite: 131 tests, 513 assertions pass. `vendor/bin/pint --dirty
--format agent`, `composer analyse` (Larastan level 6), and
  `bash scripts/check-governance.sh` pass.
- **Decisions:** Audit log infrastructure is not yet called from any domain
  action in this slice; `AuditAction` includes all four lifecycle actions
  upfront so Slice 3's reversal/replacement/posting actions can record entries
  without further enum changes.
- **Blockers:** None.
- **Uncommitted:** All Slice 2 changes are uncommitted on
  `feat/p2-ledger-foundation`, pending user review and explicit commit
  approval.
- **Next:** After commit approval, proceed to Slice 3
  (`ReverseTransaction`/`ReplaceTransaction` domain actions and account-locking
  helper, wired to `RecordAuditLog`).

### 2026-06-11 16:30 WIB - Phase 2 Ledger Foundation Slice 1 Implemented

- **Branch:** `feat/p2-ledger-foundation` (created from up-to-date `main`).
- **Feature IDs:** `P2-03`, `P2-09`, `P2-10` (part of `F-007`, `P2-01`-`P2-10`).
- **Status:** In progress.
- **Completed:** Implemented Slice 1 of 3 for the Phase 2 ledger foundation:
  added `Voided`, `Reversed`, and `Replaced` cases to `TransactionStatus`;
  refined `Transaction::save()`'s immutability guard into a transition matrix
  (terminal states are immutable; `Posted -> Reversed`/`Replaced` and
  `Draft -> Voided` are the only allowed status-only transitions); added
  `Account::postedLedgerEntries()`; fixed `CalculateAccountBalance` to derive
  balances from posted entries only and added `calculateAsOf()` for
  balance-at-date; updated `AccountController` and `AccountGroupController`
  balance sums to use the posted-only relation.
- **Verification:** New tests `tests/Feature/TransactionLifecycleGuardTest.php`
  (8 tests) and `tests/Feature/AccountBalanceCalculationTest.php` (3 tests)
  pass. Regression: `OpeningBalanceLedgerTest` (5 tests) and all `Account*`
  feature tests (17 tests) pass. `vendor/bin/pint --dirty --format agent` and
  `composer analyse` (Larastan level 6) pass.
- **Decisions:** Confirmed with user — `Voided` applies only to
  `Draft -> Voided` (discarded drafts, never hard-deleted); audit log will use
  a generic `audit_logs` table per `docs/ARCHITECTURE.md`; replacement will be
  a single-step `Posted -> Replaced` transition (no intermediate `Reversed`
  state for the original); reversal/replacement domain actions and audit log
  infrastructure are deferred to Slices 2 and 3.
- **Blockers:** None.
- **Uncommitted:** All Slice 1 changes are uncommitted on
  `feat/p2-ledger-foundation`, pending user review and explicit commit
  approval.
- **Next:** After commit approval, plan Slice 2 (generic `audit_logs` table,
  `AuditLog` model, `AuditAction` enum, `RecordAuditLog` service), then Slice 3
  (`ReverseTransaction`/`ReplaceTransaction` domain actions and account-locking
  helper, wired to the audit log).

### 2026-06-11 15:10 WIB - Published Alpha Tracker Pull Request Opened

- **Branch:** `docs/v0.1.0-alpha.1-published`.
- **Feature IDs:** `P1-01` to `P1-34`.
- **Status:** In review.
- **Completed:** Committed and pushed the published release tracker, then opened
  [PR #9](https://github.com/yosrioid/money-manager/pull/9) to `main`.
- **Verification:** The remote annotated tag and GitHub prerelease were
  verified before opening the follow-up PR.
- **Decisions:** This follow-up records publication only and does not move or
  alter the published tag.
- **Blockers:** PR CI is pending.
- **Uncommitted:** This PR-status checkpoint requires a documentation commit and
  push.
- **Next:** Push this checkpoint, verify PR CI, merge PR #9, and synchronize
  local `main`.

### 2026-06-11 15:06 WIB - Phase 1 Internal Alpha Published

- **Branch:** `docs/v0.1.0-alpha.1-published`.
- **Feature IDs:** `P1-01` to `P1-34`.
- **Status:** Completed.
- **Completed:** Merged PR #8, created and pushed annotated tag
  `v0.1.0-alpha.1` at
  `b304c18c1cb8de3fd261bdb035711ac9bc3c99ba`, and published the
  [Phase 1 Internal Alpha GitHub prerelease](https://github.com/yosrioid/money-manager/releases/tag/v0.1.0-alpha.1).
- **Verification:** PR #8 quality and browser CI passed; the published tag
  resolves to the confirmed release-preparation merge commit.
- **Decisions:** The published version remains an internal alpha milestone and
  does not claim MVP or production readiness.
- **Blockers:** None.
- **Uncommitted:** Published release tracker, stored release notes, and this
  checkpoint require a follow-up documentation commit and pull request.
- **Next:** Merge the published-release tracker follow-up, then begin Phase 2
  planning from latest `main`.

### 2026-06-11 15:12 WIB - Phase 1 Internal Alpha Pull Request Opened

- **Branch:** `release/v0.1.0-alpha.1`.
- **Feature IDs:** `P1-01` to `P1-34`.
- **Status:** In review.
- **Completed:** Committed and pushed Phase 1 closure and release preparation,
  then opened
  [PR #8](https://github.com/yosrioid/money-manager/pull/8) to `main`.
- **Verification:** Local release gates passed. GitHub did not create a check
  run for the initial PR-open event; this checkpoint push will trigger a new
  pull-request synchronization event.
- **Decisions:** Do not merge or tag until required PR CI passes.
- **Blockers:** PR CI is pending.
- **Uncommitted:** This PR-status checkpoint requires a documentation commit and
  push.
- **Next:** Push this checkpoint, verify PR CI, merge PR #8, tag the merge
  commit, and publish the prerelease.

### 2026-06-11 15:00 WIB - Phase 1 Internal Alpha Gates Passed

- **Branch:** `release/v0.1.0-alpha.1`.
- **Feature IDs:** `P1-01` to `P1-34`.
- **Status:** Ready for review.
- **Completed:** Completed Phase 1 closure documentation, mandatory-format
  release notes, release tracker preparation, and final release-process target
  clarification.
- **Verification:** Governance, Bash syntax, release/governance Markdown
  formatting, `composer validate --strict`, PHPStan debug analysis, frontend
  lint, formatting, types, Vitest, production build, 116 Pest tests with 467
  assertions, fresh migration and seed rehearsal, Composer audit, npm audit,
  and 2/2 Playwright smoke tests against an isolated PHP 8.5 server passed.
- **Decisions:** The consolidated `composer ci:check` remains unreliable at its
  non-debug PHPStan step in this shell, while PHPStan debug and all remaining
  components pass independently. Pull-request CI is the final merged-content
  gate.
- **Blockers:** None.
- **Uncommitted:** Release preparation is ready to commit, push, and submit for
  review.
- **Next:** Open and merge the release-preparation pull request after CI, then
  tag the merge commit and publish the GitHub prerelease.

### 2026-06-11 14:15 WIB - Phase 1 Internal Alpha Preparation Started

- **Branch:** `release/v0.1.0-alpha.1`.
- **Feature IDs:** `P1-01` to `P1-34`.
- **Status:** In progress.
- **Completed:** Confirmed Phase 1 implementation PRs #4, #5, and #6 are merged
  with successful quality and browser CI. Closed Phase 1 progress status,
  prepared mandatory-format release notes, moved `v0.1.0-alpha.1` to
  `Preparing`, and clarified final target-SHA handling for release drafts.
- **Verification:** Release quality, browser, migration, security, and
  governance gates pending.
- **Decisions:** `v0.1.0-alpha.1` is an internal alpha milestone, not an MVP or
  production-readiness release. It includes the minimum ledger slice for
  opening balances; general ledger workflows remain Phase 2.
- **Blockers:** None.
- **Uncommitted:** Phase closure, release notes, release tracker, process
  clarification, and this checkpoint are uncommitted.
- **Next:** Run applicable release gates, then prepare the release branch for
  review and merge.

### 2026-06-11 10:18 WIB - Release Governance Pull Request Opened

- **Branch:** `docs/release-governance`.
- **Feature IDs:** Engineering only.
- **Status:** In review.
- **Completed:** Committed and pushed the release governance and progress
  tracker, then opened
  [PR #7](https://github.com/yosrioid/money-manager/pull/7) to `main`.
- **Verification:** Commit `537fbff` is pushed to
  `origin/docs/release-governance`; governance, Bash syntax, release/governance
  Markdown formatting, and diff checks passed before the push.
- **Decisions:** This PR prepares release governance only. It does not authorize
  or create a tag or GitHub release.
- **Blockers:** Phase 1 closure and release-preparation documentation remain
  required before `v0.1.0-alpha.1` can become ready.
- **Uncommitted:** This PR-status checkpoint requires a documentation commit and
  push.
- **Next:** Review and merge PR #7, then prepare Phase 1 closure and release
  notes using the new required format.

### 2026-06-11 10:00 WIB - Release Governance And Tracker Prepared

- **Branch:** `docs/release-governance`.
- **Feature IDs:** Engineering only.
- **Status:** Ready for review.
- **Completed:** Defined the mandatory release process, Semantic Versioning and
  annotated-tag rules, release-note format, readiness checklist, and release
  progress tracker. Recorded `v0.1.0-alpha.1` as the planned Phase 1 internal
  alpha candidate and integrated release handling into the AI workflow, master
  plan, Git workflow, strict rules, and governance checks.
- **Verification:** `bash scripts/check-governance.sh`, Bash syntax validation,
  Prettier checks for release and governance Markdown, and `git diff --check`
  passed.
- **Decisions:** Phase milestone prereleases are optional and do not replace
  formal MVP, parity, extended, or Phase 8 release gates. Tag creation and
  GitHub release publication require separate explicit approvals.
- **Blockers:** Phase 1 closure documentation and release preparation remain
  required before `v0.1.0-alpha.1` can become ready.
- **Uncommitted:** Release governance, tracker, integration, and this checkpoint
  are ready to commit and push.
- **Next:** Commit and push the focused branch, then open a pull request to
  `main`.

### 2026-06-10 22:00 WIB - Branded Landing Page And Page Padding Fix

- **Branch:** `feat/p1-welcome-page`.
- **Feature IDs:** `P1-34` (new), plus a styling fix touching `P1-19` to
  `P1-31` pages.
- **Status:** Completed.
- **Completed:**
    - Cataloged a new feature `P1-34` (Public landing page, Phase 1, Public
      Experience) in `docs/FEATURE_CATALOG.md`, added it to the Phase 1 scope
      and PR sequence in `docs/MASTER_PLAN.md`, and added delivery package
      `F-021` plus an active override row in `docs/PROGRESS.md`.
    - Replaced the unmodified Laravel/Inertia starter-kit `Welcome.vue` with a
      branded landing page (Money Manager hero, feature highlights, login and
      register CTAs) using existing Button/Card UI components and theme tokens
      so it matches dark/light mode and the rest of the app.
    - Renamed the leftover "Laravel Starter Kit" sidebar branding in
      `AppLogo.vue` to "Money Manager".
    - Fixed a layout bug where Accounts, Categories, Merchants, and Tags pages
      (index/create/edit) had no padding and content touched the sidebar/edges;
      added `p-4` to match `Dashboard.vue`.
- **Verification:** - `npm run format:check`, `npm run lint:check`, `npm run types:check`,
  `npm run build` → all passed. - `APP_URL=http://localhost:8000 npx playwright test
tests/Browser/welcome.spec.ts` (chromium) → passed, title contains "Money
  Manager". - `bash scripts/check-governance.sh` → passed.
- **Decisions:** Landing page redesign was previously uncataloged scope; added
  as `P1-34` per user request before implementation.
- **Blockers:** None.
- **Uncommitted:** All listed changes, pending commit/push and PR to `main`.
- **Next:** Commit, push `feat/p1-welcome-page`, and open a PR to `main`.

### 2026-06-10 17:00 WIB - Npm Audit Remediation For Concurrently

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** Engineering only.
- **Status:** Completed.
- **Completed:** Bumped the `concurrently` dev dependency from `^9.0.1` to
  `^10.0.3` (approved by user) to resolve 2 critical `shell-quote` advisories
  (GHSA-w7jw-789q-3m8p) reported by `npm audit --audit-level=high`. Verified
  `composer dev` still runs correctly with `concurrently@10` (Node >=22, which
  this environment satisfies).
- **Verification:**
    - `npm install` → 0 vulnerabilities.
    - `php artisan test --compact` → 116 tests, 467 assertions, passed.
    - `composer analyse` → 0 errors.
    - `vendor/bin/pint --test` → passed.
    - `npm run lint:check`, `npm run format:check`, `npm run types:check`,
      `npm run build` → all passed.
    - `npm audit --audit-level=high` → 0 vulnerabilities.
- **Decisions:** None beyond the approved dependency bump.
- **Blockers:** None. The previously noted npm audit blocker is resolved.
- **Uncommitted:** `package.json` and `package-lock.json` (concurrently bump),
  `docs/PROGRESS.md`, `docs/WORKLOG.md`.
- **Next:** Commit and push this fix; the branch is then ready for review and
  merge for `P1-09` to `P1-33`.

### 2026-06-10 16:12 WIB - Phase 1 Implementation Checkpoint Pushed

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-01` to `P1-33`.
- **Status:** In progress; all features and functional testing complete,
  security remediation, review, and merge pending.
- **Completed:** Committed and pushed the complete Phase 1 implementation
  checkpoint in commit `239b78b`.
- **Verification:** The pushed checkpoint records 116 passing Pest tests with
  467 assertions, passing PHPStan debug analysis, frontend checks, production
  build, governance, Composer audit, and 2/2 Playwright smoke tests.
- **Decisions:** Phase 1 remains `In Progress`, not `Done`, because the npm
  security finding requires an approved dependency change and the branch still
  requires review and merge.
- **Blockers:** `npm audit --audit-level=high` reports two critical
  vulnerabilities through `concurrently -> shell-quote`.
- **Uncommitted:** This push-status checkpoint requires a documentation commit
  and push.
- **Next:** Commit and push this checkpoint, then remediate the npm security
  finding after dependency-change approval.

### 2026-06-10 16:07 WIB - Phase 1 Feature Implementation Complete

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-01` to `P1-33`, including the final `P1-21` ledger
  integration and final acceptance review of the complete Phase 1 catalog.
- **Status:** In progress; all Phase 1 features and functional testing are
  complete, while security remediation, review, and merge remain pending.
- **Completed:** Implemented balanced immutable ledger-backed opening balances
  with atomic account creation, account locking, workspace authorization,
  derived balances, and immutable posted entries. Closed final category
  appearance and safe one-level parent-change gaps found during the Phase 1
  acceptance audit.
- **Verification:** Full Pest suite passed with 116 tests and 467 assertions;
  focused financial/reference-data suites passed; PHPStan passed in debug mode;
  Pint, ESLint, Prettier, TypeScript, Vitest, production build, governance, and
  `git diff --check` passed. Playwright passed 2/2 on Chromium and mobile Safari
  against an isolated PHP 8.5 and SQLite server. Composer audit reported no
  vulnerabilities.
- **Decisions:** `P1-21` uses the minimum approved ledger slice: one posted
  `opening_balance` transaction with balanced account and opening-balance
  equity entries. General posting and reversal workflows remain Phase 2.
- **Blockers:** `npm audit --audit-level=high` reports two critical
  vulnerabilities through the development dependency chain
  `concurrently -> shell-quote`; dependency changes require explicit approval.
  The consolidated `composer ci:check` command cannot complete in this
  constrained shell because its non-debug PHPStan process exits without
  diagnostics, although the same PHPStan analysis passes with `--debug` and all
  remaining CI components pass independently.
- **Uncommitted:** All final ordering, application-lock, starter-preset,
  P1-21 ledger, category-safety, tests, architecture, progress, and worklog
  changes are ready for the explicitly requested commit and push.
- **Next:** Commit and push this Phase 1 implementation checkpoint, then obtain
  approval to remediate the npm audit finding before review and merge.

### 2026-06-10 15:44 WIB - Application Lock And Starter Presets Implemented

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-32`, `P1-33`.
- **Status:** In progress.
- **Completed:** Added configurable workspace inactivity locking with password
  confirmation unlock, workspace settings UI and validation, registration-time
  starter financial preset opt-in, an idempotent transactional preset domain
  action, and expanded registration, isolation, and preset coverage.
- **Verification:** `composer ci:check` passed with 110 tests and 425
  assertions; the focused application-lock, workspace-preference,
  registration, and preset suite passed with 24 tests and 94 assertions.
  PHPStan, Pint, governance, frontend lint, Prettier, type-check, unit tests,
  production build, route middleware inspection, and `git diff --check`
  passed.
- **Decisions:** Application lock is disabled by default and supports 5, 15,
  30, or 60-minute workspace inactivity periods. Starter presets are optional,
  workspace-scoped, editable reference data and do not create ledger entries.
- **Blockers:** `P1-21` cannot meet its acceptance criteria until the Phase 2
  ledger posting foundation exists. Playwright remains blocked by the macOS
  sandbox, and dependency audits remain blocked by unavailable registry DNS.
- **Uncommitted:** Ordering, application-lock, starter-preset, tests, progress,
  and worklog changes are uncommitted and unpushed.
- **Next:** Review and commit the completed Phase 1 non-ledger slices when
  requested, then implement the Phase 2 ledger foundation before closing
  ledger-backed opening balances.

### 2026-06-10 15:34 WIB - Reference Data Ordering Implemented

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-19`, `P1-22`, `P1-26` to `P1-29`.
- **Status:** In progress.
- **Completed:** Added transactional move-up/move-down ordering for account
  groups, accounts within their current group, and categories within matching
  type/parent siblings. Added authorized PATCH endpoints, accessible Wayfinder
  controls, sibling-aware initial positions, end-of-scope placement after
  parent/group changes, and focused ordering/isolation tests.
- **Verification:** `composer ci:check` passed with 102 tests and 390
  assertions; focused ordering/account/category tests passed with 15 tests and
  90 assertions; PHPStan, Pint, governance, frontend lint, Prettier,
  type-check, unit tests, production build, route inspection, and
  `git diff --check` passed.
- **Decisions:** Ordering swaps adjacent persisted positions inside a database
  transaction with row locks. Accounts only move within their current group;
  categories only move within the same workspace, type, and parent.
- **Blockers:** Browser verification remains blocked because the macOS sandbox
  denies Chromium and WebKit process startup. Composer and npm audits could not
  reach their registries because DNS/network access is unavailable.
- **Uncommitted:** Ordering implementation, tests, progress, and this checkpoint
  are uncommitted and unpushed.
- **Next:** Review this ordering slice, then continue `P1-32` application lock
  and the remaining `P1-33` starter-preset UI/test coverage.

### 2026-06-10 09:00 WIB - Reference Data Checkpoint Pushed

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-26` to `P1-31`.
- **Status:** In progress.
- **Completed:** Committed and pushed the verified category, merchant, and tag
  management slice in commit `1f9ce85`.
- **Verification:** Remote branch `origin/feat/p1-financial-setup` advanced
  through implementation commit `1f9ce85` and worklog commit `ccbefac`.
- **Decisions:** Phase 1 remains in progress. Ordering, application lock,
  ledger-backed opening balance, review, and browser verification remain.
- **Blockers:** Browser verification remains blocked in the current sandbox.
- **Uncommitted:** None.
- **Next:** Continue the remaining Phase 1 work in focused slices.

### 2026-06-10 08:57 WIB - Category Management Slice Implemented

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-26` to `P1-29`.
- **Status:** In progress.
- **Completed:** Added category management UI and navigation, income and
  expense sections, one-level subcategory support, workspace/type/top-level
  parent validation, active-resource filtering, non-destructive leaf archive,
  and focused behavior/isolation tests.
- **Verification:** `composer ci:check` passed with 97 tests and 358 assertions;
  the focused category/reference/account/preset suite passed with 15 tests and
  92 assertions; PHPStan, Pint, governance, frontend lint, Prettier,
  type-check, production build, and `git diff --check` passed.
- **Decisions:** Keep `P1-26` to `P1-29` in progress because category ordering
  remains pending. Stop before beginning application-lock or ordering work.
- **Blockers:** Browser verification remains blocked in the current sandbox.
- **Uncommitted:** Category, merchant, tag, tests, and documentation changes are
  uncommitted and unpushed.
- **Next:** Review and commit the combined reference-data slice when requested,
  then implement account/category ordering as a focused follow-up.

### 2026-06-10 08:52 WIB - Merchant And Tag Slice Implemented

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-30`, `P1-31`.
- **Status:** In progress.
- **Completed:** Added merchant and tag management pages and sidebar
  navigation, workspace-scoped merchant default-category validation,
  non-destructive archive behavior, and focused behavior/isolation tests.
- **Verification:** `composer ci:check` passed with 93 tests and 335 assertions;
  the focused merchant/tag, account, and preset suite passed with 11 tests and
  69 assertions; PHPStan, Pint, governance, frontend lint, Prettier,
  type-check, production build, and `git diff --check` passed.
- **Decisions:** Keep `P1-30` and `P1-31` in progress until review and browser
  verification. Stop before beginning the larger category-management slice.
- **Blockers:** Browser verification remains blocked in the current sandbox.
- **Uncommitted:** Merchant/tag implementation, tests, and this checkpoint are
  uncommitted and unpushed.
- **Next:** Review and commit this focused slice when requested, then implement
  the `P1-26` to `P1-29` category-management UI and tests.

### 2026-06-10 08:42 WIB - Account Management Checkpoint Pushed

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-19` to `P1-25`, with a focused `P1-33` seeder fix.
- **Status:** In progress.
- **Completed:** Committed and pushed the verified account-management slice in
  commit `9db27cb`.
- **Verification:** Remote branch `origin/feat/p1-financial-setup` advanced
  through implementation commit `9db27cb` and worklog commit `df0bf81`.
- **Decisions:** Phase 1 remains in progress; do not mark it complete until the
  remaining account ordering, ledger-backed opening balance, and
  `P1-26` to `P1-33` frontend and test work are complete.
- **Blockers:** Browser verification remains blocked in the current sandbox.
- **Uncommitted:** None.
- **Next:** Continue the remaining Phase 1 work incrementally.

### 2026-06-10 08:38 WIB - Account Management Slice Checkpoint

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-19` to `P1-25`, with a focused `P1-33` seeder fix.
- **Status:** In progress.
- **Completed:** Added account-group and account Inertia pages, shared account
  form, sidebar navigation, workspace-scoped account-group validation,
  non-destructive archive behavior, explicit personal-workspace financial
  defaults, and deterministic starter-preset category positions.
- **Verification:** `composer ci:check` passed with 89 tests and 303 assertions;
  focused Phase 1 tests passed with 18 tests and 86 assertions; PHPStan, Pint,
  governance, frontend lint, Prettier, type-check, production build, and
  `git diff --check` passed.
- **Decisions:** Keep `P1-19` to `P1-25` in progress. Account ordering and
  ledger-backed opening-balance posting remain required before completion.
  Do not begin the `P1-26` to `P1-33` frontend slice until this checkpoint is
  reviewed.
- **Blockers:** In-app browser was unavailable. `npm run test:e2e` could not
  launch Chromium or WebKit because the macOS sandbox denied browser process
  startup.
- **Uncommitted:** This account-management slice and checkpoint are uncommitted
  and unpushed.
- **Next:** Review the account-management diff, add account/group ordering and
  resolve the Phase 2 opening-balance integration boundary, then rerun browser
  verification in an environment that permits browser startup.

### 2026-06-09 22:00 WIB - P1-12 to P1-18 Implemented; P1-19 to P1-33 Backend Ready

- **Branch:** `feat/p1-financial-setup`.
- **Feature IDs:** `P1-12` to `P1-33`.
- **Status:** In Progress.
- **Completed:**
    - Added preference columns to workspaces table (`default_currency`, `timezone`, `locale`,
      `number_format`, `first_day_of_week`, `month_start_day`, `adjust_month_for_weekend`).
    - Created `currencies` table with 25 major currencies seeded via `CurrencySeeder`.
    - Updated `Workspace` model with preference fillable fields, casts, and hasMany relationships.
    - Updated `WorkspaceFactory` to include preference column defaults.
    - Created `WorkspaceController` (settings) with `edit` and `update` actions.
    - Created `WorkspacePreferencesRequest` with validation for all preference fields.
    - Added `settings/workspace` GET/PATCH routes under `auth + verified + workspace` middleware.
    - Created `settings/Workspace.vue` Inertia page.
    - Added `AuthorizesRequests` trait to the base `Controller` class.
    - Created full backend for P1-19 to P1-33 (no Vue pages yet):
        - Migrations: `account_groups`, `accounts`, `categories`, `merchants`, `tags`.
        - Enums: `AccountType`, `CategoryType`.
        - Models + factories: `AccountGroup`, `Account`, `Category`, `Merchant`, `Tag`.
        - Policies: `AccountGroupPolicy`, `AccountPolicy`, `CategoryPolicy`, `MerchantPolicy`, `TagPolicy`.
        - Controllers: `AccountGroupController`, `AccountController`, `CategoryController`,
          `MerchantController`, `TagController`.
        - Form requests for store/update on all five resource types.
        - All routes registered under `auth + verified + workspace` middleware.
        - `StarterPresetsSeeder` for workspace-scoped default accounts and categories.
    - 7 new workspace preferences tests added (82 total, all passing).
- **Verification:** `php artisan test --compact` → 82/82 passed; `vendor/bin/pint --dirty` → clean.
- **Decisions:**
    - Authorization for workspace update handled in controller via `$this->authorize('update', $workspace)`
      rather than in form request (route has no `workspace` parameter).
    - CurrencySeeder must be called in test `beforeEach` when testing routes that validate `currency_code`.
- **Blockers:** None.
- **Uncommitted:** All changes on `feat/p1-financial-setup` are uncommitted.
- **Next:** Commit and push this checkpoint. Vue pages and tests for P1-19 to P1-33 in next session.

### 2026-06-09 15:35 WIB - Pull Request Quality Gates Passed

- **Branch:** `feat/p1-personal-workspace`.
- **Feature IDs:** `P1-09`, `P1-10`, `P1-11`.
- **Status:** Ready for review.
- **Completed:** Confirmed both the quality and browser jobs passed on PR #4
  after the implementation and PR-status commits were pushed.
- **Verification:** GitHub Actions run
  [#27191678328](https://github.com/yosrioid/money-manager/actions/runs/27191678328)
  passed; the PR is open and mergeable.
- **Decisions:** None.
- **Blockers:** None.
- **Uncommitted:** This final CI checkpoint requires a documentation commit and
  push.
- **Next:** Push this checkpoint, verify the resulting docs-only CI run, then
  hand PR #4 to review without merging it.

### 2026-06-09 15:30 WIB - Personal Workspace Pull Request Opened

- **Branch:** `feat/p1-personal-workspace`.
- **Feature IDs:** `P1-09`, `P1-10`, `P1-11`.
- **Status:** Ready for review.
- **Completed:** Committed and pushed the reviewed vertical slice, then opened
  [PR #4](https://github.com/yosrioid/money-manager/pull/4) against `main`.
- **Verification:** Local quality gates and security audits passed before the
  PR; commit authorship and message contain no prohibited attribution.
- **Decisions:** Keep `F-003` in progress because the complete authentication
  package spans `P1-01` through `P1-11`; only `P1-09` through `P1-11` are in
  review in PR #4.
- **Blockers:** None.
- **Uncommitted:** Progress and worklog need a documentation commit recording
  PR #4 and the `In Review` milestone status.
- **Next:** Commit and push this PR-status checkpoint, then verify PR CI.

### 2026-06-09 15:22 WIB - Personal Workspace Slice Ready For Review

- **Branch:** `feat/p1-personal-workspace`.
- **Feature IDs:** `P1-09`, `P1-10`, `P1-11`.
- **Status:** Ready for review.
- **Completed:** Reviewed the complete workspace vertical slice and confirmed
  registration, membership, active-context, policy, frontend shared-prop, and
  documentation changes are focused on the approved feature IDs.
- **Verification:** `composer ci:check` passed with 47 tests and 171 assertions;
  `npm run test:e2e` passed on Chromium and mobile Safari; Composer and npm
  security audits reported no vulnerabilities; governance, formatting, route
  middleware, and diff checks passed.
- **Decisions:** The dashboard is the first workspace-required route. All future
  financial routes must use the `workspace` middleware and authorize their
  workspace-owned resources.
- **Blockers:** None.
- **Uncommitted:** The reviewed vertical slice and current checkpoints are ready
  to commit on the feature branch.
- **Next:** Commit the focused change, push the feature branch, create a pull
  request, then record the PR in progress tracking.

### 2026-06-09 15:10 WIB - Personal Workspace Vertical Slice Implemented

- **Branch:** `feat/p1-personal-workspace`.
- **Feature IDs:** `P1-09`, `P1-10`, `P1-11`.
- **Status:** In progress.
- **Completed:** Implemented workspace ownership and membership schema, atomic
  personal-workspace creation during registration, scoped active-workspace
  context, dashboard middleware, Inertia workspace summary, authorization
  policy, factories, enum, and focused isolation tests.
- **Verification:** Focused workspace, dashboard, and registration tests passed
  with 12 tests and 42 assertions. Frontend lint, formatting, and type checks,
  governance checks, route middleware inspection, and PHPStan debug analysis
  passed.
- **Decisions:** Active workspace resolution requires a valid membership;
  ownership alone does not bypass membership. Workspace management remains
  owner-only while members may view an authorized workspace.
- **Blockers:** None.
- **Uncommitted:** Complete vertical-slice implementation and documentation are
  uncommitted on the feature branch.
- **Next:** Run the full CI quality gate, review the complete diff, then prepare
  the branch for review.

### 2026-06-09 14:40 WIB - Personal Workspace Design Ready

- **Branch:** `feat/p1-personal-workspace`.
- **Feature IDs:** `P1-09`, `P1-10`, `P1-11`.
- **Status:** In progress.
- **Completed:** Audited registration, authentication routes, current models,
  middleware stack, factories, and test conventions. Defined workspace owner,
  membership, active-workspace reference, domain action, middleware, and policy
  contracts.
- **Verification:** Confirmed the branch starts from merged `origin/main`;
  inspected Fortify registration and current route middleware behavior.
- **Decisions:** Registration will atomically create a user, personal workspace,
  owner membership, and current workspace. Active context must verify membership
  rather than trusting `users.current_workspace_id`.
- **Blockers:** None.
- **Uncommitted:** Progress and worklog now mark `P1-09` to `P1-11` in progress.
- **Next:** Generate and implement workspace migrations, models, domain action,
  middleware, policy, and focused isolation tests.

### 2026-06-09 14:31 WIB - Governance Baseline Ready For Review

- **Branch:** `docs/master-delivery-plan`.
- **Feature IDs:** `P0-06`.
- **Status:** Ready for review.
- **Completed:** Completed final review of the authoritative delivery plan,
  feature catalog, cross-AI startup instructions, daily checkpoint protocol,
  strict governance rules, and automated CI validation.
- **Verification:** `composer ci:check`, `bash scripts/check-governance.sh`,
  Bash syntax validation, workflow YAML parsing, Prettier checks, feature-ID
  traceability checks, and `git diff --check` passed.
- **Decisions:** The governance baseline is complete enough to start Phase 1;
  future rules should be added only for concrete new risks, tools, decisions,
  or scope changes.
- **Blockers:** None.
- **Uncommitted:** Governance baseline changes are ready to be committed,
  pushed, and submitted through a pull request after the explicit user request.
- **Next:** Commit the focused governance baseline, push
  `docs/master-delivery-plan`, create a PR to `main`, and verify CI.

### 2026-06-09 14:28 WIB - Daily Checkpoint And Handoff Protocol

- **Branch:** `docs/master-delivery-plan`.
- **Feature IDs:** `P0-06`.
- **Status:** In progress.
- **Completed:** Added the append-only daily worklog, mandatory session
  checkpoint protocol, startup reading requirement, PR checklist coverage, and
  CI governance validation for handoff availability.
- **Verification:** `bash -n scripts/check-governance.sh`,
  `bash scripts/check-governance.sh`, Prettier checks, `composer ci:check`,
  workflow YAML parsing, and `git diff --check` passed during this session.
- **Decisions:** `docs/PROGRESS.md` tracks official feature and phase status;
  `docs/WORKLOG.md` tracks daily details, partial work, and session handoff.
  Checkpoints are required throughout long work because abrupt termination
  cannot guarantee a final write.
- **Blockers:** None.
- **Uncommitted:** All delivery-plan, governance, cross-AI instruction,
  checkpoint protocol, and CI validation changes remain uncommitted and
  unpushed.
- **Next:** Review the final diff, then commit and push
  `docs/master-delivery-plan` only after explicit user approval.

### 2026-06-09 14:20 WIB - Delivery Governance And AI Startup Baseline

- **Branch:** `docs/master-delivery-plan`.
- **Feature IDs:** `P0-06`.
- **Status:** In progress.
- **Completed:** Defined the master delivery plan, 167-feature catalog,
  cross-tool AI startup entrypoints, centralized AI workflow, strict scope
  controls, and automated governance validation.
- **Verification:** `composer ci:check`, `bash scripts/check-governance.sh`,
  workflow YAML parsing, Prettier checks, and `git diff --check` passed.
- **Decisions:** Every supported AI entrypoint points to
  `docs/AI_WORKFLOW.md`; daily operational checkpoints belong in this worklog,
  while official feature and phase status remains in `docs/PROGRESS.md`.
- **Blockers:** None.
- **Uncommitted:** All delivery-plan, governance, AI instruction, and worklog
  changes remain uncommitted and unpushed on `docs/master-delivery-plan`.
- **Next:** Review the final governance diff, then commit and push only after
  explicit user approval.
