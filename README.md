# Enterprise Case Management System

A modular case management platform built with **Laravel 12**, **Bootstrap 5**, and **Blade**. Supports **MySQL** and **PostgreSQL**.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ **or** PostgreSQL 14+
- Node.js 18+ (optional, for Vite asset compilation)

## Installation

```bash
git clone https://github.com/Thindwa/cms.git
cd cms
composer install
cp .env.example .env
php artisan key:generate
```

### Database setup

Edit `.env` and set your database driver and credentials.

**MySQL:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms
DB_USERNAME=root
DB_PASSWORD=
```

**PostgreSQL:**

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cms
DB_USERNAME=postgres
DB_PASSWORD=
```

Then run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

### Start the server

```bash
php artisan serve
```

## Default users (from seeder)

| Role         | Username     | Password   |
|--------------|-------------|------------|
| Super Admin  | superadmin  | password   |
| Administrator| admin       | password   |
| Officer      | officer     | password   |
| Viewer       | viewer      | password   |

## Architecture

The system is a **base platform** with a pluggable module architecture. Core services (Auth, RBAC, Audit, Dashboard, Settings, Admin) are shared; domain features are self-contained modules.

### Active modules

- **Case Management** — case CRUD, documents (with versioning), notes, audit history, reports (PDF / CSV export)

### Future module placeholders

These appear in the sidebar as "Coming soon" and can be implemented without any changes to Core:

- Complaints
- HR
- Asset Management
- Licensing
- Finance
- Workflow Automation

See `docs/ADDING_A_MODULE.md` for how to create a new module.

## Tech stack

| Layer       | Technology                        |
|-------------|-----------------------------------|
| Framework   | Laravel 12                        |
| Database    | MySQL 8+ / PostgreSQL 14+         |
| Auth & RBAC | Spatie Laravel Permission          |
| UI          | Bootstrap 5, Blade, Chart.js      |
| PDF export  | barryvdh/laravel-dompdf            |

## License

Proprietary.
