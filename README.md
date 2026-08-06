# Pusataka Ops

Pusataka Ops is a Laravel-based library management application for members and
librarians. It provides a database-backed catalog, borrowing workflows, saved
books, notifications, account settings, and librarian catalog management.

## Features

- Member registration, login, logout, profile updates, and password changes.
- Password recovery with generic responses, CSRF protection, and throttling.
- Login throttling keyed by normalized email and IP address.
- Catalog search by title, author, or publisher.
- Category, rack, availability, and sorting filters.
- Book detail pages with borrow and save-book actions.
- Borrowing lifecycle: request, confirm, extend, return, and history.
- Transaction-safe borrowing, returning, extension, and stock updates.
- Notifications and unread notification tracking.
- Account preferences for theme and notification settings.
- Avatar upload with image validation, preview, safe replacement, and public URLs.
- Librarian-only book creation, editing, stock updates, and deletion.
- Responsive Blade interface with mobile navigation.
- Session-expiry handling that redirects API-authenticated pages back to login.
- Empty states for an empty catalog and empty member activity.
- Sanctum-protected API endpoints for authentication, catalog, dashboard,
  profile, preferences, borrowing, notifications, and librarian operations.
- Role-based access for members (`anggota`) and librarians (`pustakawan`).

## Technology

- PHP 8.3+
- Laravel 13.8
- Laravel Sanctum
- Blade templates
- MySQL or SQLite
- Vite, Tailwind CSS, and JavaScript

## Local setup

### Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL or SQLite

### Installation

```bash
git clone https://github.com/Scer-VYN/Pusataka-Ops.git
cd Pusataka-Ops
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database values in `.env`, then run:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

The application is available at `http://127.0.0.1:8000`.

Set `APP_TIMEZONE` in `.env` when deploying to a different locale. It defaults
to `Asia/Jakarta`, which is also the timezone used by the due-date reminders
and the web interface.

For production, set `APP_URL` to the trusted HTTPS application origin. Password
recovery links are generated from this value rather than from request headers.
Keep `APP_DEBUG=false` outside local development.

For a local SQLite installation, create `database/database.sqlite` and set:

```dotenv
DB_CONNECTION=sqlite
```

## Seeded development accounts

`php artisan migrate --seed` creates the following development accounts:

| Role | Email | Password |
| --- | --- | --- |
| Librarian (`pustakawan`) | `admin@stack01.test` | `password` |
| Member (`anggota`) | `user@stack01.test` | `password` |

These credentials are for local development only. Use new credentials and
environment-specific account provisioning outside development.

The seeder intentionally creates accounts only; catalog, borrowing, saved-book,
and notification records are managed through the application and test fixtures.

## Development

Start the complete development environment with:

```bash
composer run dev
```

Or run the application and frontend separately:

```bash
php artisan serve
npm run dev
```

Run the test suite with:

```bash
php artisan test
```

Build production frontend assets with:

```bash
npm run build
```

## Main routes

### Web

- `/login`, `/register`, `/forgot-password`, and `/reset-password/{token}` -
  authentication and recovery
- `/` - member dashboard
- `/collection` - catalog and filters
- `/books/{book}` - book details and borrowing
- `/borrowings` - member borrowing history
- `/account` - account profile, security, and settings
- `/librarian` - librarian catalog management

### API

The API is available under `/api`. Public endpoints include books, available
books, categories, and racks. Authentication endpoints are:

- `POST /api/auth/login`, `GET /api/auth/me`, and `POST /api/auth/logout`
- `POST /api/auth/register`
- `POST /api/auth/forgot-password` and `POST /api/auth/reset-password`

Authenticated endpoints use Laravel Sanctum bearer tokens or stateful browser
sessions:

- `GET|PUT|PATCH /api/profile` reads and updates the current user's name/email.
- `POST /api/profile/avatar` uploads a validated PNG, JPEG, or WebP avatar.
- `PUT|PATCH /api/profile/password` changes the password and revokes other
  sessions and personal access tokens.
- `GET|PUT|PATCH /api/preferences` reads and saves theme and notification
  preferences.

Password recovery responses are generic and throttled to prevent account
enumeration and reset-email abuse. Login, CSRF, role-based authorization, and
session invalidation protections apply to their respective flows.

## Roles

- `anggota`: browse books, borrow and return books, extend loans, save books,
  view notifications, and manage their account.
- `pustakawan`: manage the catalog and monitor library activity.

## Testing

Run the complete regression suite with:

```bash
php artisan test
```

The current suite covers authentication, CSRF, throttling, session revocation,
profile/avatar flows, role authorization, borrowing concurrency safeguards, and
catalog behavior.
