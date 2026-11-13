# Link Management

An in-house, Linktree-style application. Authenticated users manage collections of links; each collection will eventually have a permanent public URL suitable for programming onto an NFC card.

This repository currently contains the bootstrap only: authentication, an authenticated dashboard, and layouts. Link groups, links, public pages, NFC/QR, and analytics are not implemented yet.

## Stack

- Laravel 13, PHP 8.3+
- Blade + Tailwind CSS 4 + Alpine.js, compiled with Vite
- PostgreSQL
- PHPUnit

## Requirements

- PHP 8.3+ with `pdo_pgsql`
- Composer 2
- Node.js 20+ and npm
- PostgreSQL 15+ (MySQL works too; adjust `DB_*` in `.env`)

## Local setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Create the database (PostgreSQL)
createdb l510_links
# Then set DB_USERNAME / DB_PASSWORD in .env for your local Postgres role.

php artisan migrate
php artisan db:seed   # optional: creates test@example.com / password

npm run build
```

## Development

```bash
composer dev          # runs the app server, queue, logs and Vite together
# or individually:
php artisan serve     # http://localhost:8000
npm run dev           # Vite dev server with hot reload
```

Mail uses the `log` driver locally, so password reset links are written to `storage/logs/laravel.log`.

## Testing and code style

```bash
php artisan test      # or: composer test
vendor/bin/pint       # format PHP code
npm run build         # verify frontend assets compile
```

Tests run against an in-memory SQLite database (see `phpunit.xml`), so they do not touch the development database.

## Authentication

Authentication is plain controller-based Blade auth using Laravel's built-in services (no starter-kit package, Livewire, or Inertia):

| Route | Purpose |
| --- | --- |
| `GET/POST /login` | Log in (rate limited to 5 attempts per email/IP) |
| `POST /logout` | Log out |
| `GET/POST /forgot-password` | Request a password reset link |
| `GET /reset-password/{token}`, `POST /reset-password` | Set a new password |
| `GET /dashboard` | Authenticated dashboard |

Public registration is intentionally disabled. Until an admin invite flow exists, create users with the seeder or Tinker:

```bash
php artisan tinker
>>> App\Models\User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'a-strong-password']);
```

## Project layout

- `routes/web.php`, `routes/auth.php` — application and auth routes
- `app/Http/Controllers/Auth/` — login/logout and password reset controllers
- `app/Http/Requests/Auth/` — Form Requests for auth validation
- `resources/views/components/layouts/app.blade.php` — authenticated dashboard layout (`<x-layouts.app>`)
- `resources/views/components/layouts/guest.blade.php` — login / password reset layout (`<x-layouts.guest>`)
- `resources/views/components/layouts/public.blade.php` — layout for future public link pages (`<x-layouts.public>`)
