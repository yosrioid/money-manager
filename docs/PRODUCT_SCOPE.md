# Product Scope

## Product Goal

Build a reliable personal finance web application for recording income,
expenses, transfers, budgets, liabilities, and assets. The first release targets
Indonesian users who are comfortable entering transactions manually.

The product may learn from common money-management workflows, but it must not
copy another application's branding, icon, text, screenshots, or visual design.

`docs/FEATURE_CATALOG.md` is the authoritative detailed product inventory.
`docs/MASTER_PLAN.md` defines the approved delivery phases and release gates.
This document defines product intent and release boundaries.

## Initial Users

- Individuals tracking cash, bank accounts, and e-wallets
- Users managing monthly budgets
- Users tracking credit cards, installments, savings, and debts
- Couples or families sharing a finance workspace in a later release

## MVP Boundary

The MVP requires the approved `MVP` capabilities through Phase 4 of
`docs/MASTER_PLAN.md`, followed by the applicable Phase 8 release gates.

At minimum, it provides:

- Secure identity, personal workspace, and strict workspace isolation
- Accounts, categories, merchants, tags, currency, timezone, and preferences
- Balanced income, expense, transfer, reversal, and transaction history
- Daily-use search, filters, calendar navigation, and fast-entry workflows
- Budgets, dashboard, statistics, reports, and supported data exchange
- Required production security, performance, recovery, and acceptance gates

## Parity Boundary

The parity release requires all approved `Parity` capabilities in
`docs/FEATURE_CATALOG.md` or an explicit approved deferral recorded during the
Phase 8 parity audit.

## Extended Boundary

Capabilities marked `Extended` are intentionally beyond reference parity or
require additional platform complexity. They are released only through explicit
scope approval and Phase 8 gates.

## Explicitly Deferred

- Automatic Indonesian bank synchronization
- Automatic e-wallet or QRIS transaction capture
- Native iOS or Android application
- Investment market-price feeds
- Accounting reports for registered businesses
- Offline-first synchronization

The full deferred inventory and reasons are maintained in
`docs/FEATURE_CATALOG.md`.

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
