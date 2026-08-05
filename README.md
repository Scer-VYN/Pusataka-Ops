# Pusataka Ops

Pusataka Ops is a Laravel-based library management application for members and
librarians. It provides a database-backed catalog, borrowing workflows, saved
books, notifications, account settings, and librarian catalog management.

## Features

- Member registration, login, logout, profile updates, and password changes.
- Catalog search by title, author, or publisher.
- Category, rack, availability, and sorting filters.
- Book detail pages with borrow and save-book actions.
- Borrowing lifecycle: request, confirm, extend, return, and history.
- Notifications and unread notification tracking.
- Librarian-only book creation, editing, stock updates, and deletion.
- Responsive Blade interface with mobile navigation.
- Empty states for an empty catalog and empty member activity.
- Sanctum-protected API endpoints for authentication, catalog, dashboard,
  borrowing, notifications, and librarian operations.

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
npm install
npm run build
php artisan serve
```

The application is available at `http://127.0.0.1:8000`.

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

- `/login` and `/register` - authentication
- `/` - member dashboard
- `/collection` - catalog and filters
- `/books/{book}` - book details and borrowing
- `/borrowings` - member borrowing history
- `/librarian` - librarian catalog management

### API

The API is available under `/api`. Public endpoints include authentication,
books, available books, categories, and racks. Authenticated endpoints use
Laravel Sanctum bearer tokens, with role-based access for member and librarian
operations.

## Roles

- `anggota`: browse books, borrow and return books, extend loans, save books,
  view notifications, and manage their account.
- `pustakawan`: manage the catalog and monitor library activity.
