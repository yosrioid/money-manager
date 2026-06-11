# Feature Catalog

## Purpose

This catalog is the authoritative inventory of planned product capabilities.
Every implementation change must reference one or more feature IDs from this
file. A feature not listed here is out of scope until it is added through the
scope-change process in `docs/MASTER_PLAN.md`.

The product is inspired by the useful workflows commonly available in Money
Manager Expense & Budget by Realbyte, but it must use original branding,
copywriting, interaction design, visual design, and source code.

## Research Baseline

This inventory was baselined on 2026-06-09 using:

- The official Money Manager Expense & Budget iOS App Store listing:
  <https://apps.apple.com/us/app/money-manager-expense-budget/id560481810>
- The official Realbyte website: <https://realbyteapps.com/>
- The official Money Manager Help Center and iOS user guides:
  <https://help.realbyteapps.com/hc/en-us>

The reference application can add or remove features after this baseline.
`P8-09` requires a fresh parity audit before a parity release. Newly discovered
features must enter the catalog through the scope-change process instead of
being implemented silently.

## Coverage Levels

- `Foundation`: engineering capability required before product features.
- `MVP`: required for the first usable production release.
- `Parity`: required to cover the known practical workflows of the reference
  application.
- `Extended`: valuable web-platform capability beyond reference parity.
- `Deferred`: explicitly excluded until a future scope decision.

## Phase 0 - Engineering Foundation

| ID    | Capability                             | Level      | Acceptance Summary                                                    |
| ----- | -------------------------------------- | ---------- | --------------------------------------------------------------------- |
| P0-01 | Laravel and Vue application foundation | Foundation | Laravel, Vue, Inertia, TypeScript, and Tailwind run locally           |
| P0-02 | PostgreSQL and Redis services          | Foundation | Application database, queue, and cache services are configured        |
| P0-03 | Automated quality gates                | Foundation | Formatting, linting, static analysis, tests, and builds run in CI     |
| P0-04 | Browser smoke testing                  | Foundation | Critical public page flow runs through Playwright                     |
| P0-05 | Git and contribution governance        | Foundation | Branch, commit, PR, authorship, and merge rules are documented        |
| P0-06 | Product and architecture baseline      | Foundation | Scope, architecture, catalog, master plan, and progress tracker exist |

## Phase 1 - Identity, Workspace, And Financial Setup

### Identity And Security

| ID    | Capability                             | Level    | Acceptance Summary                                                |
| ----- | -------------------------------------- | -------- | ----------------------------------------------------------------- |
| P1-01 | User registration                      | MVP      | A visitor can create an account with validated credentials        |
| P1-02 | Login and logout                       | MVP      | A registered user can securely start and end a session            |
| P1-03 | Password reset                         | MVP      | A user can request and complete a password reset                  |
| P1-04 | Email verification                     | MVP      | Restricted features require a verified email                      |
| P1-05 | Two-factor authentication              | MVP      | A user can enable, confirm, recover, and disable 2FA              |
| P1-06 | Passkey authentication                 | Extended | Supported users can register and use passkeys                     |
| P1-07 | Account security page                  | MVP      | Password, 2FA, passkeys, and active security state are manageable |
| P1-08 | Sensitive-action password confirmation | MVP      | Sensitive settings require recent password confirmation           |

### Workspace And Preferences

| ID    | Capability                   | Level  | Acceptance Summary                                                 |
| ----- | ---------------------------- | ------ | ------------------------------------------------------------------ |
| P1-09 | Automatic personal workspace | MVP    | Registration creates one private workspace and membership          |
| P1-10 | Active workspace context     | MVP    | Every financial request resolves an authorized workspace           |
| P1-11 | Workspace isolation          | MVP    | Cross-workspace reads and writes are denied and tested             |
| P1-12 | Workspace profile            | MVP    | User can edit workspace name and basic preferences                 |
| P1-13 | Default currency             | MVP    | New workspace defaults to IDR and can select another base currency |
| P1-14 | Workspace timezone           | MVP    | Dates display using the configured workspace timezone              |
| P1-15 | Locale and number format     | MVP    | Indonesian and English formats are supported                       |
| P1-16 | First day of week            | Parity | Calendar and weekly reports respect workspace preference           |
| P1-17 | Custom month start date      | Parity | Monthly views and reports can use a non-calendar month boundary    |
| P1-18 | Weekend-adjusted month start | Parity | Optional month start adjustment follows configured weekend rules   |

### Financial Reference Data

| ID    | Capability                  | Level  | Acceptance Summary                                                                      |
| ----- | --------------------------- | ------ | --------------------------------------------------------------------------------------- |
| P1-19 | Account groups              | MVP    | User can create, rename, reorder, hide, and archive account groups                      |
| P1-20 | Accounts                    | MVP    | Cash, bank, e-wallet, savings, investment, asset, loan, and card accounts are supported |
| P1-21 | Account opening balance     | MVP    | Opening balance is posted through the ledger                                            |
| P1-22 | Account ordering            | Parity | Accounts can be reordered within groups                                                 |
| P1-23 | Account visibility          | Parity | Accounts can be hidden from entry lists while optionally remaining in totals            |
| P1-24 | Account inclusion in totals | Parity | User controls whether an account contributes to summary totals                          |
| P1-25 | Account archive             | MVP    | Used accounts are archived instead of destructively deleted                             |
| P1-26 | Income categories           | MVP    | User can manage ordered income categories                                               |
| P1-27 | Expense categories          | MVP    | User can manage ordered expense categories                                              |
| P1-28 | Subcategories               | Parity | Categories support one child level and safe parent changes                              |
| P1-29 | Category appearance         | Parity | Category icon, color, visibility, and ordering are configurable                         |
| P1-30 | Merchants and recipients    | MVP    | Frequently used payees can be recorded and reused                                       |
| P1-31 | Tags                        | MVP    | Transactions can be labeled with reusable tags                                          |
| P1-32 | Session or application lock | Parity | User can require re-authentication after configured inactivity                          |
| P1-33 | Starter financial presets   | Parity | New workspace can opt into editable default account and category presets                |

### Public Experience

| ID    | Capability          | Level  | Acceptance Summary                                                                                                                     |
| ----- | ------------------- | ------ | -------------------------------------------------------------------------------------------------------------------------------------- |
| P1-34 | Public landing page | Parity | Unauthenticated visitors see a branded landing page consistent with the application theme, with entry points to login and registration |

## Phase 2 - Ledger And Core Transactions

### Ledger Foundation

| ID    | Capability                     | Level | Acceptance Summary                                                 |
| ----- | ------------------------------ | ----- | ------------------------------------------------------------------ |
| P2-01 | Double-entry transaction model | MVP   | Every posted transaction has balanced entries                      |
| P2-02 | Integer money storage          | MVP   | Money is stored in currency minor units without floating point     |
| P2-03 | Transaction lifecycle          | MVP   | Draft, posted, voided, reversed, and replaced states are enforced  |
| P2-04 | Atomic posting                 | MVP   | Transaction and entries post inside one database transaction       |
| P2-05 | Concurrent posting protection  | MVP   | Affected financial records are locked where required               |
| P2-06 | Immutable posted entries       | MVP   | Posted entries cannot be edited or deleted                         |
| P2-07 | Reversal and replacement       | MVP   | Corrections create auditable reversal and replacement transactions |
| P2-08 | Financial audit log            | MVP   | Sensitive transaction operations leave an audit trail              |
| P2-09 | Balance calculation service    | MVP   | Account balances derive only from posted entries                   |
| P2-10 | Balance-at-date calculation    | MVP   | Historical account balances can be calculated accurately           |

### Transaction Entry

| ID    | Capability                       | Level  | Acceptance Summary                                                   |
| ----- | -------------------------------- | ------ | -------------------------------------------------------------------- |
| P2-11 | Income entry                     | MVP    | User can post income to an authorized account and category           |
| P2-12 | Expense entry                    | MVP    | User can post expense from an authorized account and category        |
| P2-13 | Account transfer                 | MVP    | User can transfer between owned accounts with balanced entries       |
| P2-14 | Transfer fee                     | Parity | Transfer fee is recorded as a linked expense                         |
| P2-15 | Cash withdrawal workflow         | Parity | Bank-to-cash transfer is supported as a normal transfer              |
| P2-16 | Credit-card settlement transfer  | Parity | Bank-to-card settlement is supported without double-counting expense |
| P2-17 | Transaction date and time        | MVP    | User can record and edit draft-effective transaction time            |
| P2-18 | Merchant or recipient            | MVP    | Income and expense can reference a merchant or recipient             |
| P2-19 | Memo and notes                   | MVP    | Transaction supports searchable notes                                |
| P2-20 | Transaction tags                 | MVP    | Transaction can have multiple authorized tags                        |
| P2-21 | Split transaction                | Parity | One payment can be split across multiple categories                  |
| P2-22 | Calculator-assisted amount entry | Parity | Amount input supports safe arithmetic expressions                    |
| P2-23 | Save as draft                    | MVP    | Incomplete transaction can be saved without affecting balances       |
| P2-24 | Transaction duplication          | Parity | Existing transaction can seed a new draft                            |

## Phase 3 - Daily Use And Transaction Productivity

### History And Navigation

| ID    | Capability                         | Level  | Acceptance Summary                                                    |
| ----- | ---------------------------------- | ------ | --------------------------------------------------------------------- |
| P3-01 | Daily transaction history          | MVP    | Transactions are grouped by workspace-local date                      |
| P3-02 | Calendar view                      | Parity | Calendar shows daily income, expense, balance, and records            |
| P3-03 | Weekly view                        | Parity | Weekly summaries and drill-down are available                         |
| P3-04 | Monthly view                       | Parity | Monthly income and expense comparison is available                    |
| P3-05 | Summary view                       | Parity | Period summary combines budget and account movement                   |
| P3-06 | Daily memo                         | Parity | User can add a note to a calendar date                                |
| P3-07 | Search                             | MVP    | User can search by memo, merchant, category, account, and amount      |
| P3-08 | Advanced filters                   | MVP    | Date, type, category, account, tag, and status filters combine safely |
| P3-09 | Sorting                            | Parity | Supported transaction views have explicit safe sort options           |
| P3-10 | Pagination and infinite navigation | MVP    | Large histories remain responsive and deterministic                   |
| P3-11 | Transaction detail                 | MVP    | User can review full transaction, entries, audit state, and links     |
| P3-12 | Bulk selection                     | Parity | Allowed draft/reference operations can be applied in bulk             |

### Fast Entry And Personalization

| ID    | Capability                                     | Level    | Acceptance Summary                                                                          |
| ----- | ---------------------------------------------- | -------- | ------------------------------------------------------------------------------------------- |
| P3-13 | Transaction bookmarks                          | Parity   | Frequent transactions can be saved, reordered, edited, and reused                           |
| P3-14 | Payment profiles                               | Parity   | Reusable payment defaults accelerate transaction entry                                      |
| P3-15 | Recent values                                  | Parity   | Entry form can suggest recently used authorized values                                      |
| P3-16 | Favorite accounts and categories               | Parity   | Favorites appear first without changing financial meaning                                   |
| P3-17 | Entry-form field configuration                 | Parity   | Optional fields can be shown, hidden, and ordered                                           |
| P3-18 | Keyboard-first desktop entry                   | Extended | Core transaction entry works efficiently from keyboard                                      |
| P3-19 | Responsive mobile entry                        | MVP      | Core entry works on supported mobile viewport sizes                                         |
| P3-20 | Swipe and navigation preferences               | Parity   | User can configure supported navigation shortcuts                                           |
| P3-21 | Include or exclude transaction from statistics | Parity   | Authorized transaction can be excluded from selected analysis without changing ledger truth |

## Phase 4 - Budgets, Goals, Statistics, And Data Exchange

### Budgets And Goals

| ID    | Capability                | Level    | Acceptance Summary                                           |
| ----- | ------------------------- | -------- | ------------------------------------------------------------ |
| P4-01 | Category budget           | MVP      | Expense category can have a default recurring budget         |
| P4-02 | Monthly budget override   | Parity   | Individual month can override the default budget             |
| P4-03 | Weekly budget             | Parity   | Budget can be viewed and evaluated weekly                    |
| P4-04 | Annual budget             | Parity   | Budget can be viewed and evaluated annually                  |
| P4-05 | Income budget             | Parity   | Planned income can be configured separately                  |
| P4-06 | Total budget summary      | MVP      | User can compare total actual spending with total budget     |
| P4-07 | Recommended spending pace | Parity   | Budget shows expected spend-to-date for the period           |
| P4-08 | Budget trend              | MVP      | Historical actual-versus-budget trend is available           |
| P4-09 | Asset target              | Parity   | User can define and monitor a target net asset amount        |
| P4-10 | Budget carry-over         | Extended | Optional unused or overspent budget carries into next period |

### Statistics And Reports

| ID    | Capability                        | Level  | Acceptance Summary                                                 |
| ----- | --------------------------------- | ------ | ------------------------------------------------------------------ |
| P4-11 | Income and expense summary        | MVP    | Summary is available by supported period                           |
| P4-12 | Category spending statistics      | MVP    | Expense breakdown and drill-down are available                     |
| P4-13 | Merchant and recipient statistics | Parity | Spending can be grouped by merchant or recipient                   |
| P4-14 | Account statistics                | Parity | Activity and balance trend are available per account               |
| P4-15 | Asset and liability summary       | MVP    | User can see assets, liabilities, and net worth                    |
| P4-16 | Net-worth trend                   | MVP    | Historical net worth is charted from ledger data                   |
| P4-17 | Period comparison                 | Parity | Current period can be compared with previous periods               |
| P4-18 | Custom report filters             | Parity | Reports respect date, account, category, merchant, and tag filters |
| P4-19 | Dashboard customization           | Parity | User can configure supported summary cards and chart visibility    |

### Import And Export

| ID    | Capability                    | Level    | Acceptance Summary                                             |
| ----- | ----------------------------- | -------- | -------------------------------------------------------------- |
| P4-20 | CSV transaction export        | MVP      | Authorized filtered history can be exported safely             |
| P4-21 | Excel export                  | Parity   | Supported monthly and annual reports export to Excel           |
| P4-22 | Structured transaction import | Parity   | CSV or Excel transactions can be validated and imported        |
| P4-23 | Import preview and validation | Parity   | User reviews mappings, warnings, and errors before import      |
| P4-24 | Idempotent import             | MVP      | Retried import cannot silently duplicate accepted transactions |
| P4-25 | Asynchronous large export     | Extended | Large exports run through an authorized queue job              |

## Phase 5 - Cards, Debt, Assets, And Multi-Currency

### Credit Cards And Debt

| ID    | Capability                 | Level    | Acceptance Summary                                                |
| ----- | -------------------------- | -------- | ----------------------------------------------------------------- |
| P5-01 | Credit-card account model  | Parity   | Card balance and available information follow liability semantics |
| P5-02 | Billing cycle              | Parity   | Card closing and payment dates determine statement periods        |
| P5-03 | Outstanding card balance   | Parity   | User can see current and statement outstanding amounts            |
| P5-04 | Card settlement workflow   | Parity   | Settlement transfer reduces card liability correctly              |
| P5-05 | Debit-card account linkage | Parity   | Debit-card expense posts against its linked funding account       |
| P5-06 | Installment purchase       | Parity   | Purchase can generate a validated installment schedule            |
| P5-07 | Installment tracking       | Parity   | Paid, due, and remaining installments are visible                 |
| P5-08 | Loan account               | Parity   | Principal liability and repayment transfers are tracked           |
| P5-09 | Debt payoff progress       | Extended | User can monitor repayment progress without changing ledger truth |

### Assets And Multi-Currency

| ID    | Capability                            | Level    | Acceptance Summary                                                 |
| ----- | ------------------------------------- | -------- | ------------------------------------------------------------------ |
| P5-10 | Account currency                      | Parity   | Each account can use an explicitly configured currency             |
| P5-11 | Multi-currency transaction            | Parity   | Transaction stores source, target, and base-currency values safely |
| P5-12 | Exchange-rate entry                   | Parity   | User can record a decimal exchange rate with required precision    |
| P5-13 | Base-currency totals                  | Parity   | Workspace summaries convert supported balances consistently        |
| P5-14 | Exchange gain and loss handling       | Extended | Conversion differences are represented explicitly                  |
| P5-15 | Time-deposit and savings workflows    | Parity   | Restricted savings movements are represented as transfers          |
| P5-16 | Insurance and tracked-asset workflows | Parity   | Asset-like accounts can be included or excluded from totals        |

## Phase 6 - Automation, Attachments, Backup, And Restore

### Automation

| ID    | Capability                         | Level    | Acceptance Summary                                                 |
| ----- | ---------------------------------- | -------- | ------------------------------------------------------------------ |
| P6-01 | Recurring income and expense rules | Parity   | Rules generate due drafts or posted transactions idempotently      |
| P6-02 | Automatic transfer rules           | Parity   | Scheduled transfers generate balanced transactions safely          |
| P6-03 | Flexible recurrence frequency      | Parity   | Daily, weekly, monthly, annual, and supported custom patterns work |
| P6-04 | Future scheduled transactions      | Parity   | User can review and manage upcoming generated activity             |
| P6-05 | Automation failure handling        | Extended | Failures are visible, retryable, and do not duplicate transactions |
| P6-06 | Due reminders                      | Extended | User receives configured reminders for due activity                |

### Attachments

| ID    | Capability                      | Level    | Acceptance Summary                                         |
| ----- | ------------------------------- | -------- | ---------------------------------------------------------- |
| P6-07 | Transaction receipt attachments | Parity   | Income, expense, and transfer support private attachments  |
| P6-08 | Multiple attachments            | Parity   | Transaction supports up to the configured safe limit       |
| P6-09 | Attachment authorization        | MVP      | Cross-workspace attachment access is denied and tested     |
| P6-10 | Signed attachment downloads     | MVP      | Private files use short-lived authorized URLs              |
| P6-11 | Receipt OCR                     | Extended | OCR can suggest draft values without silently posting them |

### Backup And Restore

| ID    | Capability                     | Level    | Acceptance Summary                                                    |
| ----- | ------------------------------ | -------- | --------------------------------------------------------------------- |
| P6-12 | Workspace backup export        | Parity   | User can export a complete portable workspace backup                  |
| P6-13 | Backup restore preview         | Parity   | Restore validates compatibility and impact before applying            |
| P6-14 | Safe workspace restore         | Parity   | Restore is auditable, transactional, and protected from partial apply |
| P6-15 | Attachment backup export       | Parity   | Attachments can be exported with ownership metadata                   |
| P6-16 | Automatic server backup policy | Extended | Production data has tested automated backup and restore procedures    |

## Phase 7 - Collaboration, Sync, PWA, And Customization

### Shared Usage And Synchronization

| ID    | Capability                   | Level    | Acceptance Summary                                           |
| ----- | ---------------------------- | -------- | ------------------------------------------------------------ |
| P7-01 | Shared workspace invitations | Extended | Owner can invite and remove members safely                   |
| P7-02 | Workspace roles              | Extended | Owner, editor, and viewer permissions are enforced           |
| P7-03 | Member audit trail           | Extended | Sensitive member actions remain attributable                 |
| P7-04 | Multi-device synchronization | Extended | Changes become available consistently across active sessions |
| P7-05 | Conflict handling            | Extended | Conflicting edits are detected and resolved explicitly       |
| P7-06 | Real-time refresh            | Extended | Supported summaries refresh without unsafe stale writes      |

### Web Platform Experience

| ID    | Capability                             | Level    | Acceptance Summary                                                                      |
| ----- | -------------------------------------- | -------- | --------------------------------------------------------------------------------------- |
| P7-07 | Installable PWA                        | Extended | Supported devices can install the web application                                       |
| P7-08 | Offline transaction drafts             | Extended | Drafts can be created offline and synchronized safely                                   |
| P7-09 | Desktop management experience          | Parity   | Full transaction and account management works on desktop                                |
| P7-10 | Theme mode                             | Parity   | System, light, and dark modes are supported                                             |
| P7-11 | Theme color                            | Parity   | User can select from supported original color themes                                    |
| P7-12 | Accessible interface                   | MVP      | Critical flows meet keyboard, label, contrast, and focus requirements                   |
| P7-13 | Notification preferences               | Extended | User controls supported email and in-app notifications                                  |
| P7-14 | Multiple finance books                 | Parity   | User can maintain and switch between isolated finance workspaces                        |
| P7-15 | Transaction display preferences        | Parity   | User can configure supported transaction-list fields and density                        |
| P7-16 | Controlled workspace reset or deletion | Extended | Authorized user can safely reset or delete workspace data after export and confirmation |

## Phase 8 - Production Hardening And Parity Acceptance

| ID    | Capability                          | Level  | Acceptance Summary                                                                 |
| ----- | ----------------------------------- | ------ | ---------------------------------------------------------------------------------- |
| P8-01 | Security threat review              | MVP    | Authentication, authorization, uploads, exports, and financial writes are reviewed |
| P8-02 | Performance budgets                 | MVP    | Common dashboard and transaction queries meet agreed targets                       |
| P8-03 | Concurrency and idempotency audit   | MVP    | Critical financial writes and jobs are proven safe under retry                     |
| P8-04 | Browser and responsive acceptance   | MVP    | Critical journeys pass supported desktop and mobile browser tests                  |
| P8-05 | Localization acceptance             | MVP    | Indonesian and English critical flows are complete                                 |
| P8-06 | Observability                       | MVP    | Production errors, queues, and scheduled jobs can be monitored                     |
| P8-07 | Disaster-recovery rehearsal         | MVP    | Backup restoration is documented and tested                                        |
| P8-08 | Data-retention and privacy controls | MVP    | User data lifecycle and export/delete obligations are defined                      |
| P8-09 | Reference-feature parity audit      | Parity | Every Parity item is implemented, deferred explicitly, or rejected with rationale  |
| P8-10 | Release readiness review            | MVP    | Product, engineering, security, operations, and documentation gates pass           |

## Deferred Capabilities

The following capabilities are not approved for implementation:

| ID   | Capability                                  | Reason                                                         |
| ---- | ------------------------------------------- | -------------------------------------------------------------- |
| D-01 | Automatic Indonesian bank synchronization   | Provider, legal, security, and reliability evaluation required |
| D-02 | Automatic e-wallet or QRIS capture          | Provider and security evaluation required                      |
| D-03 | Native iOS and Android applications         | Web and PWA delivery are the current platform strategy         |
| D-04 | Investment market-price feeds               | Requires external data-provider scope                          |
| D-05 | Business accounting and tax reports         | Product targets personal finance                               |
| D-06 | Copying reference branding or visual design | Product must maintain an original identity                     |

## Traceability Rules

1. Every branch and PR must identify the relevant feature ID.
2. Every feature implementation must define acceptance criteria before coding.
3. `docs/PROGRESS.md` tracks current status; this catalog defines scope.
4. A feature may move between phases only through the change-control process.
5. A feature cannot be marked `Done` while any required acceptance criterion,
   test, documentation update, or release gate is incomplete.
