# SOS Labour Solutions Installation Guide

This repository contains the SOS Labour Solutions web application built with Laravel, Inertia, Vue, and Vite.

## Requirements

Before installing, make sure you have:

- PHP `8.3+`
- Composer
- Node.js `18+` and npm
- SQLite

## Installation

1. Clone the repository.

```bash
git clone <your-repo-url>
cd soslaboursolutions
```

2. Install PHP dependencies.

```bash
composer install
```

3. Install frontend dependencies.

```bash
npm install
```

4. Create your environment file.

```bash
cp .env.example .env
```

If you are on Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

5. Generate the application key.

```bash
php artisan key:generate
```

6. Configure the database.

The project defaults to SQLite. Update `.env` if needed:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

If the SQLite file does not exist yet, create it:

```bash
touch database/database.sqlite
```

If you are on Windows PowerShell:

```powershell
New-Item database\database.sqlite -ItemType File
```

7. Run the database migrations.

```bash
php artisan migrate
```

## Running The App

Start the Laravel server and Vite development server in separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

Then open the local URL shown by Laravel, usually:

```text
http://127.0.0.1:8000
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
- install npm dependencies
- build frontend assets

## Helpful Commands

Run tests:

```bash
php artisan test
```

Format frontend files:

```bash
npm run format
```

Check frontend types:

```bash
npm run types:check
```

## Notes

- If you use MySQL or another database, update the `DB_*` values in `.env` before running migrations.
- If `npm run build` or `npm run dev` fails on Windows because of native dependency issues, remove `node_modules` and reinstall dependencies.
