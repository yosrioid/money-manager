# Money Manager

Web-based personal finance manager inspired by the useful workflows of modern
expense trackers. This project will implement its own product identity, design,
and source code.

## Documentation

- [Product Scope](docs/PRODUCT_SCOPE.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Strict Project Rules](docs/STRICT_RULES.md)
- [Coding Standards](docs/CODING_STANDARDS.md)
- [Git Workflow](docs/GIT_WORKFLOW.md)
- [Feature Progress](docs/PROGRESS.md)

## Planned Stack

- Laravel 13
- PHP 8.4+
- PostgreSQL 18
- Vue 3 with TypeScript
- Inertia 3
- Tailwind CSS 4
- shadcn-vue
- Redis and Laravel Horizon
- Pest, Vitest, and Playwright

## Current Status

The Laravel and Vue foundation is installed. See
[Feature Progress](docs/PROGRESS.md) for the current implementation status and
next milestone.

## Local Setup

Requirements:

- PHP 8.4 or newer
- Composer 2
- Node.js 24 or newer
- PostgreSQL 18
- Redis for Horizon and production queue processing

Install and configure:

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
/opt/homebrew/opt/postgresql@18/bin/createdb money_manager
php artisan migrate
npm run build
```

Set the PostgreSQL username and password in `.env` for the local environment.
On Homebrew installations, the default PostgreSQL username is commonly the
macOS username.

Install and start the local data services with Homebrew when they are not
already available:

```bash
brew install postgresql@18 redis
brew services start postgresql@18
brew services start redis
```

When using Laravel Herd, the application is available at:

```text
http://money-manager.test
```

Run the development processes:

```bash
composer run dev
```

Run quality checks:

```bash
composer test
composer analyse
npm run lint:check
npm run format:check
npm run types:check
npm run test:unit
npm run build
```

## Core Engineering Rule

Financial balances must be derived from a balanced double-entry ledger. Never
store or update an account balance as an independent source of truth.
