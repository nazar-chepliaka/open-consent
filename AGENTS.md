# AGENTS.md — Open Consent

This file contains repository-specific instructions for coding agents working on Open Consent.

Read this file before making changes or running project commands.

## Project

Open Consent is a server-rendered Laravel application.

Main stack:

- Laravel
- PHP
- PostgreSQL
- Blade
- CoreUI Free
- SCSS
- native JavaScript
- Laravel Vite

Do not introduce additional frontend frameworks unless explicitly requested.

In particular, do not add:

- Vue
- React
- Angular
- Livewire
- Inertia
- Alpine.js
- jQuery

Keep the application as a Laravel monolith.

Before architectural changes, also read `open-consent-spec.md` if it exists.

---

## Node.js / NVM

Node.js is managed through NVM.

Important: `nvm` is a shell function, not a standalone executable.

Therefore, do NOT use the following as a test for whether NVM is installed:

```bash
which nvm
```

A failed `which nvm` does NOT mean that NVM is unavailable.

Before running Node/npm commands, initialize NVM explicitly:

```bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
```

In this environment, sourcing `nvm.sh` may return a non-zero status because of NVM auto-use/default-alias behavior even though NVM itself was loaded successfully.

Therefore, when necessary use:

```bash
export NVM_DIR="$HOME/.nvm"
source "$NVM_DIR/nvm.sh" || true
```

Then verify the actual environment with:

```bash
type nvm
node --version
npm --version
```

The project currently builds successfully with:

```text
Node.js v24.21.0
npm 11.19.0
```

Before frontend build commands, use:

```bash
export NVM_DIR="$HOME/.nvm"
source "$NVM_DIR/nvm.sh" || true
nvm use v24.21.0
```

Then run, as appropriate:

```bash
npm install
npm run build
```

Do not report that Node.js or NVM is unavailable before attempting the initialization above.

If `.nvmrc` exists, prefer:

```bash
nvm use
```

over hardcoding the Node.js version.

---

## Frontend build

The frontend is built through Laravel Vite.

Use the existing `package.json` and Vite configuration.

Do not introduce another build system such as Webpack, Gulp, Parcel, or a separate frontend application.

For a production build:

```bash
export NVM_DIR="$HOME/.nvm"
source "$NVM_DIR/nvm.sh" || true
nvm use v24.21.0
npm run build
```

A successful build should be reported explicitly.

---

## PHP / Laravel

Before reporting a Laravel test failure, distinguish between:

1. application/test failures;
2. missing PHP extensions or other environment problems.

Run:

```bash
php --version
php -m
```

when the PHP environment is relevant.

Laravel tests currently expect an SQLite in-memory database.

If tests fail because `pdo_sqlite` / SQLite support is unavailable, report this as an environment dependency problem rather than an application test failure.

Do not modify application code, database configuration, or tests merely to work around a missing PHP extension unless explicitly requested.

Typical test command:

```bash
php artisan test
```

If it cannot run because `pdo_sqlite` is unavailable, state that clearly in the final report.

---

## Working rules

Before modifying the project:

1. Inspect the repository.
2. Read this `AGENTS.md`.
3. Read `open-consent-spec.md` when the task touches architecture or domain behavior.
4. Inspect existing implementation before creating replacements.
5. Preserve existing architecture unless the task explicitly requires changing it.

Prefer minimal changes that fit the existing codebase.

Do not install packages merely because they make implementation easier if Laravel, CoreUI, PHP, CSS, or native JavaScript already provide the required functionality.

Do not silently change architectural decisions.

When something fails because of the execution environment, investigate the environment before changing project code.

---

## Validation

After relevant changes, run the checks applicable to the task.

Laravel:

```bash
php artisan test
```

Frontend:

```bash
export NVM_DIR="$HOME/.nvm"
source "$NVM_DIR/nvm.sh" || true
nvm use v24.21.0
npm run build
```

Routes, when relevant:

```bash
php artisan route:list
```

Do not claim that a check passed unless it was actually executed successfully.

Do not claim that a check failed because of application code when the actual cause is a missing system dependency.

At the end of a task, report:

- files changed;
- important implementation decisions;
- checks executed;
- checks passed;
- checks blocked by the environment;
- remaining TODOs or unresolved issues.
