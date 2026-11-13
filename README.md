# Link Management

An in-house, Linktree-style application. Authenticated users manage collections of links; each collection will eventually have a permanent public URL suitable for programming onto an NFC card.

Implemented so far: authentication, the link group data model and policies, link group management (create, edit, activate/deactivate, soft delete, copy permanent URL), and link management (add, inline edit, enable/disable, delete, drag-and-drop or Move up/down ordering), and public pages at `/p/{slug}`. Appearance customization, NFC/QR, and analytics are not implemented yet.

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
php artisan storage:link   # serves group avatars from storage/app/public
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
| `GET /dashboard` | Authenticated dashboard listing your link groups |
| `GET /groups/create`, `POST /groups` | Create a link group (slug generated from the name) |
| `GET /groups/{group}/edit`, `PUT /groups/{group}` | Edit name, description, active status |
| `DELETE /groups/{group}` | Soft-delete a group (links and slug are kept) |
| `GET/POST /groups/{group}/links` | Link management screen; add a link (appended to the end) |
| `PATCH/DELETE /groups/{group}/links/{link}` | Edit, enable/disable, or delete a link (scoped to the group) |
| `PATCH /groups/{group}/links/order` | Save link order (JSON `{"links": [ids...]}`; must list every link exactly once) |

Link URLs must be `http` or `https`. A URL entered without a scheme that looks like a domain (`example.com/page`) is saved as `https://example.com/page`; any other input is stored exactly as typed or rejected. Requests for another user's groups or links return 404.

## Public pages

`GET /p/{slug}` (`public.groups.show`, `PublicLinkGroupController`) is the permanent public page for a group. It needs no login, shows only active links in saved order, and returns 404 for unknown, inactive, or soft-deleted groups; reactivating a group brings the same URL back. The route runs without the session, cookie, and CSRF middleware, so anonymous visits (for example NFC taps) create no session rows or cookies. The page loads only CSS (no JavaScript). Stored links that are not absolute `http(s)` URLs are shown as unavailable rather than as links. An avatar is shown when `avatar_path` points to an existing image (jpg, jpeg, png, gif, webp, avif) on the `public` disk.

Public registration is intentionally disabled. Until an admin invite flow exists, create users with the seeder or Tinker:

```bash
php artisan tinker
>>> App\Models\User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'a-strong-password']);
```

## Data model

- `link_groups` — owned by a user; published at `/p/{slug}`. Slugs are generated from the name at creation (`acme-co`, `acme-co-2`, ...), are unique across all rows including soft-deleted ones, and can never be changed. Groups are soft-deleted only (`forceDelete` is denied by policy), and a user who owns groups cannot be hard-deleted (`ON DELETE RESTRICT`), so issued URLs are never freed.
- `links` — belong to one group, ordered by `sort_order` then `id`. Soft-deleting a group keeps its links so a restore brings them back; they are removed only if the group is hard-deleted (`ON DELETE CASCADE`).
- Authorization: `LinkGroupPolicy` (owner only) and `LinkPolicy` (owner of the link's group; links in soft-deleted groups are denied).

Sample data for an existing user (default `test@example.com`, override with `SEED_USER_EMAIL`):

```bash
php artisan db:seed --class=LinkGroupSeeder
```

## Project layout

- `routes/web.php`, `routes/auth.php` — application and auth routes
- `app/Http/Controllers/Auth/` — login/logout and password reset controllers
- `app/Http/Requests/Auth/` — Form Requests for auth validation
- `resources/views/components/layouts/app.blade.php` — authenticated dashboard layout (`<x-layouts.app>`)
- `resources/views/components/layouts/guest.blade.php` — login / password reset layout (`<x-layouts.guest>`)
- `resources/views/components/layouts/public.blade.php` — layout for future public link pages (`<x-layouts.public>`)
