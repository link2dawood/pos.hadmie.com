# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Ultimate POS** — a Laravel 9 Point-of-Sale application for retail businesses. Supports multi-location, multi-tenant operations with sales, purchasing, inventory, accounting, and 17+ pluggable modules.

## Common Commands

```bash
# Install dependencies
composer install
npm install

# Run dev server
php artisan serve

# Database setup
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed   # Reset and repopulate

# Frontend assets
npm run dev          # One-time build
npm run watch        # Watch for changes
npm run prod         # Production build

# Cache management
php artisan cache:clear
php artisan config:cache
php artisan view:clear

# Run tests (uses real DB — SQLite/in-memory lines are commented out in phpunit.xml)
php artisan test
./vendor/bin/phpunit
./vendor/bin/phpunit --filter TestClassName   # Run a single test
```

## Architecture

### Modular System

The app uses [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules). Module status is controlled by `modules_statuses.json`. Only **Repair** and **Superadmin** modules have source code in `/Modules/`; the remaining 17 modules (Essentials, Accounting, Ecommerce, CRM, etc.) are enabled in `modules_statuses.json` but their code is not present in this repo.

Each module follows: `Modules/<Name>/{Entities, Http/Controllers, Routes, Database/Migrations, Resources/views, module.json}`.

### Business Logic Utilities (`app/Utils/`)

Heavy business logic lives in utility classes injected into controllers via the service container:

| Utility | Responsibility |
|---|---|
| `TransactionUtil` | Core sales/purchase/transfer transaction creation, updates, payments |
| `ProductUtil` | Product creation, variation handling, stock calculations |
| `BusinessUtil` | Business and location management |
| `ContactUtil` | Customer/supplier operations |
| `CashRegisterUtil` | Till open/close, cash counts |
| `AccountTransactionUtil` | General ledger double-entry accounting |
| `TaxUtil` | Tax rate calculations and application |
| `RestaurantUtil` | Table booking and restaurant-mode operations |
| `NotificationUtil` | SMS/email notification dispatch |
| `ModuleUtil` | Module enable/disable, asset publishing |
| `Util` | Formatting, math helpers (numbers, currency, dates) |

Global helper functions are autoloaded from `app/Http/helpers.php`.

### Transaction System

`Transaction` is the central model. Key fields:
- `type`: `purchase`, `sell`, `expense`, `stock_adjustment`, `sell_transfer`, `purchase_transfer`, `opening_stock`, `sell_return`, `opening_balance`, `purchase_return`, `payroll`, `expense_refund`, `sales_order`, `purchase_order`
- `status`: `draft`, `ordered`, `received`, `final`, `in_transit`, `completed`
- `payment_status`: `paid`, `due`, `partial`

Line items live in `TransactionSellLine` (sales) and `PurchaseLine` (purchases). Payments in `TransactionPayment`. The join table `TransactionSellLinesPurchaseLines` links sell lines to their source purchase lines for COGS tracking.

### Multi-Tenancy

All queries are scoped to `business_id`. The authenticated user's `business_id` is set in session via `SetSessionData` middleware. Almost every model query includes `->where('business_id', $business_id)`.

### Authentication & Permissions

- Web: session-based via `CheckUserLogin` middleware
- API: Laravel Passport OAuth2 tokens (core `routes/api.php` is minimal; module APIs live in `Modules/<Name>/Routes/api.php`)
- Permissions: Spatie `laravel-permission` with role-based access; checked via `$request->user()->can('permission.name')` or `@can` blade directives

### Routes

`routes/web.php` (34KB) contains all web routes organized by feature. Route names follow `feature.action` convention (e.g., `contacts.index`, `sell.create`). `routes/install_r.php` handles the web installer flow. Module routes are in `Modules/<Name>/Routes/web.php`.

### Frontend

AdminLTE template with Blade views in `resources/views/` organized by feature. DataTables handles all listing pages via AJAX (controllers return JSON when `$request->ajax()`). Laravel Mix (`webpack.mix.js`) bundles assets.

**POS receipt templates** live in `resources/views/sale_pos/receipts/` (classic, detailed, elegant, elegant_modified, slim, slim2, columnize-taxes, delivery_note, packing_slip, download_pdf). The `custom_views/sale_pos/receipts/` directory can hold tenant-specific receipt overrides (e.g., `elegant.blade.php`) that take precedence over the defaults.

### Restaurant Mode

`app/Restaurant/` contains `Booking.php` and `ResTable.php` models for restaurant table management. `RestaurantUtil` handles the business logic. This is a sub-feature of the core app, not a separate module.

## Key Configuration

**Environment**: Copy `.env.example` to `.env`. Required variables:
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `APP_KEY` (generate with `php artisan key:generate`)
- `ENVATO_PURCHASE_CODE` / `MAC_LICENCE_CODE` — license validation

**Payment gateways** configured per-business in DB, but API keys live in `.env`: `STRIPE_*`, `PAYPAL_*`, `RAZORPAY_*`, `PAYSTACK_*`, `FLUTTERWAVE_*`.

**Real-time**: Pusher keys in `.env` (`PUSHER_APP_ID`, etc.) for live notifications.

**Modules**: Enable/disable by editing `modules_statuses.json` or via Superadmin UI.

## Testing

PHPUnit config in `phpunit.xml`. Tests use a **real database** (SQLite/in-memory options are commented out — ensure a test DB is configured). Seeders in `database/seeders/` — `DummyBusinessSeeder` sets up a complete test business.
