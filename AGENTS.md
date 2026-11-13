<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>


# Link Management Application — Development Specification and Agent Instructions

## 1. Purpose

Build a lightweight, maintainable, in-house application for authenticated users to create, customize, and manage public collections of links ("link groups"). Each group has a stable public URL that can be shared directly or written to an NFC card using separate, external software.

This file defines the target product, architectural constraints, implementation sequence, and operating rules for AI coding agents. It is intentionally independent of the project's current implementation status. Inspect the repository to determine what already exists before acting.

## 2. Product scope

### Required MVP capabilities

- Secure user authentication, logout, and password reset.
- Internal account provisioning; public self-registration disabled by default.
- Each user can own and manage multiple link groups.
- Each link group has a stable, unique, public URL.
- Users can create, edit, deactivate, and delete their groups.
- Users can create, edit, delete, activate/deactivate, and reorder links within their groups.
- Public pages show group branding, description, and active links in saved order.
- Public pages are fast, accessible, and mobile-first.
- Users can customize appearance using a constrained set of supported themes and settings.
- Dashboard displays the group's public URL with a copy action.
- Authorization prevents users from viewing or changing other users' private resources.
- Administrators can provision accounts through a minimal, authorized workflow before production use.

### Optional, only after core MVP

- Downloadable QR codes for public URLs.
- Basic privacy-conscious page/click analytics.
- Custom domains, scheduled links, shared/team ownership, complex page builders.

### Explicitly out of scope for MVP

- NFC card inventory, card assignments, card token URLs, or physical card programming.
- Separate frontend SPA, REST API, or mobile application.
- Billing, subscriptions, or public SaaS signup.

NFC cards are written with the link group's public URL using software outside this application. Editing the group's links must not require reprogramming a card.

## 3. Technology and architectural constraints

- Backend: Laravel, using the installed supported version and its conventions.
- PHP: use the version supported by the installed Laravel application.
- Views: server-rendered Blade.
- Frontend: Tailwind CSS and Alpine.js for lightweight interactions.
- Build tooling: Vite.
- Database: PostgreSQL preferred; MySQL is acceptable if already configured.
- Tests: use the project's existing Pest or PHPUnit setup.
- Drag-and-drop ordering: a small library such as SortableJS is acceptable when needed.

Do **not** introduce Livewire, Inertia, React, Vue, a separate API, or a general-purpose frontend state framework. Do not add Docker, queues, Redis, repositories, service layers, or third-party packages without a demonstrated requirement. Prefer first-party Laravel features and established conventions.

Use controllers, Eloquent models and relationships, Form Request validation, authorization policies, route model binding, Blade components, and database migrations where appropriate. Keep business logic in appropriate domain-level code rather than overloading templates or controllers; avoid speculative abstractions.

## 4. Data model

### Users

Use Laravel's standard users table and authentication mechanisms. A user has many link groups.

### Link groups (`link_groups`)

Recommended fields:

- `id` — primary key.
- `user_id` — owner; foreign key to users.
- `name` — required display name.
- `slug` — required globally unique public identifier.
- `description` — optional public description.
- `avatar_path` — optional uploaded image path.
- `theme` — supported predefined theme identifier.
- `appearance` — JSON object of validated appearance settings.
- `is_active` — whether public page is available.
- timestamps and `deleted_at` for soft deletion.

A group belongs to one user and has many links. Enforce uniqueness at the database level. Public URLs use `/p/{slug}`.

**Permanent URL contract:** A group's public URL must remain stable after issuance. Do not allow slug edits in the MVP. Preserve reserved slugs when groups are soft-deleted; never silently reuse a URL that may already be encoded on a physical NFC card. Do not change the public route format without explicit approval. Consider slug collisions and normalization at creation time.

### Links (`links`)

Recommended fields:

- `id` — primary key.
- `link_group_id` — foreign key to owning group.
- `title` — required display label.
- `url` — required validated destination URL.
- `description` — optional supporting text.
- `sort_order` — integer ordering within group.
- `is_active` — whether shown publicly.
- timestamps.

A link belongs to one group. Sort links by `sort_order` with a deterministic secondary order. Define appropriate cascade/deletion behavior deliberately.

### Future card management

If card inventory or reassignment becomes necessary, add an independent `cards` resource later, with immutable opaque tokens and an optional group association. Do not create that table, redirects, or dashboard during MVP development.

## 5. Routes and interfaces

### Authenticated dashboard

Use conventional resourceful Laravel routes where possible, including:

- `/dashboard` — overview of the user's link groups.
- `/groups/create` and `/groups/{group}/edit` — group creation and editing.
- `/groups/{group}/links` — link management and ordering.
- `/groups/{group}/appearance` — appearance editor.

Every authenticated group or link operation must be authorized server-side. Nested link operations must confirm that the link belongs to the specified group, not merely that the user owns some group.

### Public pages

- `GET /p/{slug}` — public link group landing page.

Public pages require no login. Display only active groups and active links. Define consistent behavior for missing, inactive, and soft-deleted groups (404 is an acceptable MVP default). Avoid leaking private dashboard data in public output.

## 6. UX and visual requirements

- Mobile-first public pages, optimized for a phone opened from an NFC tap.
- Clear branding, readable typography, prominent touch-friendly link buttons.
- Accessible color contrast, keyboard focus states, semantic HTML, and descriptive labels.
- Responsive dashboard with understandable empty states, validation errors, and confirmation for destructive actions.
- Dashboard users can easily copy a canonical public URL.
- Appearance customization should begin with a small set of validated options: predefined theme, background/text/button colors, button shape, and optional image. Avoid arbitrary custom CSS or unrestricted HTML.
- Use Alpine.js for local preview behavior; reuse shared Blade styling/components so previews and public pages stay aligned.
- Favor simple, predictable workflows over elaborate UI effects.

## 7. Security and data integrity

- Enforce authorization with policies or equivalent server-side checks on every private resource operation; never rely on hiding buttons.
- Use Laravel CSRF protections for state-changing requests.
- Validate and normalize input with Form Requests where appropriate.
- Allow only safe URL schemes for destination links, normally `http` and `https`; reject JavaScript and other dangerous schemes.
- Escape user-generated text in Blade; do not render raw HTML supplied by users.
- Validate image MIME type, size, and storage location; use Laravel's storage facilities.
- Protect authentication and sensitive endpoints with appropriate throttling.
- Never commit `.env`, credentials, secrets, or private user data.
- Avoid logging passwords, reset tokens, or unnecessary visitor-identifying data.
- Use database constraints and transactions where they protect consistency, including link ordering updates.
- Keep publicly exposed identifiers and behavior deliberate; do not confuse a group's slug with an authentication or authorization token.

## 8. Development roadmap

Follow these milestones in order unless dependencies or an explicit user instruction justify a change. Each milestone must leave the application functional and testable. Do not automatically begin the next milestone without the user's direction.

### Milestone 1 — Foundation

- Initialize and configure Laravel, database, authentication, Blade layouts, Tailwind, Alpine.js, and Vite.
- Disable public registration by default.
- Provide a protected dashboard and baseline authentication tests.

**Done when:** An authorized user can log in, access the dashboard, and log out; unauthenticated visitors cannot access it.

### Milestone 2 — Data model and authorization

- Add link group and link migrations, models, relationships, factories, and seeders.
- Establish unique immutable slugs, soft deletion, and ownership policies.
- Add tests for relationships, constraints, and cross-user access denial.

**Done when:** Data can be created reliably and unauthorized access is blocked.

### Milestone 3 — Link group management

- Build group listing, creation, editing, activation/deactivation, and soft deletion.
- Show each group's stable public URL with a copy action.
- Add validation, authorization, and feature tests.

**Done when:** Users can manage multiple groups without changing previously issued URLs.

### Milestone 4 — Link management

- Build create/edit/delete and enable/disable controls for links.
- Implement deterministic ordering and drag-and-drop with persisted order.
- Test nested ownership, validation, ordering, and inactive links.

**Done when:** Users can fully manage links within their own groups.

### Milestone 5 — Public pages

- Render `/p/{slug}` from the database with group information and active links in order.
- Add responsive, accessible public-page layout.
- Handle unavailable groups consistently.

**Done when:** Public URLs work without login and reflect changes to links.

### Milestone 6 — Appearance customization

- Add predefined themes and validated appearance settings.
- Build an appearance editor with lightweight Alpine.js preview.
- Ensure preview and published page use consistent styling.
- Test defaults, validation, and mobile rendering.

**Done when:** Users can customize and reliably publish their group's appearance.

### Milestone 7 — Public URL finishing

- Make copying the canonical public URL easy and reliable.
- Verify URL stability across group edits and deletion behavior.
- Optionally add QR export if requested.
- Test a URL written to a physical NFC card using external software.

**Done when:** A programmed card opens the correct page, and link edits require no card rewrite.

### Milestone 8 — Security, quality, and account provisioning

- Review authorization, URL validation, uploads, throttling, error handling, accessibility, and responsive behavior.
- Implement a minimal administrator-only account provisioning workflow suitable for internal use.
- Add regression tests for key workflows and ownership boundaries.
- Confirm password-reset delivery configuration is suitable for the intended environment.

**Done when:** The product is safe and operable by its intended users without developer-only account creation.

### Milestone 9 — Deployment and launch

- Configure production environment, HTTPS, database migrations, asset build, persistent storage, backups, and monitoring.
- Document deployment, rollback, and account administration.
- Run production smoke tests including the public page and a physical NFC tap.

**Done when:** The application works end to end in production and has a basic recovery plan.

## 9. Agent working agreement

For every requested change:

1. Read this file and inspect the relevant existing code, tests, routes, migrations, and dependencies. Never assume a milestone is unfinished merely because it appears in this roadmap.
2. Briefly state the proposed scope and important design decisions before implementing substantial changes. Ask for approval when a decision changes product behavior, schema contracts, dependencies, or scope.
3. Implement the smallest coherent increment. Preserve existing conventions and avoid unrelated refactors.
4. Add or update focused automated tests for new behavior, especially authorization, validation, and public URL stability.
5. Run relevant tests and build/lint commands when available. Report actual outcomes; never claim tests passed without running them.
6. Summarize changed files, behavioral changes, verification performed, and remaining risks or manual steps.
7. Stop at the requested milestone. Do not deploy, alter production data, install system services, change infrastructure, or commit/push code unless specifically authorized.

If an instruction here conflicts with the existing codebase or a new user request, identify the conflict and seek clarification rather than silently overriding it. Favor clarity, maintainability, and reliable behavior over adding features.

## 10. MVP acceptance workflow

The MVP is complete when:

1. An administrator can provision a user account.
2. The user logs in and creates a link group with a stable public URL.
3. The user adds, edits, enables/disables, and reorders links.
4. The user selects a theme and customizes the public page.
5. The user copies the group's public URL.
6. The company writes that URL to an NFC card using separate software.
7. A visitor taps the card and sees the correct mobile-friendly public page.
8. The user changes links, and the same card URL immediately displays the changes.
9. Another user cannot access or modify the group's private management resources.
10. Automated tests pass and production deployment, backup, and recovery procedures are documented.

**Design principle:** This is a link management application, not an NFC card management platform. Build the smallest maintainable solution that fulfills that purpose.
