# Strict Project Rules

These rules are mandatory for every human developer, AI assistant, automation,
script, and integration working in this repository.

When rules conflict, follow the stricter rule. Stop and request explicit user
approval before performing an action that violates or bypasses these rules.

## Approval Rules

- Never create a commit unless the user explicitly requests a commit.
- Never push unless the user explicitly requests a push.
- Never create, merge, close, or modify a pull request unless the user
  explicitly requests it.
- Never create or push a Git tag unless the user explicitly requests it.
- Never publish a release unless the user explicitly requests it.
- Never change dependencies, application services, or deployment configuration
  without explicit user approval.
- Never delete, revert, overwrite, or discard user changes without explicit
  user approval.
- Never perform destructive filesystem, database, Git, or infrastructure
  operations without explicit user approval.
- Keep every change within the requested scope. Do not add unrelated refactors
  or features.

## Git Branch Rules

- Never commit directly to `main`.
- Never push directly to `main`.
- Never force push to `main`.
- Never bypass branch protection or required reviews.
- Before creating a commit, create or switch to a short-lived non-`main`
  branch.
- All changes must reach `main` through a reviewed pull request.
- Do not merge a pull request without explicit user approval.
- Keep branches focused on one feature, bug fix, or engineering concern.
- Do not rewrite shared branch history unless the user explicitly requests it.
- When rewriting an approved shared branch, use `--force-with-lease`; never use
  plain `--force`.

## Commit Rules

- Use Conventional Commits:
  `<type>(<scope>): <imperative summary>`.
- Write commit messages in English.
- Use imperative mood such as `add`, `fix`, or `prevent`.
- Keep one logical concern per commit.
- Review staged changes before committing.
- Never commit secrets, `.env`, credentials, tokens, private keys, generated
  build output, dependency directories, test reports, or unrelated changes.
- Do not amend or rewrite an existing commit unless explicitly requested.

## Authorship And Attribution Rules

- Commit authorship must represent the human developer only.
- Never add Codex, Claude, ChatGPT, an AI assistant, bot, or automated tool as
  an author, co-author, committer, signer, reviewer, or attribution.
- Never add `Co-authored-by`, `Generated-by`, `Assisted-by`, or similar AI
  attribution trailers or messages.
- Do not add AI attribution to commits, tags, pull requests, changelogs,
  release notes, documentation, or generated artifacts.

## Financial Integrity Rules

- Use double-entry bookkeeping for every financial transaction.
- Every posted transaction must balance to zero in its base currency.
- Store monetary values as integers in the currency's smallest unit.
- Never use `float` or `double` for money.
- Store currency identifiers wherever currency is not unambiguously implied.
- Use decimal arithmetic for exchange-rate calculations.
- Derive account balances from posted ledger entries.
- Never maintain an independently mutable account balance as a source of truth.
- Posted ledger entries are immutable.
- Correct posted transactions using reversal transactions and replacement
  transactions.
- Never silently repair or auto-balance invalid ledger input.
- Perform ledger writes inside database transactions.
- Lock affected records when concurrent financial writes could conflict.
- Preserve an audit trail for sensitive financial changes.
- Never hard-delete posted financial history.

## Workspace And Authorization Rules

- Every user-owned financial record must belong to a workspace.
- Scope every financial query to the active authorized workspace.
- Never trust a `workspace_id` supplied by the browser without membership
  verification.
- Authorize every workspace-owned resource operation.
- Use Laravel Policies for resource authorization.
- Test attempts to access another workspace's data.
- Never expose another workspace's identifiers, files, reports, or financial
  data.

## Backend Rules

- Keep controllers limited to orchestration and response creation.
- Place multi-step business rules in domain actions or services.
- Domain services must not depend on HTTP request objects.
- Use Form Requests for HTTP validation.
- Use typed parameters, properties, and return values.
- Use PHP enums for stable business states.
- Use database constraints in addition to application validation.
- Never mass-assign unvalidated request data.
- Avoid model observers for critical financial behavior.
- Avoid raw SQL when Eloquent or the query builder can express the operation
  clearly and efficiently.
- Never modify an old production migration; create a new migration.
- Make migrations reversible when reasonably possible.
- Jobs and scheduled tasks must be idempotent and safe to retry.

## Frontend Rules

- Use Vue Composition API with `<script setup lang="ts">`.
- Never use TypeScript `any`; use explicit types or narrow `unknown`.
- Do not place business or financial calculations in Vue pages.
- Treat server-provided persisted financial data as the source of truth.
- Extract reusable UI into components and reusable behavior into composables.
- Use Pinia only when state genuinely spans unrelated pages or components.
- Keep frontend validation aligned with backend validation.
- Ensure interactive elements are keyboard-accessible and labeled.

## Data, Files, And Security Rules

- Store timestamps in UTC and convert them at application boundaries.
- Keep the workspace timezone as explicit configuration.
- Validate file type, size, ownership, and authorization for every upload.
- Store uploaded receipts and financial files privately.
- Use signed temporary URLs for private file downloads.
- Never log passwords, tokens, secrets, complete financial exports, or receipt
  contents.
- Return actionable user-facing errors without exposing internal details.
- Rate-limit authentication, imports, exports, and expensive reports.
- Keep dependencies updated and review security advisories.

## Testing Rules

- Every behavioral change requires an automated test.
- Every confirmed bug fix requires a regression test.
- Test financial invariants, authorization, validation failures, and
  concurrency when relevant.
- Do not rely on test execution order.
- Use factories and explicit factory states.
- Test behavior and contracts instead of framework internals.
- Do not mark a feature complete while applicable tests fail.

## Required Quality Gates

Run all applicable checks before requesting review:

```bash
composer ci:check
npm run build
npm run test:e2e
composer audit
npm audit --audit-level=high
```

- Do not bypass failing quality gates.
- Do not remove or weaken tests merely to make checks pass.
- Document checks that cannot run and explain the blocking condition.

## Documentation And Progress Rules

- Read `docs/PRODUCT_SCOPE.md`, `docs/ARCHITECTURE.md`,
  `docs/CODING_STANDARDS.md`, `docs/GIT_WORKFLOW.md`, and `docs/PROGRESS.md`
  before implementing a feature.
- Update documentation when behavior, architecture, setup, or workflow changes.
- Update `docs/PROGRESS.md` in the same change when a feature starts or
  completes.
- Mark a feature `Done` only after its acceptance criteria, tests, quality
  gates, review requirements, and documentation are complete.
- Record incomplete follow-up work explicitly.
- Do not rewrite historical progress entries except to correct factual errors.

## Definition Of Done

A change is complete only when:

- Requested scope and acceptance criteria are satisfied.
- Authorization and validation are handled.
- Financial and workspace invariants are preserved.
- Automated tests are added or updated and pass.
- Applicable formatting, linting, static analysis, type checks, builds, and
  browser tests pass.
- Security implications are handled.
- Relevant documentation and `docs/PROGRESS.md` are updated.
- No secrets, generated artifacts, or unrelated changes are included.
- The change is ready for review on a non-`main` branch.
