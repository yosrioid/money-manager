# Product Scope

## Product Goal

Build a reliable personal finance web application for recording income,
expenses, transfers, budgets, liabilities, and assets. The first release targets
Indonesian users who are comfortable entering transactions manually.

The product may learn from common money-management workflows, but it must not
copy another application's branding, icon, text, screenshots, or visual design.

## Initial Users

- Individuals tracking cash, bank accounts, and e-wallets
- Users managing monthly budgets
- Users tracking credit cards, installments, savings, and debts
- Couples or families sharing a finance workspace in a later release

## MVP Features

### Authentication And Workspace

- Register, login, logout, password reset, and email verification
- Two-factor authentication
- One personal workspace per new user
- Strict workspace-level data isolation

### Accounts

- Cash, bank, e-wallet, savings, investment, asset, loan, and credit-card
  accounts
- Account groups, ordering, archive, and visibility settings
- Opening balance entered as a ledger transaction
- Asset, liability, and net-worth summaries

### Categories

- Income and expense categories
- Optional subcategories
- Custom name, icon, color, and display order
- Archive instead of destructive deletion after use

### Transactions

- Income, expense, and transfer transactions
- Balanced double-entry ledger
- Merchant, notes, tags, transaction date, and optional attachment
- Draft, posted, voided, and reversed lifecycle
- Search and filters
- Transaction templates or bookmarks

### Budgets And Reports

- Monthly expense budget per category
- Actual versus budget progress
- Monthly income and expense summary
- Spending breakdown by category
- Account balance and net-worth trend
- CSV export

## Post-MVP Features

- Recurring transactions
- Installment plans
- Credit-card billing cycles and settlements
- Multi-currency accounts and exchange rates
- Receipt attachment and OCR
- Excel import
- Shared workspaces and member roles
- Budget carry-over
- PWA installation and offline transaction drafts
- Bank or e-wallet integrations after security and provider evaluation

## Explicitly Out Of Scope For MVP

- Automatic Indonesian bank synchronization
- Automatic e-wallet or QRIS transaction capture
- Native iOS or Android application
- Investment market-price feeds
- Accounting reports for registered businesses
- Offline-first synchronization

## Non-Functional Requirements

- Financial correctness takes priority over convenience.
- Common dashboard queries should complete within 500 ms under expected load.
- Sensitive actions require authorization and audit logging.
- All user-facing flows must work on mobile and desktop layouts.
- Dates are stored in UTC and displayed using the workspace timezone.
- The initial interface supports Indonesian and English.

## MVP Success Criteria

- A user can create accounts and accurately record income, expenses, and
  transfers.
- Ledger entries always balance.
- Dashboard balances match the ledger.
- A user can create a monthly budget and compare it with actual spending.
- A user can export their transaction history.
- Automated tests protect all financial posting rules.

