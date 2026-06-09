# Coding Standards

## General Principles

- Prefer clear, explicit code over clever abstractions.
- Keep changes focused and avoid unrelated refactors.
- Model business rules in domain services, not controllers or Vue pages.
- Add abstractions only when they remove meaningful duplication or enforce an
  important invariant.
- Use English for code, database names, commits, and technical documentation.
- User-facing text may be translated into Indonesian and English.

## PHP And Laravel

- Follow PSR-12 and format with Laravel Pint.
- Enable strict types in new standalone PHP files where compatible with Laravel
  conventions.
- Use typed properties, parameters, and return values.
- Use PHP enums for stable business states.
- Use Form Requests for HTTP validation.
- Use Policies for resource authorization.
- Use database constraints in addition to application validation.
- Use actions or domain services for multi-step business operations.
- Keep controllers limited to orchestration and response creation.
- Avoid model observers for critical financial behavior because they hide
  execution flow.
- Avoid mass assignment of unvalidated request data.
- Avoid raw SQL unless Eloquent or the query builder cannot express the query
  clearly or efficiently.

## Money And Dates

- Store money as `BIGINT` minor units.
- Never use floating-point arithmetic for money.
- Store the currency code with monetary values where currency is not implied.
- Perform exchange-rate calculations using a decimal arithmetic library.
- Store timestamps in UTC.
- Store the workspace timezone separately and convert only at boundaries.
- Use immutable date objects for domain calculations.

## Database

- Use plural snake_case table names and singular snake_case foreign keys.
- Add foreign keys, indexes, unique constraints, and check constraints where
  they protect correctness.
- Migrations must be reversible when reasonably possible.
- Never modify an old production migration; create a new migration.
- Avoid nullable columns unless absence has a clear meaning.
- Use soft deletes only when the domain requires restoration. Prefer explicit
  archive states for financial reference data.
- Never hard-delete posted ledger data.

## Vue And TypeScript

- Use Vue Composition API with `<script setup lang="ts">`.
- Do not use `any`; define explicit types or use `unknown` and narrow it.
- Keep pages responsible for page composition, not business calculations.
- Extract reusable UI into components and reusable behavior into composables.
- Use server-provided data as the source of truth for persisted financial data.
- Use Pinia only for state that genuinely spans unrelated pages or components.
- Keep form validation messages aligned with Laravel validation responses.
- Ensure interactive elements are keyboard-accessible and labeled.

## API And Inertia Responses

- Return only data needed by the current page.
- Use Laravel Resources or explicit data objects for complex response shapes.
- Avoid exposing internal IDs or fields without a UI requirement.
- Paginate transaction lists.
- Use lazy or deferred Inertia props for expensive reports.
- Validate sorting and filtering fields against allowlists.

## Error Handling

- Throw domain-specific exceptions for business-rule violations.
- Do not silently repair unbalanced ledger input.
- Return actionable user-facing errors without exposing internal details.
- Log failures with relevant identifiers, excluding secrets and unnecessary
  financial data.

## Security

- Authorize every workspace-owned resource operation.
- Protect sensitive actions with password confirmation when appropriate.
- Rate-limit authentication, imports, exports, and expensive reports.
- Validate and privately store uploads.
- Never log passwords, tokens, complete financial exports, or uploaded receipts.
- Keep dependencies updated and review security advisories.

## Automated Quality Checks

Expected commands after project scaffolding:

```bash
composer test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run type-check
npm run lint
npm run test:unit
npm run test:e2e
npm run build
```

Not every narrow change requires the full suite, but pull requests must run all
applicable checks in CI.

## Test Standards

- Follow Arrange, Act, Assert.
- Test behavior and invariants instead of framework internals.
- Give tests descriptive names.
- Include authorization and validation failure cases.
- Do not rely on test execution order.
- Use factories with explicit state methods.
- Add regression tests before fixing confirmed bugs.

## Definition Of Done Checklist

- Acceptance criteria satisfied
- Authorization and validation handled
- Financial invariants preserved
- Tests added or updated
- Relevant tests passing
- Formatting and static analysis passing
- Documentation updated
- `docs/PROGRESS.md` updated

