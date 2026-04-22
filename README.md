# SOS Labour Solutions Installation Guide

This repository contains the SOS Labour Solutions web application built with Laravel, Inertia, Vue, and Vite.

## Requirements

Before installing, make sure you have:

- [Laravel Herd](https://herd.laravel.com/)
- Node.js `18+`
- pnpm
- PostgreSQL

## Installation

1. Clone the repository.

```bash
git clone <your-repo-url>
cd soslaboursolutions
```

2. Make sure Laravel Herd is installed and running.

Laravel Herd already covers the local PHP and Composer setup, so you do not need to install those separately in most cases.

3. Make sure `pnpm` is installed.

```bash
pnpm --version
```

If `pnpm` is not installed yet, you can install it with Corepack:

```bash
corepack enable
corepack prepare pnpm@latest --activate
```

4. Install PHP dependencies.

```bash
composer install
```

5. Install frontend dependencies with `pnpm`.

```bash
pnpm install
```

6. Create your environment file and configure PostgreSQL.

```bash
cp .env.example .env
```

If you are on Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

After that, update your `.env` file:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Make sure the PostgreSQL database already exists before continuing.

7. Generate the application key.

```bash
php artisan key:generate
```

8. Run the database migrations.

```bash
php artisan migrate
```

## Running The App

Before running the app, make sure Laravel Herd is open and the Herd PATH setup is enabled on your machine.

Prerequisite:

- Open Laravel Herd
- Make sure Herd has added PHP and Composer to your system PATH
- Make sure this project is parked or linked in Herd


Then open the app in your browser using the Herd local domain:

```text
http://app_name.test
```

## One-Command Setup

You can also use the Composer setup script:

```bash
composer run setup
```

This will:

- install Composer dependencies
- create `.env` if it does not exist
- generate the app key
- run migrations
- install frontend dependencies
- build frontend assets

The recommended frontend workflow for this project is still the manual `pnpm` setup shown above.

## Helpful Commands

Run tests:

```bash
php artisan test
```

Format frontend files:

```bash
pnpm run format
```

Check frontend types:

```bash
pnpm run types:check
```

## Notes

- Laravel Herd handles the local PHP environment, which makes setup simpler for Windows-based development.
- If you use a database other than PostgreSQL, update the `DB_*` values in `.env` before running migrations.
- If `pnpm run build` or `pnpm run dev` fails because of frontend dependency issues, remove `node_modules` and reinstall with `pnpm install`.
