# Architecture

## Architecture Style

Use a modular monolith. Laravel owns authentication, authorization, business
logic, persistence, queues, and scheduled jobs. Vue pages are delivered through
Inertia, avoiding a separate public API during the MVP.

```text
Browser
  -> Laravel routes and middleware
  -> Controllers or Actions
  -> Domain services
  -> Eloquent repositories and PostgreSQL
  -> Inertia responses
  -> Vue pages
```

Do not introduce microservices, event sourcing, or a separate SPA API unless a
measured product requirement justifies the additional complexity.

## Technology Baseline

| Area | Choice |
|---|---|
| Runtime | PHP 8.4+ |
| Backend | Laravel 13 |
| Database | PostgreSQL 18 |
| Frontend | Vue 3 Composition API and TypeScript |
| Web bridge | Inertia 3 |
| Styling | Tailwind CSS 4 and shadcn-vue |
| Charts | Apache ECharts |
| Build | Vite |
| Queue and cache | Redis |
| Queue monitoring | Laravel Horizon |
| Object storage | S3-compatible storage |
| Backend tests | Pest |
| Frontend tests | Vitest |
| End-to-end tests | Playwright |
| Static analysis | Larastan |
| Formatting | Laravel Pint, ESLint, and Prettier |

## Module Boundaries

Organize business behavior by domain instead of placing all logic in
controllers or models.

```text
app/
  Domain/
    Accounts/
    Budgets/
    Categories/
    Ledger/
    Reports/
    Workspaces/
  Http/
    Controllers/
    Requests/
    Resources/
  Models/
  Policies/
  Jobs/
  Console/

resources/js/
  components/
  composables/
  layouts/
  pages/
  types/
```

Domain folders may contain actions, data objects, enums, exceptions, queries,
and services. Eloquent models remain in `app/Models` unless the codebase later
establishes a better convention.

## Core Data Model

```text
users
workspaces
workspace_members

currencies
accounts
account_groups
categories
merchants
tags

transactions
transaction_entries
transaction_attachments
transaction_tags

budgets
budget_categories

recurring_rules
installment_plans
audit_logs
```

All workspace-owned records must include `workspace_id` directly or have an
unambiguous relationship to a workspace-owned parent.

## Ledger Rules

`transactions` store the business event and its lifecycle. Each transaction has
two or more `transaction_entries`.

Example transfer of IDR 500,000 from BCA to Cash:

```text
BCA   -500000
Cash  +500000
Total        0
```

Example IDR 50,000 food expense paid from BCA:

```text
BCA               -50000
Expense: Food      50000
Total                  0
```

Required invariants:

- Money uses integer minor units.
- Every posted transaction balances to zero in its base currency.
- Ledger posting occurs inside a database transaction.
- Concurrent postings lock affected account rows.
- Posted entries cannot be edited or deleted.
- A correction creates a reversal and a replacement transaction.
- Account balances are calculated from posted entries.

## Service Boundaries

Expected core services:

- `PostTransaction`: validates and atomically posts ledger entries.
- `ReverseTransaction`: creates an auditable reversing transaction.
- `CalculateAccountBalance`: calculates balance at a given date.
- `CalculateBudgetUsage`: calculates actual spending against budget.
- `GenerateRecurringTransactions`: creates due transactions idempotently.
- `ImportTransactions`: validates and imports structured transaction files.

Services must accept validated data objects and must not depend on HTTP request
objects.

## Authorization And Isolation

- Use Laravel Policies for workspace-owned resources.
- Never trust a `workspace_id` provided by the browser without membership
  verification.
- Resolve workspace context in middleware.
- Scope route model binding and queries to the active workspace.
- Test attempts to access another workspace's data.

## Background Jobs

Use queues for:

- Report and export generation
- Attachment processing
- Import processing
- Notification delivery
- Recurring transaction generation

Every job must be idempotent and safe to retry. Scheduled jobs must use overlap
protection where duplicate execution could affect financial data.

## Attachments

- Store files outside the public web root using private object storage.
- Persist metadata and ownership in `transaction_attachments`.
- Use signed temporary URLs for downloads.
- Validate file type, size, and ownership.
- Strip unsafe metadata where practical.

## Testing Strategy

Highest-priority tests:

- Ledger balance invariant
- Income, expense, and transfer posting
- Reversal behavior
- Concurrent posting behavior
- Workspace isolation
- Budget calculations
- Credit-card and installment calculations when introduced

Use:

- Unit tests for calculations and data objects
- Feature tests for HTTP flows, policies, and persistence
- Browser tests only for critical user journeys

## Deployment Shape

Initial production deployment:

```text
Web application
PostgreSQL
Redis
Queue workers
Scheduler
Private object storage
Error monitoring
Automated backups
```

Keep application servers stateless. Store sessions, queues, and cache in Redis;
store uploaded files in object storage.

