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

### 2026-06-14 20:10 WIB - P5-01 Full Suite Verified

- **Branch:** `feat/phase-5`.
- **Feature IDs:** `P5-01`.
- **Status:** Completed.
- **Completed:** Resolved a full-suite-only failure in `FavoriteAccountsAndCategoriesTest::an account can be marked and unmarked as a favorite` that surfaced after the `UpdateAccountRequest` `credit_limit` validation was added. Cause: `Account::factory()` could randomly produce a `credit_card` type account; the favorite test's `accounts.update` PATCH omits `credit_limit`, which the new validator now rejects for `credit_card` accounts, breaking the expected redirect. Fix: `AccountFactory::definition()` now excludes `AccountType::CreditCard` from its random `type` selection (generic factory accounts default to `credit_limit => null`); dedicated `creditCard()` state remains for tests that need a credit-card account.
- **Verification:** `php artisan test --compact` (full suite) passed: 323 tests, 2515 assertions. `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` passed (0 errors). `vendor/bin/pint --dirty --format agent` passed (no changes needed).
- **Decisions:** None.
- **Blockers:** None.
- **Uncommitted:** All of `P5-01` (migration, model, requests, controller, factory, Vue form/index, tests, doc updates) remains uncommitted on `feat/phase-5`, pending user approval to commit/push.
- **Next:** Awaiting approval to commit `P5-01`, then continue the `P5-01`-`P5-05` group with `P5-02` (billing cycle - closing/payment dates determining statement periods for credit-card accounts).

### 2026-06-14 19:30 WIB - Phase 5 Started: P5-01 Credit-Card Account Model Implemented

- **Branch:** `feat/phase-5`.
- **Feature IDs:** `P5-01`.
- **Status:** Completed.
- **Completed:** Started Phase 5 (Cards, Debt, Assets, And Multi-Currency) following `v0.3.0-beta.1`'s publication. Implemented `P5-01` (credit-card account model): added a nullable `credit_limit` bigInteger column (minor units) to `accounts` via migration. `StoreAccountRequest`/`UpdateAccountRequest` gained an `after()` check requiring `credit_limit` when `type` is `credit_card` and rejecting it for every other type; `AccountController::update` clears `credit_limit` to `null` when an account's type changes away from `credit_card`. `AccountForm.vue` now tracks the selected type reactively (`v-model` + `ref`) and only shows the "Credit limit in minor units" field for `credit_card` accounts. `accounts/Index.vue` shows credit-card accounts with "Outstanding balance" (`-balance` when negative, else 0 - following liability semantics, since existing expense postings already make a credit-card account's ledger balance more negative as debt accrues) and, when a limit is set, "Available credit" (`credit_limit - outstanding`), instead of the generic "Ledger balance" used by other account types. Added `AccountFactory::creditCard()` state. Updated `docs/PROGRESS.md`: Current Milestone now targets Phase 5 (`P5-01`-`P5-16`, In Progress); Phase 5 and `F-013` rows changed from `Planned` to `In Progress`; added a `P5-01` row to the Catalog Status Summary and Active Feature Overrides table.
- **Verification:** `php artisan test --compact --filter="AccountManagementTest|AccountBalanceCalculationTest"` passed: 13 tests, 83 assertions (3 new tests: credit-card creation requires `credit_limit`, `credit_limit` rejected for non-credit-card types, changing type away from `credit_card` clears `credit_limit`; plus a new balance-calculation test asserting the index exposes `balance` and `credit_limit` for a credit-card account with a posted expense). `vendor/bin/phpstan analyse --no-progress --memory-limit=512M` (full app) passed (0 errors). `npm run types:check` (`vue-tsc --noEmit`) passed. `vendor/bin/pint --dirty --format agent` applied (no changes needed).
- **Decisions:** No new transaction/posting logic was needed for liability semantics - existing income/expense posting already makes a credit-card account's ledger balance negative as charges accrue (same mechanism as any other expense-funding account), so "outstanding balance" is simply the negated ledger balance and "available credit" is `credit_limit - outstanding`. Scoped `P5-01` strictly to the account model/limit and its display; billing cycles (`P5-02`), statement balances (`P5-03`), and settlement (`P5-04`) are separate features.
- **Blockers:** None.
- **Uncommitted:** All of `P5-01` (migration, model, requests, controller, factory, Vue form/index, tests, doc updates) is uncommitted on `feat/phase-5`, pending user approval to commit/push.
- **Next:** Continue the `P5-01`-`P5-05` group: `P5-02` (billing cycle - closing/payment dates determining statement periods for credit-card accounts).

### 2026-06-14 18:00 WIB - Phase 4 Merged; Preparing `v0.3.0-beta.1` Release

- **Branch:** `docs/v0.3.0-beta.1-release-prep`.
- **Feature IDs:** `P4-01` to `P4-25` (release preparation only; no scope change).
- **Status:** In progress.
- **Completed:** Reviewed the Phase 4 PR (#16): fixed a CSV/formula-injection issue in `GenerateTransactionsCsv::writeTransaction` (escape leading `=`, `+`, `-`, `@`, tab, CR on text fields, covering both `stream()` and the queued `writeToFile()` path) with a regression test, and rewrote the 5 `feat/phase-4` commit messages to remove `Co-Authored-By` AI-attribution trailers per `docs/STRICT_RULES.md` (force-pushed with `--force-with-lease`). Merged PR #16 into `main` (`5e27e89`). Updated `docs/PROGRESS.md`: Phase 4 status changed from `In Progress` to `Done`, and the Current Milestone section now points at Phase 4 (`P4-01`-`P4-25`, completed 2026-06-14). Re-ran the full release gate suite against `main` at `5e27e89`.
- **Verification:** `composer ci:check` passed (Pint, PHPStan/Larastan 0 errors, Pest 319 tests / 2492 assertions, ESLint, Prettier, vue-tsc, Vitest, production build). `bash scripts/check-governance.sh` passed. `composer audit` and `npm audit --audit-level=high` both report 0 vulnerabilities. Fresh `php artisan migrate:fresh --seed --force` rehearsal against an isolated SQLite database (`/tmp/release-rehearsal.sqlite`, not the dev Postgres database) passed. `npx playwright test` passed 2/2 (Chromium and mobile Safari).
- **Decisions:** Chose `v0.3.0-beta.1` (Phase 3 private beta, never previously published) as the release candidate rather than `v1.0.0-rc.1`, since the roadmap in `docs/RELEASE_PROGRESS.md` explicitly gates `v1.0.0-rc.1` on "Phase 4 and applicable Phase 8 gates" and Phase 8 has not started. `v0.3.0-beta.1`'s scope now covers both Phase 3 (`P3-01`-`P3-21`) and Phase 4 (`P4-01`-`P4-25`), since both are `Done` on `main` and neither has been released yet.
- **Blockers:** None.
- **Uncommitted:** `docs/PROGRESS.md` and `docs/WORKLOG.md` updates on `docs/v0.3.0-beta.1-release-prep`, plus a pending `docs/RELEASE_PROGRESS.md` update with release notes for `v0.3.0-beta.1`.
- **Next:** Finish updating `docs/RELEASE_PROGRESS.md` (candidate, checklist, release notes), open and merge the release-preparation PR, confirm the merge commit is the latest `main`, then seek explicit approval before creating the `v0.3.0-beta.1` tag and before publishing the GitHub prerelease.

### 2026-06-14 16:30 WIB - P4-25 Queued Large CSV Export Implemented (Phase 4 Feature-Complete)

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-25`.
- **Status:** Completed.
- **Completed:** Extracted the filter-resolution/query-building logic shared by `transactions.index` and `transactions.export` into `App\Domain\Transactions\FilterTransactionsQuery` (`resolve()` reads filters from the request via `input()` so it works for both GET query strings and POST bodies, `query()` builds the filtered `HasMany` query, and `serialize()`/`hydrate()` round-trip the `from`/`to` Carbon dates through JSON for storage). `TransactionController` now delegates to this service and no longer has its own `resolveTransactionFilters()`/`filteredTransactionsQuery()`. `App\Domain\Export\GenerateTransactionsCsv` gained `writeToFile()` (shares the header/`chunkById` writer with `stream()`) for writing the CSV directly to disk. Added migration for `transaction_exports` (`workspace_id`, `created_by`, `status`, `filters` JSON, `file_path`, `failed_reason`, `ready_at`), `App\Enums\TransactionExportStatus`, `App\Models\TransactionExport`, `Workspace::transactionExports()`, and `App\Policies\TransactionExportPolicy`. New `App\Jobs\GenerateTransactionsExportFile` (queued, `ShouldQueue`): loads the export, re-checks that its creator is still a workspace member ("authorize again inside jobs," failing the export with `failed_reason` if not), transitions `pending` -> `processing` -> `ready`/`failed`, hydrates the stored filters, and writes the CSV to `storage/app/private/exports/{workspace_id}/{uuid}.csv`. New `TransactionExportController` with routes `transactions.exports.index`/`store`/`download` and a `transactions/Exports.vue` page (status badges, download link once ready), linked via new "Queue export"/"Exports" buttons on `transactions/Index.vue`. Regenerated Wayfinder routes, ran the new migration.
- **Verification:** `vendor/bin/phpstan analyse --no-progress --memory-limit=512M` (full app) passed (0 errors). `php artisan test --compact` (full suite) passed: 318 tests, 2491 assertions, including new `tests/Feature/TransactionExportQueueTest.php` (5 tests: guest redirects, queue-and-download happy path with filter persistence and CSV content, download blocked until `ready`, job fails the export and leaves `file_path` null when the creator is no longer a workspace member, cross-workspace download returns 404). `npx eslint`/`npx prettier --write` on changed/new Vue files passed with no issues; `vendor/bin/pint --dirty --format agent` applied. `composer ci:check` (lint, format, types, unit, analyse, build, full test suite) and `bash scripts/check-governance.sh` both passed.
- **Decisions:** `QUEUE_CONNECTION=sync` in `phpunit.xml` means the queued job runs synchronously and inline during feature tests (no `Queue::fake()` needed for the happy-path test); the re-authorization-failure test instead constructs the job directly and calls `handle()` after removing the creator's workspace membership. `FilterTransactionsQuery::resolve()` reads filters via `$request->input()` (not `query()`) so the same method works for the GET-with-query-string history/export endpoints and the POST-with-body queued-export endpoint.
- **Blockers:** None.
- **Uncommitted:** All of Phase 4 (`P4-01`-`P4-25`, Groups 1-3) remains uncommitted on `feat/phase-4`, pending user approval to commit/push.
- **Next:** Phase 4 (`P4-01`-`P4-25`) is feature-complete and `F-012` is marked Done in `docs/PROGRESS.md`. Awaiting user direction to commit/push and prepare Phase 4 for review (per the standing rule requiring explicit approval before any commit).

### 2026-06-14 14:45 WIB - P4-22/P4-23/P4-24 Transaction Import Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-22`, `P4-23`, `P4-24`.
- **Status:** Completed.
- **Completed:** Added structured, previewable, idempotent transaction import (income/expense rows only for v1; transfers are out of scope). New domain classes: `App\Domain\Import\ParseTransactionImportFile` (parses CSV/`.xlsx`/`.xls` via `phpoffice/phpspreadsheet` into header-keyed rows), `App\Domain\Import\ValidateTransactionImportRows` (resolves account/category/merchant/tags by name, validates date/type/amount/currency, computes a deterministic `import:<sha256>` idempotency key per row), and `App\Domain\Import\ImportTransactions` (calls `RecordIncomeExpense` per valid row, skipping rows whose idempotency key already exists for the workspace). New `ImportController` with routes `imports.transactions.create` (GET, upload form), `imports.transactions.preview` (POST, stores the upload under `storage/app/private/imports/{workspace_id}/{uuid}.{ext}` and returns a per-row preview with a `token`), and `imports.transactions.store` (POST `token`, re-validates and imports, then deletes the temp file and flashes an `imported`/`duplicated`/`invalid` summary toast). New `imports/Transactions.vue` page (file upload, preview table with status badges/errors, confirm button), linked via an "Import" button on `transactions/Index.vue`. Regenerated Wayfinder routes.
- **Verification:** `vendor/bin/phpstan analyse --no-progress --memory-limit=512M` (full app) passed (0 errors). `php artisan test --compact` (full suite) passed: 313 tests, 2470 assertions, including new `TransactionImportTest` (3 tests: guest redirect, preview validation/resolution including an unknown-account error row, and confirm-import + idempotent retry producing no duplicate transactions). `npx eslint`/`npx prettier --write` on changed/new Vue files passed with no issues; `vendor/bin/pint --dirty --format agent` applied.
- **Decisions:** Scoped the v1 import format to income/expense rows only (mirroring the CSV export columns minus `Status`, with `Amount` as a positive minor-unit integer and `Type` determining the sign). Transfers require pairing two rows and were deferred as a documented limitation rather than adding ad hoc pairing logic. Idempotency reuses the existing `PostTransaction` idempotency-key mechanism (already used by manual transaction creation); `ImportTransactions` additionally pre-checks for an existing transaction with the same key so retried imports report `duplicated` counts instead of relying on `PostTransaction`'s match-or-throw behavior.
- **Blockers:** None.
- **Uncommitted:** All `P4-22`/`P4-23`/`P4-24` changes (new domain classes, controller, requests, routes, Vue page, test, generated Wayfinder routes/actions, doc updates) remain uncommitted on `feat/phase-4`, alongside the previously uncommitted `P4-20`/`P4-21` work, per the standing rule requiring explicit user approval before any commit.
- **Next:** Continue Group 3: `P4-25` (asynchronous large export via an authorized queue job, per `docs/MASTER_PLAN.md`'s "Queue large exports/imports and authorize again inside jobs"). After `P4-25`, Phase 4 (`P4-01`-`P4-25`) is feature-complete; run `composer ci:check` and `bash scripts/check-governance.sh`, update `F-012` to Done in `docs/PROGRESS.md`, and prepare Phase 4 for review.

### 2026-06-14 13:15 WIB - P4-21 Excel Report Export Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-21`.
- **Status:** Completed.
- **Completed:** Added `App\Domain\Export\GenerateReportSpreadsheet` (using `phpoffice/phpspreadsheet`'s `Spreadsheet`/`Xlsx` writer, streamed via `response()->streamDownload()`). `ReportController::export` (route `reports.export`) builds `forMonth()`: a 5-sheet workbook (Summary, Categories, Merchants, Accounts, Net worth) for the selected billing month, reusing `GenerateTransactionReport` and `CalculateNetAsset` with the same filters as `reports.index`. `TransactionController::exportYear` (route `transactions.monthly.export`) builds `forYear()`: a single "Monthly summary" sheet from `SummarizeTransactionPeriod::forYear()` (one row per month with activity). Added "Export Excel" buttons to `reports/Index.vue` (preserving month + filters) and `transactions/Monthly.vue` (preserving year). Regenerated Wayfinder routes.
- **Verification:** `vendor/bin/phpstan analyse --no-progress --memory-limit=512M` (full app) passed (0 errors). `php artisan test --compact` (full suite) passed: 310 tests, 2436 assertions, including two new `ReportTest` cases that load the generated `.xlsx` via `PhpOffice\PhpSpreadsheet\IOFactory::load()` and assert sheet names and cell values. `npx eslint`/`npx prettier --write` on changed Vue files passed with no issues; `vendor/bin/pint --dirty --format agent` applied.
- **Decisions:** Amounts are written as `"{amount} {CURRENCY}"` strings per currency (comma-separated for multi-currency), matching the `formatAmounts()` convention already used in `reports/Index.vue`, rather than splitting into separate per-currency columns.
- **Blockers:** None.
- **Uncommitted:** All of Group 1 (`P4-01`-`P4-10`), Group 2 (`P4-11`-`P4-19`), and Group 3 so far (`P4-20`, `P4-21`) remain uncommitted on `feat/phase-4`, pending user approval to commit/push.
- **Next:** Continue Group 3: `P4-22` (structured CSV/Excel transaction import with validation), `P4-23` (import preview/validation UI), `P4-24` (idempotent import), `P4-25` (async large export via queue job).

### 2026-06-14 11:30 WIB - P4-20 CSV Transaction Export Implemented (Group 3 Started)

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-20`.
- **Status:** Completed.
- **Completed:** Installed `phpoffice/phpspreadsheet` (v5.8.0) as a substitute for `maatwebsite/excel`, which cannot install on PHP 8.5 (its phpspreadsheet dependency caps PHP <8.5); this unblocks the Excel work in `P4-21`-`P4-23` while satisfying the user's approval to add Excel support. Refactored `TransactionController::index()`, extracting `resolveTransactionFilters()` and `filteredTransactionsQuery()` as shared private methods (the latter rewritten from a `when()` chain to plain `if` statements on a `Builder<Transaction>` to satisfy Larastan, per the pattern established in `GenerateTransactionReport::filteredTransactions()`). Added `TransactionController::export()` and a `transactions.export` route, and new `App\Domain\Export\GenerateTransactionsCsv`, which streams a CSV (`response()->streamDownload()` + `fputcsv()`, `chunkById(500, ...)`) of the filtered posted-transaction history, one row per account-side ledger entry (`Date, Type, Status, Description, Memo, Merchant, Account, Category, Amount, Currency, Tags`; the category column comes from the transaction's category-type entry, if any). Added an "Export CSV" button to `transactions/Index.vue` that preserves the active filters. Regenerated Wayfinder routes (`exportMethod`, since `export` is a reserved JS identifier).
- **Verification:** `vendor/bin/phpstan analyse --no-progress --memory-limit=512M` (full app) passed (0 errors). `php artisan test --compact --filter=Transaction` passed: 140 tests, 1354 assertions, including new `tests/Feature/TransactionExportTest.php` (guest redirect, header row + income/expense rows, transfer producing two rows, and filter application). `npx eslint`/`npx prettier --write` on the changed Vue file passed with no issues; `vendor/bin/pint --dirty --format agent` applied.
- **Decisions:** Substituted `phpoffice/phpspreadsheet` for the unavailable `maatwebsite/excel` (informed, not re-asked, since it preserves the approved intent and is the library `maatwebsite/excel` itself wraps).
- **Blockers:** None.
- **Uncommitted:** All of Group 1 (`P4-01`-`P4-10`), Group 2 (`P4-11`-`P4-19`), and now `P4-20` remain uncommitted on `feat/phase-4`, pending user approval to commit/push.
- **Next:** Continue Group 3: `P4-21` (Excel export of monthly/annual reports using `phpoffice/phpspreadsheet`), then `P4-22`-`P4-25` (structured import with preview/validation, idempotent import, async large export via queue job).

### 2026-06-14 09:00 WIB - P4-19 Dashboard Customization Implemented (Group 2 Complete)

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-19`.
- **Status:** Completed.
- **Completed:** Added a nullable `report_widgets` JSON column to `workspaces` (migration `2026_06_13_144920_add_report_widgets_to_workspaces_table`), `Workspace::reportWidgets()`/`defaultReportWidgets()` (default: all seven widgets), and `report_widgets`/`report_widgets.*` validation in `WorkspacePreferencesRequest` (restricted to `summary`, `comparison`, `categoryBreakdown`, `merchantBreakdown`, `accountActivity`, `netWorth`, `netWorthTrend`). `WorkspaceController` exposes/persists the preference; `settings/Workspace.vue` gained a "Report cards and charts" checkbox group. `ReportController::index` now only builds deferred props for enabled widgets (others are omitted from the response) and passes `visibleWidgets`; `reports/Index.vue` conditionally renders each `<Deferred>` section via `isVisible()`.
- **Verification:** `vendor/bin/phpstan analyse --no-progress --memory-limit=512M` (full app) passed (0 errors). `php artisan test --compact` (full suite) passed: 304 tests, 2412 assertions, including new `ReportTest` ("reports page only includes the workspace configured report widgets") and `WorkspacePreferencesTest` (configure/validate `report_widgets`) tests. `npx eslint` on changed Vue files passed with no output. `npx prettier --write` and `vendor/bin/pint --dirty --format agent` applied.
- **Decisions:** Hidden widgets are fully omitted from the Inertia response (not just hidden client-side), so their expensive deferred computations are skipped entirely. Reused the existing `entry_form_fields` checkbox-list convention for the new preference, without reordering (visibility-only, per the catalog wording).
- **Blockers:** None.
- **Uncommitted:** All of Group 1 (`P4-01`-`P4-10`) and Group 2 (`P4-11`-`P4-19`) remain uncommitted on `feat/phase-4`, pending user approval to commit/push. This completes Group 2 of Phase 4.
- **Next:** Proceed to Group 3 (`P4-20`-`P4-25`, CSV/Excel export and validated idempotent import) per "lanjut seluruh phase 4".

### 2026-06-13 21:30 WIB - P4-11 to P4-18 Reports Implemented (Group 2 Statistics Complete)

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-11`, `P4-12`, `P4-13`, `P4-14`, `P4-15`, `P4-16`, `P4-17`, `P4-18`.
- **Status:** Completed.
- **Completed:** Added `App\Domain\Reports\GenerateTransactionReport` (`summary()`, `byCategory()`, `byMerchant()`, `byAccount()`, `comparison()`, all sharing a filtered query honoring `account_ids`/`category_ids`/`merchant_id`/`tag_ids`). Extended `App\Domain\Ledger\CalculateNetAsset` with `summary()` (assets/liabilities/net by currency, `Loan`/`CreditCard` treated as liabilities) and `trend()` (6-month net worth history). Added `ReportController::index` and `reports/Index.vue` (sidebar entry "Reports", `reports.index` route), using deferred Inertia props (`reports`/`net-worth` groups) with skeleton fallbacks, month navigation via `usePeriodNavigation`, and native multi-select filters. Net worth trend rendered as CSS bars (no new chart dependency, per CLAUDE.md). Added `tests/Feature/ReportTest.php` (3 tests covering summary/breakdowns, filters, and net worth).
- **Verification:** `vendor/bin/phpstan analyse --no-progress --memory-limit=512M app/Domain/Reports app/Domain/Ledger/CalculateNetAsset.php app/Http/Controllers/ReportController.php` passed (0 errors, after fixing 15 Larastan enum-cast/nullsafe/relation-typing errors using the `getRawOriginal('column') !== Enum->value` pattern and replacing `when()` chains with plain `if` statements in `filteredTransactions()`). `php artisan test --compact --filter=ReportTest` passed (3 tests, 127 assertions). `npx prettier --write` and `vendor/bin/pint --dirty --format agent` applied to changed files.
- **Decisions:** Reused `SummarizeTransactionPeriod::billingMonthStart()` for period boundaries and `CalculateAccountBalance::calculateAsOf()` for opening/closing balances, consistent with `transactions.summary`. Did not add a charting library; net worth trend uses CSS bar widths.
- **Blockers:** None.
- **Uncommitted:** All Group 1 (`P4-01`-`P4-10`) and Group 2 (`P4-11`-`P4-18`) work remains uncommitted on `feat/phase-4`, pending user approval to commit/push.
- **Next:** Implement `P4-19` (dashboard customization — workspace preference for visible summary cards/charts plus `Dashboard.vue` updates), the last item of Group 2, then proceed to Group 3 (`P4-20`-`P4-25`, CSV/Excel export/import) per "lanjut seluruh phase 4".

### 2026-06-13 20:10 WIB - P4-10 Budget Carry-Over Implemented (Group 1 Complete)

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-10`.
- **Status:** Completed.
- **Completed:** Added a `budget_carryover_enabled` boolean column (default
  `false`) to `categories` (migration
  `2026_06_13_122903_add_budget_carryover_enabled_to_categories_table`),
  added to `Category`'s `#[Fillable]` and `casts()`, and validated
  (`sometimes`, `boolean`) in `StoreCategoryRequest`/`UpdateCategoryRequest`.
  `categories/Index.vue` gained a "Carry over budget" checkbox in both the
  category and subcategory edit forms. `CalculateBudgetUsage::forMonth()` now
  computes the previous billing month's `forRange()` rows and passes each
  category's `budget`/`actual` into the current month's `forRange()` call via
  a new `$previousPeriod` parameter; `forRange()` adds a `carryover` field to
  each row (`null` unless `budget_carryover_enabled` and a previous-period
  budget exists), computed as `previousBudget - previousActual`, and folds it
  into `budget` (`default_budget`/`override` + `carryover`). `pace` is now
  derived from this carryover-adjusted `budget`. `budgets/Index.vue` and
  `budgets/Income.vue` show "Carryover: +/-N" under the actual amount when
  `carryover !== null`. Added 4 new tests to `BudgetTest.php` (unused
  carry-over increases next month's budget, overspend reduces it, carry-over
  disabled ignores prior surplus/deficit) and 1 to
  `CategoryManagementTest.php` (toggling `budget_carryover_enabled`).
  `docs/PROGRESS.md` marks `P4-10` `Done` and narrows the "Planned" catalog
  range to `P4-11` to `P8-10`. This completes Group 1 (`P4-01`-`P4-10`) of
  the Phase 4 recommended PR sequence.
- **Verification:** `php artisan test --compact --filter=BudgetTest` (19
  passed, 252 assertions); `php artisan test --compact
  --filter=CategoryManagementTest` (8 passed, 54 assertions); `composer
  ci:check` (298 tests / 2254 assertions, 0 PHPStan errors,
  Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Carry-over is single-hop (based only on the immediately
  preceding billing month's effective budget vs. actual, itself computed
  without carry-over) rather than compounding indefinitely across history —
  bounds the computation to one extra `forRange()` call per `forMonth()` and
  avoids unbounded recursion; matches the "Extended" (not "Core") catalog
  level. Scoped to `forMonth`/`forRange` only (not `forWeek`/`forYear`),
  mirroring the `P4-07` `pace` scoping decision, since a prorated
  weekly/yearly carry-over figure would be confusing.
- **Blockers:** None.
- **Uncommitted:** Migration, model, request, domain service, frontend, test,
  and doc changes are uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit Group 1 (`P4-01`-`P4-10`), then
  begin Group 2 (`P4-11`-`P4-19`, reporting) per the recommended PR sequence
  in `docs/MASTER_PLAN.md`.

### 2026-06-13 19:30 WIB - P4-09 Asset Target Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-09`.
- **Status:** Completed.
- **Completed:** Added a nullable `net_asset_target` integer column to
  `workspaces` (migration
  `2026_06_13_121918_add_net_asset_target_to_workspaces_table`), cast to
  `integer` on the `Workspace` model and added to its `#[Fillable]` list.
  `WorkspacePreferencesRequest` validates it as `['nullable', 'integer',
  'min:0']`; `WorkspaceController::edit()` exposes it, and
  `settings/Workspace.vue` gained a "Net asset target" number field with
  helper text. Added `App\Domain\Ledger\CalculateNetAsset::current()`, which
  sums `CalculateAccountBalance::calculate()` for the workspace's active
  accounts with `include_in_total` enabled, grouped by `currency_code`.
  `TransactionController::summary()` now injects `CalculateNetAsset` and
  passes `netAssets`, `netAssetTarget`, and `defaultCurrency` to
  `transactions/Summary.vue`, which gained a new "Net asset" card showing
  per-currency totals and, when a target is set, the remaining amount to
  reach it in `default_currency` (color-coded green at/under target, red
  otherwise). Added 2 tests to `WorkspacePreferencesTest.php` (set target,
  reject negative target) and 1 test to `TransactionSummaryTest.php` (net
  asset totals with and without a target). `docs/PROGRESS.md` marks `P4-09`
  `Done` and narrows the "Planned" catalog range to `P4-10` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=TransactionSummaryTest`
  (6 passed, 118 assertions); `php artisan test --compact
  --filter=WorkspacePreferencesTest` (10 passed, 37 assertions); `composer
  ci:check` (294 tests / 2209 assertions, 0 PHPStan errors,
  Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Net asset is computed as the current (all-time) balance per
  `CalculateAccountBalance`, summed per `currency_code` (no FX conversion);
  the single workspace-level target is only compared against the
  `default_currency` total, since cross-currency aggregation would require a
  conversion mechanism not yet in scope.
- **Blockers:** None.
- **Uncommitted:** Migration, model, request, controller, domain service,
  frontend, test, and doc changes are uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-10`
  (Budget carry-over), the last feature in Group 1, per the recommended PR
  sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 18:15 WIB - P4-08 Budget Trend Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-08`.
- **Status:** Completed.
- **Completed:** Extracted `TransactionController`'s private
  `summarizeBudgetRows()` (added in `P4-06`) into a public
  `CalculateBudgetUsage::summarizeTotal()`, updating `TransactionController`
  to call it. Added `CalculateBudgetUsage::trend()`, which calls `forMonth()`
  + `summarizeTotal()` for each of the past 6 workspace-local billing months
  (oldest first) up to and including the given reference month, returning
  `{month: 'Y-m', budget, actual, currency}` rows. Added
  `BudgetController::trend()` plus a `budgets.trend` route, and a
  `budgets/Trend.vue` page with two 6-month tables (Expense and Income)
  showing budget/planned vs actual vs remaining per month (remaining
  color-coded as in `P4-06`'s summary card), with month navigation shifting
  the 6-month window. Added a "Trend" tab to `BudgetViewNav` (now
  Monthly/Weekly/Yearly/Income/Trend). Added a new test "budget trend page
  shows actual versus budget for the past several months" to
  `BudgetTest.php`. `docs/PROGRESS.md` marks `P4-08` `Done` and narrows the
  "Planned" catalog range to `P4-09` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=BudgetTest` (16
  passed, 213 assertions); `composer ci:check` (291 tests / 2178 assertions,
  0 PHPStan errors, Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Implemented the trend as a workspace-wide total (mirroring
  `P4-06`'s summary), not per-category, to keep the page readable as an
  "MVP"-level overview; per-category historical trends would need a
  significantly larger UI (a grid or chart per category) and aren't required
  by the catalog acceptance summary ("Historical actual-versus-budget trend
  is available").
- **Blockers:** None.
- **Uncommitted:** Domain service, controller, routes, frontend, test, and
  doc changes are uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-09` (Asset
  target) per the recommended PR sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 17:50 WIB - P4-07 Recommended Spending Pace Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-07`.
- **Status:** Completed.
- **Completed:** Added a `pace` field to every row returned by
  `CalculateBudgetUsage::forRange()`/`forMonth()`: the portion of the
  resolved budget expected to be used by "today" (the current
  workspace-local date), via a new private `elapsedDays()` helper that
  returns `{elapsed, total}` day counts for `[$start, $end)` — `elapsed = 0`
  if the period has not started, `elapsed = total` if it has already ended,
  otherwise the number of days from `$start` through today inclusive.
  `pace = budget === null ? null : round(budget * elapsed / total)`.
  `budgets/Index.vue` and `budgets/Income.vue` gained an "On pace: …" column
  (added a 6th grid column), highlighted amber when actual spending exceeds
  pace (expense) or actual income falls behind pace (income). Added a `pace`
  assertion to the existing "shows the default budget and actual spending"
  test (expense) and "shows planned income and actual income" test (income),
  and a new test "the recommended spending pace is zero for a future period
  and the full budget for a past period". `docs/PROGRESS.md` marks `P4-07`
  `Done` and narrows the "Planned" catalog range to `P4-08` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=BudgetTest` (15
  passed, 188 assertions); `composer ci:check` (290 tests / 2153 assertions,
  0 PHPStan errors, Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Scoped `pace` to `forRange()`/`forMonth()` only (used by
  `budgets.index`/`budgets.income`), not `forWeek()`/`forYear()` — those
  views already aggregate across different period granularities and a
  pace-within-pace figure would be confusing; the catalog acceptance summary
  ("Budget shows expected spend-to-date for the period") is satisfied by the
  primary monthly budget views.
- **Blockers:** None.
- **Uncommitted:** Domain service, frontend, test, and doc changes are
  uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-08`
  (Budget trend) per the recommended PR sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 17:25 WIB - P4-06 Total Budget Summary Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-06`.
- **Status:** Completed.
- **Completed:** Fulfilled the budget-comparison portion of `P3-05`'s
  acceptance summary, deferred to `P4-06` per PR #14 review.
  `TransactionController::summary()` now injects `CalculateBudgetUsage` and
  computes `budgetSummary` (`expense` and `income`) via `forMonth()` for
  `CategoryType::Expense` and `CategoryType::Income`, reduced to a single
  `{budget, actual, currency}` total per type by a new private
  `summarizeBudgetRows()` helper. `transactions/Summary.vue` gained a "Budget
  summary" card with two rows: "Expense budget" (links to `budgets.index`)
  and "Planned income" (links to `budgets.income`), each showing
  budget/planned, actual, and a color-coded remaining amount (green when
  under the expense budget or at/under planned income, red otherwise —
  inverted for income since exceeding the plan is favorable). Added budget
  assertions to the existing "summary view shows period totals and account
  movement" test and a new dedicated test "summary view compares total actual
  spending and income with the total budget" in `TransactionSummaryTest.php`.
  `docs/PROGRESS.md` marks `P4-06` `Done` and narrows the "Planned" catalog
  range to `P4-07` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=TransactionSummaryTest`
  (5 passed, 92 assertions); `composer ci:check` (289 tests / 2131 assertions,
  0 PHPStan errors, Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Reused `CalculateBudgetUsage::forMonth()` for both category
  types rather than introducing a new aggregation method, since the
  budget-vs-actual reduction is the same shape already used per-category in
  `budgets.index`/`budgets.income`.
- **Blockers:** None.
- **Uncommitted:** Controller, frontend, test, and doc changes are
  uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-07`
  (Recommended spending pace) per the recommended PR sequence in
  `docs/MASTER_PLAN.md`.

### 2026-06-13 17:00 WIB - P4-05 Income Budget Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-05`.
- **Status:** Completed.
- **Completed:** Removed the `P4-01` `after()` validators in
  `StoreCategoryRequest`/`UpdateCategoryRequest` that rejected
  `monthly_budget_amount` for non-expense categories — income categories may
  now set the same field as a "planned monthly income" target.
  `categories/Index.vue` now shows the field for both category types (label
  switches to "Planned income" for income categories; the add-category form
  label reads "Monthly budget / planned income"). Generalized
  `CalculateBudgetUsage::forMonth()`/`forRange()`/`forWeek()`/`forYear()` to
  accept an optional `CategoryType $type = CategoryType::Expense`, and
  renamed/generalized `actualExpenseByCategory()` to `actualByCategory()`,
  which sums actual income (absolute value of negative category-entry
  amounts) for income categories and actual spend (positive amounts) for
  expense categories. Added `BudgetController::income()` plus a
  `budgets.income` route, and a `budgets/Income.vue` page (planned vs actual
  income per income category for the current billing month) reusing the
  `P4-02` override/reset mechanism. Added an "Income" tab to `BudgetViewNav`
  (now Monthly/Weekly/Yearly/Income). Updated the `P4-01` "income category
  cannot have a monthly budget" test in `CategoryManagementTest.php` to
  "an income category can have planned monthly income" (now asserts success
  instead of validation errors), and added 3 feature tests to
  `BudgetTest.php` for the income view. `docs/PROGRESS.md` marks `P4-05`
  `Done` and narrows the "Planned" catalog range to `P4-06` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=BudgetTest` (14
  passed, 166 assertions); `php artisan test --compact
  --filter=CategoryManagementTest` (7 passed, 48 assertions); `composer
  ci:check` (288 tests / 2111 assertions, 0 PHPStan errors,
  Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Reused the existing `monthly_budget_amount` column and
  `category_budget_overrides` mechanism for planned income rather than adding
  a parallel column/table, since the "target amount vs actual" shape is
  identical for both category types and `CategoryBudgetOverride` is already
  keyed by `category_id` (type-agnostic).
- **Blockers:** None.
- **Uncommitted:** Requests, frontend, domain service, controller, routes,
  component, test, and doc changes are uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-06` (Total
  budget summary) per the recommended PR sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 16:35 WIB - P4-04 Annual Budget Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-04`.
- **Status:** Completed.
- **Completed:** Added `CalculateBudgetUsage::forYear()`, which iterates the
  12 billing months of the workspace-local calendar year, calling
  `forMonth()` for each and summing each expense category's `budget` (`null`
  if no month has a budget) and `actual` across the year — so a `P4-02`
  monthly override is reflected in the yearly total. Added
  `BudgetController::yearly()` plus a `budgets.yearly` route, and a
  `budgets/Yearly.vue` page showing the annual budget/actual/remaining per
  category with year navigation, mirroring `transactions.monthly`. Added a
  "Yearly" tab to `BudgetViewNav` (now Monthly/Weekly/Yearly), used by
  `budgets/Index.vue`, `budgets/Weekly.vue`, and `budgets/Yearly.vue`. Added
  3 feature tests covering the yearly sum, override-adjusted total, and the
  budget/spend-empty exclusion. `docs/PROGRESS.md` marks `P4-04` `Done` and
  narrows the "Planned" catalog range to `P4-05` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=BudgetTest` (11
  passed, 124 assertions); `composer ci:check` (285 tests / 2068 assertions,
  0 PHPStan errors, Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Like the weekly view, the yearly view is read-only; budget
  overrides remain editable only from `budgets.index` (monthly).
- **Blockers:** None.
- **Uncommitted:** Domain service, controller, routes, frontend, component,
  test, and doc changes are uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-05`
  (Income budget) per the recommended PR sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 16:10 WIB - P4-03 Weekly Budget Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-03`.
- **Status:** Completed.
- **Completed:** Added `CalculateBudgetUsage::forWeek()`, which resolves the
  workspace-local week containing a reference date (via
  `SummarizeTransactionPeriod::weekStart()`), sums actual posted expense per
  expense category for that week (`forWeek()`/`actualExpenseByCategory()`),
  and prorates each category's effective monthly budget (override from
  `P4-02` or `monthly_budget_amount` from `P4-01`, resolved for the billing
  month containing the week) to 7 days based on the number of days in that
  billing month. Added `BudgetController::weekly()` plus a `budgets.weekly`
  route, and a `budgets/Weekly.vue` page showing prorated weekly
  budget/actual/remaining per category with week navigation, mirroring
  `transactions.weekly`. Added a `BudgetViewNav` component (Monthly/Weekly
  tabs) used by both `budgets/Index.vue` and `budgets/Weekly.vue`. Added 3
  feature tests covering proration, override proration, and the
  budget/spend-empty exclusion. `docs/PROGRESS.md` marks `P4-03` `Done` and
  narrows the "Planned" catalog range to `P4-04` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=BudgetTest` (8
  passed, 89 assertions); `composer ci:check` (282 tests / 2033 assertions, 0
  PHPStan errors, Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** The weekly view is read-only (no override editing) since
  `CategoryBudgetOverride.period` is keyed by billing month, not week;
  overrides remain editable only from the monthly `budgets.index` page and
  are reflected in the weekly view via proration.
- **Blockers:** None.
- **Uncommitted:** Domain service, controller, routes, frontend, component,
  test, and doc changes are uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-04`
  (Annual budget) per the recommended PR sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 15:45 WIB - P4-02 Monthly Budget Override Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-02`.
- **Status:** Completed.
- **Completed:** Added a `category_budget_overrides` table (`workspace_id`,
  `category_id`, `period` date, `amount`, unique on `category_id`+`period`)
  and `CategoryBudgetOverride` model/factory, plus a `budgetOverrides()`
  relation on `Category` and `categoryBudgetOverrides()` on `Workspace`.
  Added `App\Domain\Budgets\CalculateBudgetUsage`, a shared domain service
  that resolves the effective monthly budget for a category in a billing
  period (override over `monthly_budget_amount` from `P4-01`) and sums
  actual posted expense per category over a range, reusing
  `SummarizeTransactionPeriod::billingMonthStart()`. Added
  `UpdateCategoryBudgetOverrideRequest`/`DestroyCategoryBudgetOverrideRequest`
  (authorized via `CategoryPolicy::update`), `BudgetController` with
  `budgets.index`/`budgets.overrides.update`/`budgets.overrides.destroy`
  routes, and a `budgets/Index.vue` page listing each expense category's
  default/override/effective budget versus actual spend for the current
  billing month, with month navigation and inline forms to set/reset a
  per-month override. Added "Budgets" to the sidebar nav. Added
  `tests/Feature/BudgetTest.php` (5 tests, 56 assertions). `docs/PROGRESS.md`
  marks `P4-02` `Done` and narrows the "Planned" catalog range to
  `P4-03` to `P8-10`.
- **Verification:** `php artisan test --compact --filter=BudgetTest` (5
  passed, 56 assertions); `composer ci:check` (279 tests / 2000 assertions, 0
  PHPStan errors, Pint/ESLint/Prettier/TypeScript/build clean); `bash
  scripts/check-governance.sh` passed.
- **Decisions:** Built `CalculateBudgetUsage::forRange()`/`forMonth()` as the
  shared engine intended for reuse by `P4-03` (weekly), `P4-04` (annual),
  `P4-06` (total summary), `P4-07` (pace), and `P4-08` (trend), per the
  "Architecture Work" item in `docs/MASTER_PLAN.md`. Only expense categories
  with a default budget, an override for the period, or actual spending are
  listed, to avoid cluttering the page with irrelevant categories.
- **Blockers:** None.
- **Uncommitted:** Migration, model, factory, domain service, requests,
  controller, routes, frontend, sidebar, test, and doc changes are
  uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-03`
  (Weekly budget) per the recommended PR sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 15:10 WIB - P4-01 Category Budget Implemented

- **Branch:** `feat/phase-4`.
- **Feature IDs:** `P4-01`.
- **Status:** Completed.
- **Completed:** Added a nullable `monthly_budget_amount` column to
  `categories` (minor units, same convention as ledger entry amounts).
  `StoreCategoryRequest`/`UpdateCategoryRequest` validate it as a non-negative
  integer and reject it for non-expense categories via an `after()` validator.
  `categories/Index.vue` shows a "Monthly budget" field for expense categories
  (top-level and subcategories) in the add and edit forms. Added factory
  default and two feature tests (`an expense category can have a default
  monthly budget`, `an income category cannot have a monthly budget`).
  `docs/PROGRESS.md` marks `P4-01` `Done`, `F-011` and Phase 4 `In Progress`.
- **Verification:** `composer ci:check` (274 tests / 1944 assertions, 0
  PHPStan errors, Pint/ESLint/Prettier/TypeScript/build clean) and
  `bash scripts/check-governance.sh` passed.
- **Decisions:** Budgets are stored as plain integers in the same unit as
  transaction amounts (no minor/major unit conversion), matching the existing
  transaction amount convention. Income categories are rejected rather than
  silently nulled to keep validation explicit and consistent with other
  type-dependent category rules.
- **Blockers:** None.
- **Uncommitted:** Migration, model, request, factory, frontend, test, and
  doc changes are uncommitted on `feat/phase-4`.
- **Next:** Await user approval to commit, then continue with `P4-02` (Monthly
  budget override) per the recommended PR sequence in `docs/MASTER_PLAN.md`.

### 2026-06-13 14:12 WIB - Phase 3 Merged

- **Branch:** `main`.
- **Feature IDs:** `P3-01` to `P3-21`.
- **Status:** Completed.
- **Completed:** Merged PR #14 into `main` at the user's request (merge commit,
  matching prior PR merge style). Updated `docs/PROGRESS.md`: Phase 3, `F-009`,
  `F-010`, and `P3-01` through `P3-21` are now `Done`; the current milestone
  status is `Done`.
- **Verification:** `bash scripts/check-governance.sh` passed.
- **Decisions:** None.
- **Blockers:** None.
- **Uncommitted:** `docs/PROGRESS.md` and this worklog entry are uncommitted on
  `main`.
- **Next:** Commit and push the progress update to `main`, then select the
  next Phase 4 feature per `docs/MASTER_PLAN.md`.

### 2026-06-13 14:05 WIB - PR #14 Review Findings Resolved

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-02`, `P3-03`, `P3-04`, `P3-05`, `P3-07`, `P3-08`,
  `P1-16`, `P1-17`, `P1-18`.
- **Status:** In review.
- **Completed:** Addressed all 5 findings from `yosrioid`'s review of PR #14:
  (1) `SummarizeTransactionPeriod::forRange()` now excludes `Replaced`
  transactions and buckets income/expense by the account entry's sign so
  reversal/replacement pairs net to zero instead of double-counting, with new
  regression tests covering calendar, weekly, monthly, and summary views plus
  `forRange()` itself; (2) calendar, weekly, monthly, and summary views now
  honor the workspace's `first_day_of_week` (`P1-16`), `month_start_day`
  (`P1-17`), and `adjust_month_for_weekend` (`P1-18`) preferences via new
  `SummarizeTransactionPeriod::weekStart()` and `billingMonthStart()` helpers
  (weekend-adjusted start days shift to the preceding Friday, per user
  decision); (3) formally split `P3-05`'s acceptance summary in
  `docs/FEATURE_CATALOG.md` so the budget-comparison portion is reassigned to
  `P4-06`, per the documented scope-change process, with `docs/PROGRESS.md`
  updated accordingly (user-approved); (4) `from`/`to`/`month` query
  parameters are now strictly validated with `checkdate()` and rejected
  instead of silently normalizing or risking a 500; (5) numeric transaction
  search now parses input as an integer instead of `float`, avoiding
  precision loss above 2^53.
- **Verification:** `composer ci:check` (272 Pest tests / 1933 assertions, 13
  Vitest tests, PHPStan 0 errors, Pint, ESLint, Prettier, TypeScript,
  production build), `npm run test:e2e` (2/2), `composer audit` and
  `npm audit --audit-level=high` (0 vulnerabilities), and
  `bash scripts/check-governance.sh` all passed.
- **Decisions:** Reversal/replacement nets are shown as gross income and
  expense activity that sums to zero net, matching
  `CalculateAccountBalance::calculateAsOf()`'s exclusion of `Replaced`
  transactions. Weekend-adjusted `month_start_day` boundaries always shift to
  the preceding Friday (single consistent direction).
- **Blockers:** None.
- **Uncommitted:** All review-fix changes are uncommitted on `feat/phase-3`.
- **Next:** Review the diff with the user, then commit and push to PR #14 only
  after explicit approval.

### 2026-06-13 10:28 WIB - Phase 3 Pull Request Opened

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-01` to `P3-21`.
- **Status:** In review.
- **Completed:** Pushed the complete Phase 3 branch and opened
  [PR #14](https://github.com/yosrioid/money-manager/pull/14) against `main`.
  Updated `docs/PROGRESS.md` so the current milestone, delivery packages,
  catalog range, and individual Phase 3 features accurately show `In Review`.
- **Verification:** Pre-push final gates passed: `composer ci:check` (257 Pest
  tests / 1768 assertions, 13 Vitest tests, PHPStan 0 errors, Pint, ESLint,
  Prettier, TypeScript, Wayfinder generation, and production build),
  `npm run test:e2e` (2/2 Chromium and mobile Safari), Composer/npm security
  audits, governance, and `git diff --check`.
- **Decisions:** Phase 3 remains not `Done` until PR #14 is reviewed, CI passes,
  the exit gate is confirmed, and the branch is merged.
- **Blockers:** None.
- **Uncommitted:** This PR-status documentation update only.
- **Next:** Commit and push the PR-status documentation, then verify PR #14 CI
  and review state without merging.

### 2026-06-13 10:26 WIB - Phase 3 Ready For Review

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-01` to `P3-21`.
- **Status:** Ready for review.
- **Completed:** Completed final Phase 3 audit and committed the remaining
  focused slices: `P3-20` navigation preferences (`d85c591`), `P3-21`
  statistics inclusion (`c6138b2`), and the isolated loopback browser-test
  server (`45c16bf`). All approved Phase 3 features are implemented on the
  phase branch.
- **Verification:** `composer ci:check` passed with 257 Pest tests / 1768
  assertions, 13 Vitest tests, PHPStan 0 errors, Pint, ESLint, Prettier,
  TypeScript, Wayfinder generation, and production build. `npm run test:e2e`
  passed 2/2 on Chromium and mobile Safari using `127.0.0.1:8011`. Composer
  and npm security audits reported no vulnerabilities; governance and
  `git diff --check` passed.
- **Decisions:** Phase 3 remains `In Progress` in `docs/PROGRESS.md` until its
  pull request is open; it becomes `In Review` only after the PR exists.
- **Blockers:** None.
- **Uncommitted:** This review-ready documentation checkpoint only.
- **Next:** Commit this checkpoint, push `feat/phase-3`, open the Phase 3 pull
  request, then record the PR and `In Review` status.

### 2026-06-13 10:21 WIB - Playwright Uses Isolated Loopback Server

- **Branch:** `feat/phase-3`.
- **Feature IDs:** Engineering only, supporting the Phase 3 browser gate.
- **Status:** Completed.
- **Completed:** Changed Playwright's default target from the environment-
  dependent `money-manager.test` hostname to `http://127.0.0.1:8011`.
  Playwright now automatically starts an isolated `php artisan serve` process
  when `APP_URL` is not explicitly provided; callers can still override the
  target through `APP_URL`.
- **Verification:** `npm run test:e2e` passed 2/2 on Chromium and mobile Safari.
  `npm run types:check`, `npm run lint:check`, Prettier config check, and
  `git diff --check` passed.
- **Decisions:** Keep browser tests self-contained by default instead of
  depending on Laravel Herd DNS or a manually running development server.
- **Blockers:** None.
- **Uncommitted:** The Playwright configuration change and all pending Phase 3
  work remain uncommitted on `feat/phase-3`.
- **Next:** Run final governance checks and prepare the Phase 3 branch for
  review when requested.

### 2026-06-13 10:14 WIB - `P3-21` Statistics Inclusion Implemented

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-21`.
- **Status:** Completed.
- **Completed:** Added an `include_in_statistics` boolean to transactions,
  enabled by default; a validated, workspace-scoped PATCH endpoint; an atomic
  row-locked domain action; audit logging; and a transaction-detail control.
  Excluded transactions remain in history/detail and ledger-derived account
  balances, while period statistics omit them. Updated progress to record that
  all Phase 3 features are implemented on the branch.
- **Verification:** `composer ci:check` passed with PHPStan reporting 0 errors,
  257 Pest tests / 1768 assertions, 13 Vitest tests, Pint, ESLint, Prettier,
  TypeScript, Wayfinder generation, and production build. Focused `P3-21`,
  lifecycle, summary, calendar, and detail suites passed (22 tests / 254
  assertions); final focused inclusion/lifecycle regression passed (12 tests /
  67 assertions). Composer and npm security audits reported no vulnerabilities;
  governance and `git diff --check` passed.
- **Decisions:** Statistics inclusion is mutable non-ledger metadata and the
  only new mutation allowed on posted/terminal transaction records. Account
  movements remain ledger-derived and intentionally ignore this preference.
- **Blockers:** Browser verification remains blocked because the in-app browser
  is unavailable and the existing Playwright target `money-manager.test`
  cannot resolve in this environment.
- **Uncommitted:** Complete `P3-20` and `P3-21` implementations, tests,
  migrations, progress, and worklog updates remain uncommitted on
  `feat/phase-3`.
- **Next:** Review and commit the focused `P3-20` and `P3-21` slices only when
  requested, then prepare the Phase 3 branch for review.

### 2026-06-13 10:06 WIB - `P3-20` Navigation Preferences Completed

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-20`.
- **Status:** Completed.
- **Completed:** Finished the interrupted navigation-preference slice. Added a
  hidden `0` fallback so an unchecked workspace-settings checkbox reliably
  disables shortcuts, and extended swipe/Left/Right period navigation to the
  Summary view alongside calendar, weekly, monthly, and day views. Updated
  focused endpoint coverage and `docs/PROGRESS.md`.
- **Verification:** `composer ci:check` passed with PHPStan reporting 0 errors,
  253 Pest tests / 1712 assertions, 13 Vitest tests, Pint, ESLint, Prettier,
  TypeScript checks, Wayfinder generation, and production build. Focused
  navigation/preferences tests passed with 16 tests / 123 assertions;
  governance and `git diff --check` passed.
- **Decisions:** Shortcut direction handling remains a reusable frontend
  composable; the persisted workspace preference is the single enable/disable
  control for every supported period view.
- **Blockers:** In-app browser was unavailable. Playwright could launch outside
  the sandbox, but both existing welcome smoke tests could not resolve
  `http://money-manager.test/`; direct browser verification remains blocked by
  the local target/DNS environment.
- **Uncommitted:** Complete `P3-20` implementation, tests, progress update, and
  worklog checkpoints are uncommitted on `feat/phase-3`.
- **Next:** Review and commit the focused `P3-20` slice when requested, then
  continue with `P3-21` (include or exclude transaction from statistics).

### 2026-06-13 10:01 WIB - `P3-20` Interrupted Work Audit

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-20`.
- **Status:** In progress.
- **Completed:** Audited the interrupted local work. The uncommitted slice adds
  a workspace preference, period-navigation composable, swipe/arrow direction
  helpers, and integration for calendar, weekly, monthly, and day views.
- **Verification:** Focused Pest tests passed (16 tests / 115 assertions);
  full Pest suite passed (253 tests / 1704 assertions); focused Vitest passed
  (8 tests); `npm run types:check`, `npm run lint:check`,
  `npm run format:check`, `npm run build`, and `git diff --check` passed.
  `composer analyse` / direct PHPStan exited with code 1 without diagnostic
  output.
- **Decisions:** Treat `P3-20` as incomplete until the disabled-checkbox
  browser submission path is corrected and the Summary period view is either
  integrated or explicitly excluded with rationale.
- **Blockers:** The unchecked navigation preference is omitted by native form
  submission while backend validation requires the field, so the current UI
  cannot reliably disable shortcuts. Summary has previous/next period controls
  but does not receive or use the preference/composable.
- **Uncommitted:** All local `P3-20` implementation files and this audit
  checkpoint are uncommitted. No implementation files were changed during the
  audit.
- **Next:** Fix and test disabled preference submission, add Summary period
  navigation coverage, investigate the silent PHPStan failure, then rerun
  applicable quality gates and update `docs/PROGRESS.md`.

### 2026-06-13 09:55 WIB - `P3-19` Responsive Mobile Entry

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-19`.
- **Status:** Completed.
- **Completed:** Audited `TransactionForm.vue` and the shared app shell for
  narrow-viewport issues. The main field grid already uses
  `grid gap-6 md:grid-cols-2` (single column below `md`), split rows already
  collapse to one column, and recent-value/bookmark chips already use
  `flex flex-wrap`. The shadcn sidebar already renders as a mobile drawer, and
  `AppSidebarLayout`'s `AppContent` already sets `overflow-x-hidden`. The only
  fix needed was the bottom action row: changed
  `flex items-center gap-3` to `flex flex-wrap items-center gap-3` so
  "Save transaction" / "Save draft" / "Cancel" wrap instead of overflowing on
  narrow screens, and hid the `P3-18` keyboard-shortcut hint below the `sm`
  breakpoint (`hidden ... sm:inline`) since it isn't relevant on touch
  devices.
- **Verification:** `npm run lint:check`, `npm run format:check` (after
  `npx prettier --write` on `TransactionForm.vue`), `npm run types:check`,
  `npm run build`, and the full `php artisan test --compact`
  (250 tests / 1659 assertions, unaffected) all passed.
- **Decisions:** Full automated desktop/mobile browser acceptance testing of
  this and other journeys is the dedicated scope of `P8-04` (Browser and
  responsive acceptance); this slice satisfies the catalog's "core entry
  works on supported mobile viewport sizes" acceptance at the responsive
  layout level, consistent with how `P3-05` documented its own scoped
  deferral to a later phase.
- **Blockers:** None.
- **Uncommitted:** All `P3-19` changes are uncommitted on `feat/phase-3`,
  pending the standard P3-19 commit.
- **Next:** Commit as `feat(transactions): improve mobile layout for
  transaction entry form (P3-19)`, then continue with `P3-20` (Swipe and
  navigation preferences).

### 2026-06-13 09:35 WIB - `P3-18` Keyboard-First Desktop Entry

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-18`.
- **Status:** Completed.
- **Completed:** `TransactionForm.vue` now auto-focuses the Amount input on
  load (the first value most users type) and listens for a Ctrl+Enter /
  Cmd+Enter keydown anywhere within the `<Form>` to call
  `requestSubmit()` on the form element — this lets users submit from inside
  the multi-line Description and Memo textareas, where a plain Enter inserts
  a newline instead. The shortcut predicate (`isSubmitShortcut`) was extracted
  to `resources/js/lib/keyboard-shortcuts.ts` so it can be unit tested
  independently of mounting the form. A short text hint near the
  save/draft/cancel buttons documents the shortcut for desktop users.
- **Verification:** New `resources/js/lib/keyboard-shortcuts.test.ts`
  (4 tests) via `npm run test:unit` (5 tests total, all passing),
  `npm run lint:check`, `npm run format:check`, `npm run types:check`,
  `npm run build`, and the full `php artisan test --compact`
  (250 tests / 1659 assertions, unaffected) all passed.
- **Decisions:** Scoped to the two highest-value, low-risk keyboard
  improvements (initial focus + submit shortcut) rather than introducing a
  broader hotkey scheme, since the catalog acceptance is "core transaction
  entry works efficiently from keyboard" and native tab order through the
  form's fields was already sequential and logical.
- **Blockers:** None.
- **Uncommitted:** All `P3-18` changes are uncommitted on `feat/phase-3`,
  pending the standard P3-18 commit.
- **Next:** Commit as `feat(transactions): add keyboard shortcuts for
  transaction entry (P3-18)`, then continue with `P3-19` (Responsive mobile
  entry).

### 2026-06-13 09:10 WIB - `P3-17` Entry-Form Field Configuration

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-17`.
- **Status:** Completed.
- **Completed:** Added a workspace-scoped `entry_form_fields` nullable JSON
  column (after `application_lock_minutes`), with `Workspace::entryFormFields()`
  falling back to `Workspace::defaultEntryFormFields()`
  (`['merchant', 'memo', 'tags']`) when unset. `WorkspacePreferencesRequest`
  validates `entry_form_fields` as `sometimes|array` with each entry
  restricted to `merchant`/`memo`/`tags` and `distinct`;
  `WorkspaceController::update()` defaults a missing key to `[]` so unchecking
  all fields stores an empty configuration rather than leaving stale data.
  `WorkspaceController::edit()` and `TransactionController::formProps()` both
  expose `entryFormFields`. `settings/Workspace.vue` gained a "Transaction
  entry form fields" section: a checkbox per field (bound to a reactive
  `entryFormFieldRows` ref initialized/sorted from the workspace config) plus
  up/down reorder buttons, submitting the visible fields in order as hidden
  `entry_form_fields[]` inputs. `TransactionForm.vue` was restructured so the
  merchant/recipient, memo, and tags sections render via a single
  `<template v-for="field in visibleOptionalFields">` driven by the
  workspace's `entryFormFields` order (description and occurred_at remain
  fixed/required).
- **Verification:** `composer analyse` (0 errors), `vendor/bin/pint --dirty
  --format agent`, new `tests/Feature/EntryFormFieldConfigurationTest.php`
  (5 tests, 38 assertions), full suite `php artisan test --compact`
  (250 tests / 1659 assertions), `npm run lint:check`,
  `npm run format:check` (after `npx prettier --write` on
  `settings/Workspace.vue` and `TransactionForm.vue`), `npm run types:check`,
  and `npm run build` all passed.
- **Decisions:** Exactly the three catalog-listed optional fields
  (merchant/recipient, memo, tags) are configurable; description and
  occurred_at stay mandatory. An empty `entry_form_fields` array means "hide
  all three optional fields", distinct from `null`/absent which means
  "use the default order".
- **Blockers:** None.
- **Uncommitted:** All `P3-17` changes are uncommitted on `feat/phase-3`,
  pending the standard P3-17 commit.
- **Next:** Commit as `feat(workspaces,transactions): add entry-form field
  configuration (P3-17)`, then continue with `P3-18` (Keyboard-first desktop
  entry).

### 2026-06-13 07:05 WIB - `P3-16` Favorite Accounts And Categories

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-16`.
- **Status:** Completed.
- **Completed:** Added a migration giving `accounts` and `categories` an
  `is_favorite` boolean (default `false`, after `is_visible`), added it to
  both models' `Fillable`/`casts()`, and to `UpdateAccountRequest` /
  `UpdateCategoryRequest` validation (`sometimes|boolean`). `AccountForm.vue`
  gained a "Favorite" checkbox; `categories/Index.vue` gained the same
  checkbox on both top-level category rows and subcategory rows.
  `accounts/Index.vue` shows a star icon next to favorite account names.
  `TransactionController::formProps()` now orders `accounts` and `categories`
  with `orderByDesc('is_favorite')->orderBy('name')` and includes
  `is_favorite` in the selected columns; `TransactionForm.vue` prefixes
  favorite options with "★" across all account/category selects (source,
  destination, category, split categories, transfer fee category). Added
  `favorite()` states to `AccountFactory`/`CategoryFactory`.
- **Verification:** `composer analyse` (0 errors), `vendor/bin/pint --dirty
  --format agent`, new `tests/Feature/FavoriteAccountsAndCategoriesTest.php`
  (3 tests, 33 assertions), full suite `php artisan test --compact`
  (245 tests / 1621 assertions), `npm run lint:check`,
  `npm run format:check` (after `npx prettier --write` on
  `TransactionForm.vue`), `npm run types:check`, and `npm run build` all
  passed.
- **Decisions:** Favorite ordering only affects the transaction entry form's
  account/category selection lists (catalog: "Favorites appear first without
  changing financial meaning") — `position`-based ordering used everywhere
  else (account/category management pages, reports, balances) is untouched.
- **Blockers:** None.
- **Uncommitted:** All `P3-16` backend, frontend, test, and documentation
  changes are complete and verified but not yet committed.
- **Next:** Commit as `feat(accounts,categories): add favorite accounts and
  categories (P3-16)`, mark task #15 completed, then begin `P3-17`
  (Entry-form field configuration).

### 2026-06-13 06:10 WIB - `P3-15` Recent Value Suggestions

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-15`.
- **Status:** Completed.
- **Completed:** `TransactionController::formProps()` now queries the
  workspace's 50 most recently posted transactions
  (`whereNotNull('posted_at')`, ordered by `occurred_at`/`id` desc) and derives
  `recentDescriptions` (up to 8 distinct, non-empty descriptions,
  most-recent-first) and `recentMerchants` (up to 8 distinct merchants,
  ordered by recency of first occurrence). Both are returned alongside the
  existing `bookmarks` prop, available on `transactions.create` and the draft
  resume page. `CreateTransaction.vue` passes both through to
  `TransactionForm.vue`, which renders them as clickable "Recent:" suggestion
  chips below the Description textarea (fills `description`) and the Merchant
  select (fills `merchantId`); both fields are now `v-model`-bound instead of
  using `:value`.
- **Verification:** `composer analyse` (0 errors), `vendor/bin/pint --dirty
  --format agent`, new `tests/Feature/TransactionRecentValuesTest.php`
  (2 tests, 26 assertions) via `php artisan test --compact
  --filter=TransactionRecentValuesTest`, full suite `php artisan test
  --compact` (242 tests / 1588 assertions), `npm run lint:check`,
  `npm run format:check`, `npm run types:check`, and `npm run build` all
  passed.
- **Decisions:** Recent values are sourced from the same 50-transaction
  query for both descriptions and merchants to avoid duplicate queries; the
  8-item cap and "most recently used first" ordering match the catalog intent
  of speeding up repeat entry without overwhelming the form.
- **Blockers:** None.
- **Uncommitted:** Backend, frontend, test, and documentation changes for
  `P3-15` are complete and verified but not yet committed.
- **Next:** Commit as `feat(transactions): add recent value suggestions
  (P3-15)`, mark task #14 completed, then begin `P3-16` (Favorite accounts and
  categories).

### 2026-06-13 05:05 WIB - `P3-13` Transaction Bookmarks

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-13`.
- **Status:** Completed.
- **Completed:** Added a workspace-scoped, reorderable `transaction_bookmarks`
  table (`workspace_id`, `name`, JSON `payload`, `position`, indexed on
  `[workspace_id, position]`), `TransactionBookmark` model/policy/factory
  following the `AccountGroup` pattern, and `Workspace::transactionBookmarks()`.
  Added `TransactionBookmarkController` with `transaction-bookmarks.store`
  (validated via `StoreTransactionBookmarkRequest`, which extends
  `StoreTransactionDraftRequest` and adds `name`; the remaining validated
  fields become the JSON `payload`, mirroring `Transaction::draft_data`),
  `.update` (rename only), `.move` (reuses the existing
  `MoveOrderedResource`/`MoveOrderedResourceRequest`, extended to recognize
  the `transaction_bookmark` route parameter), and `.destroy`. The transaction
  entry form (`transactions/CreateTransaction.vue` /
  `TransactionForm.vue`) now lists the workspace's bookmarks with "Use"
  (a link to `transactions.create?bookmark_id=`), a rename field/button,
  up/down reorder buttons, and a delete button, plus a "Save as bookmark"
  name field and button that posts the current form's `FormData` (the same
  approach as the existing "Save draft" action). `TransactionController::create`
  / `editDraft` / `formProps()` now accept the request, resolve
  `?bookmark_id=` to a workspace-owned bookmark, and use its `payload` as
  `initialData` when there is no draft (reusing the draft-resume prefill
  mechanism), plus return the ordered `bookmarks` list.
- **Verification:** New `tests/Feature/TransactionBookmarkTest.php` (6 tests,
  42 assertions) covers: saving the current form as a bookmark (payload
  excludes `name`, position starts at 0); bookmarks appearing on
  `transactions.create` and `?bookmark_id=` prefilling `initialData`;
  renaming; reordering via move; deletion; and that another workspace's
  bookmark cannot be renamed, moved, or deleted (403). Full suite: 240 Pest
  tests / 1562 assertions, PHPStan/Larastan (0 errors), Pint, ESLint,
  Prettier, TypeScript checks (`vue-tsc`), and a production build all
  passed.
- **Decisions:** Scoped "edited" (from the catalog acceptance text "saved,
  reordered, edited, and reused") to renaming only — the bookmark's payload
  is fixed at save time and replaced by deleting and re-saving, keeping this
  slice minimal and consistent with existing draft/duplicate infrastructure.
- **Blockers:** None.
- **Uncommitted:** All `P3-13` changes are implemented but not yet committed
  on `feat/phase-3`.
- **Next:** Commit `P3-13` as `feat(transactions): add transaction bookmarks
  (P3-13)`, then continue with `P3-14` (Payment profiles).

### 2026-06-13 04:10 WIB - `P3-12` Bulk Selection

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-12`.
- **Status:** Completed.
- **Completed:** Confirmed with the user that, since posted transaction tags
  (`TransactionTag::guardPostedTransaction()`) and ledger entries are
  immutable, the only "allowed... operation" that can safely apply to
  multiple selected transactions is duplicate-as-draft. Added checkboxes to
  each transaction card on `transactions/Index.vue` and a bulk action bar
  (shown when one or more are selected) with "Duplicate as drafts" and
  "Clear selection". Added `POST transactions/bulk-duplicate`
  (`transactions.bulk-duplicate`), which validates `transaction_ids` (array,
  min 1, integers), loads them scoped to the current workspace via
  `$workspace->transactions()->whereIn('id', ...)` (ids outside the
  workspace are silently dropped), authorizes `view` on each, and calls the
  existing `DuplicateTransaction::duplicate()` once per transaction. A
  single resulting draft redirects to `transactions.drafts.edit` (matching
  the existing single-transaction duplicate flow); multiple drafts redirect
  to a new `GET transaction-drafts` (`transactions.drafts.index`) page.
  Added `TransactionController::drafts()` and a new minimal
  `transactions/Drafts.vue` page listing the workspace's draft transactions
  (description, type badge, last-updated time, "Resume draft" link to
  `transactions.drafts.edit`) — this was needed so multi-select duplicates
  are discoverable, since no drafts list previously existed.
- **Verification:** New `tests/Feature/TransactionBulkActionsTest.php` (5
  tests, 25 assertions) covers: single-selection duplicate redirects to the
  new draft's edit page; multi-selection duplicate creates one draft per
  transaction and redirects to the drafts index; the drafts index lists
  draft transactions with the correct id/description; a transaction from
  another workspace cannot be bulk-duplicated (redirects to
  `transactions.index`, no draft created); and an empty `transaction_ids`
  array is rejected by validation. Full suite: 234 Pest tests / 1520
  assertions, PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript
  checks (`vue-tsc`), and a production build all passed.
- **Decisions:** New routes were ordered so `transactions/bulk-duplicate`
  (POST) and `transaction-drafts` (GET, the new drafts index) are registered
  before `transaction-drafts/{transaction}/edit` and
  `transactions/{transaction}` — no collisions occur since they differ in
  HTTP method or path segment count, but ordering keeps the route list
  readable and consistent with the existing static-before-wildcard
  convention.
- **Blockers:** None.
- **Uncommitted:** All `P3-12` changes are ready to commit on `feat/phase-3`.
- **Next:** Commit `P3-12`, then continue Phase 3 with `P3-13` (transaction
  bookmarks).

### 2026-06-13 03:20 WIB - `P3-11` Transaction Detail

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-11`.
- **Status:** Completed.
- **Completed:** Added a `transactions/{transaction}` GET route
  (`->whereNumber('transaction')`, named `transactions.show`), registered
  after all static-segment transaction routes so it cannot shadow
  `transactions/create`, `/calendar`, `/weekly`, `/monthly`, `/summary`,
  `/day`, and `/day-notes/{date}`. `TransactionController::show()` authorizes
  via `TransactionPolicy::view`, loads the transaction with its creator,
  merchant, tags, entries (with account/category), and
  reversal/replacement relations, and renders `transactions/Show` with the
  full transaction detail plus its immutable audit log entries (ordered
  newest first, with actor and metadata). New `transactions/Show.vue` page
  shows description, memo, currency, occurred/posted timestamps, recorder,
  tags, all ledger entries, reversal/replacement cross-links
  (`reverses`/`reversal`/`replaces`/`replacement` as links to the related
  transaction's detail page), and an audit history card. Each transaction
  card on `transactions/Index.vue` now links its title to
  `transactions.show`. Regenerated Wayfinder routes
  (`resources/js/routes/transactions/index.ts`) to add the `show` helper.
- **Verification:** New `tests/Feature/TransactionDetailTest.php` (5 tests,
  108 assertions) covers: full detail rendering with entries, creator,
  currency, and a `transaction.posted` audit log entry; reversal cross-links
  and the `transaction.reversed` audit log on the original transaction (none
  on the reversal); replacement cross-links and the `transaction.replaced`
  audit log on the original transaction (none on the replacement);
  cross-workspace access returns 404; and `transactions/create` still
  resolves to `transactions/CreateTransaction` (no route collision with
  `transactions.show`). Full suite: 229 Pest tests / 1495 assertions, PHPStan
  / Larastan (0 errors), Pint, ESLint, Prettier, TypeScript checks
  (`vue-tsc`), and a production build all passed.
- **Decisions:** `posted_at` is read via `getRawOriginal()` + `Carbon::parse()`
  (matching the existing `occurred_at` pattern in
  `transformTransaction()`), since Larastan infers `string` for
  `immutable_datetime`-cast attributes accessed directly in some contexts.
  `Show.vue`'s `defineOptions({ layout: { breadcrumbs: [...] } })` only
  includes the static "Transactions" breadcrumb — a dynamic per-transaction
  breadcrumb referencing `props.transaction.id` is not possible because
  `defineOptions()` content is hoisted out of `setup()` and cannot reference
  setup-scope bindings (build error: "`defineOptions()` ... cannot reference
  locally declared variables").
- **Blockers:** None.
- **Uncommitted:** All `P3-11` changes are ready to commit on `feat/phase-3`.
- **Next:** Commit `P3-11`, then continue Phase 3 with `P3-12` (bulk
  selection).

### 2026-06-13 02:45 WIB - `P3-10` Pagination And Infinite Navigation

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-10`.
- **Status:** Completed.
- **Completed:** `TransactionController::index()` now wraps the existing
  `paginate(30)->withQueryString()` result in `Inertia::scroll($transactions)`
  before rendering, which configures merge behavior and normalizes pagination
  metadata for the frontend infinite-scroll component without changing the
  `data`/`links`/`meta` shape used by existing tests. `transactions/Index.vue`
  now renders the grouped transaction list inside `<InfiniteScroll
  data="transactions">` (from `@inertiajs/vue3`), which automatically loads
  and appends subsequent pages as the user scrolls near the end of the list,
  with a "Loading more transactions…" indicator via the `loading` slot. The
  previous manual page-number `<nav>` was removed since infinite scroll
  supersedes it; ordering remains deterministic (existing sort plus an `id`
  tiebreaker), so merged pages stay stable as history grows.
- **Verification:** Full suite: 224 Pest tests / 1387 assertions (existing
  `TransactionHistoryTest` pagination coverage still passes unchanged),
  PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript checks
  (`vue-tsc`), and a production build all passed.
- **Decisions:** None.
- **Blockers:** None.
- **Uncommitted:** All `P3-10` changes are ready to commit on `feat/phase-3`.
- **Next:** Commit `P3-10`, then continue Phase 3 with `P3-11` (transaction
  detail).

### 2026-06-13 02:10 WIB - `P3-09` Sorting

- **Branch:** `feat/phase-3`.
- **Feature IDs:** `P3-09`.
- **Status:** Completed.
- **Completed:** Added a `sort` query-string parameter to
  `TransactionController::index()` with an explicit whitelist: `date_desc`
  (default), `date_asc`, `amount_desc`, `amount_asc`, `description_asc`, and
  `description_desc`. Any other value falls back to `date_desc`. Date sorting
  orders by `occurred_at` (with `id` as a stable tiebreaker); description
  sorting orders by `description`. Amount sorting adds a correlated subquery
  selecting `MAX(ABS(amount))` from `transaction_entries` where
  `type = 'account'` for the transaction, and orders by that value — this
  represents the account-affecting leg even for split transactions and
  transfers. The response now includes `sort` (resolved value) and
  `sortOptions` (the whitelist) for the frontend. `transactions/Index.vue`
  gained a "Sort by" select wired into the existing filter form
  (`applyFilters`/`clearFilters`); when a non-date sort is active, the
  date-grouped section headings are replaced with a flat list showing each
  transaction's local date inline on its card.
- **Verification:** Added `tests/Feature/TransactionSortTest.php` (7 tests,
  106 assertions) covering the default order, `date_asc`, `amount_desc`,
  `amount_asc`, `description_asc`, `description_desc`, and an invalid sort
  value falling back to `date_desc`. Full suite: 224 Pest tests / 1387
  assertions, PHPStan/Larastan (0 errors), Pint, ESLint, Prettier, TypeScript
  checks (`vue-tsc`), and a production build all passed.
- **Decisions:** None.
- **Blockers:** None.
- **Uncommitted:** All `P3-09` changes are ready to commit on `feat/phase-3`.
- **Next:** Commit `P3-09`, then continue Phase 3 with `P3-10` (pagination and
  infinite navigation).

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
