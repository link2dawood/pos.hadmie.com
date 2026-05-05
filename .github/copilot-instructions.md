# Copilot Instructions for Ultimate POS

- This is a Laravel 9 application with `nwidart/laravel-modules` support. The root app lives under `app/`, `resources/views/`, and `routes/web.php`, while feature extensions are located in `Modules/`.
- `routes/web.php` is the main HTTP route registry. It defines auth, install, business setup, and core POS flows, and is the first place to check for URL/permission boundaries.
- Module activation is controlled by `Module::has(...)` and `App\nUtils\ModuleUtil::isModuleInstalled()`. In views and controllers, `Module::has('Repair')`, `Module::has('Essentials')`, and `Module::has('Superadmin')` gates feature visibility.
- The module loader is configured in `config/modules.php`; modules are stored in `Modules/`, and migrations may be published to `database/migrations`.
- Business logic frequently uses `System::getProperty(...)` and `ModuleUtil` to check installed versions, permissions, and superadmin subscription state.
- Auth routes and session middleware are applied globally in `routes/web.php` using middleware groups like `setData`, `SetSessionData`, `language`, `timezone`, `AdminSidebarMenu`, and `CheckUserLogin`.
- UI is Blade-driven and uses AdminLTE markup. Key UI files include `resources/views/layouts/partials/header.blade.php`, `resources/views/layouts/partials/header-pos.blade.php`, and `custom_views/sale_pos/receipts/`.
- There is no root `package.json`; frontend assets are mostly legacy vendor scripts under `resources/plugins/` and `public/`. Do not assume a JavaScript build step in the root workspace.
- Setup commands:
  - `composer install`
  - `cp .env.example .env`
  - `php artisan key:generate`
  - configure MySQL credentials in `.env`
  - `php artisan migrate`
- Tests are run with PHPUnit via `./vendor/bin/phpunit` or `php artisan test` if installed. Root test files live under `tests/`.
- Dependency awareness: this app integrates with external services such as Stripe, PayPal, Paystack, Razorpay, Pesapal, MyFatoorah, Dropbox, Pusher, and OpenAI. Environment keys are declared in `.env.example`.
- When editing controllers, prefer the existing Laravel pattern of full controllers and route resources over ad-hoc route closures.
- Avoid speculative refactors of module conditional logic without verifying `Module::has()` conditions and the `Modules/` folder contents.

> Ask for clarification if you need the current active module set, the install state of `Repair`/`Superadmin`, or the published database schema for a feature area.