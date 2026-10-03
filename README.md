# Invoice System

This repository contains two Laravel 10 applications:

- The root application contains the invoice and user-facing features.
- `backend/` contains the administration application.

Both applications require PHP 8.1 or newer and Composer. Configure each application independently by copying its `.env.example` to `.env`, setting its database credentials, installing dependencies from that application's directory, and generating an application key:

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Run the admin application's commands from `backend/` and the user-facing application's commands from the repository root. Install front-end dependencies with the included `package.json` in the application being built.

The provided migrations do not yet define all invoice, customer, and related feature tables. Use a database with the application's complete schema; running the available migrations alone does not create a fully usable invoice database.

The root `HomeService` also references `Doctor` and `Appointment` modules that are not present in the supplied project folder. Endpoints depending on those modules require their missing source before they can run.

Uploaded files, environment files, logs, and generated cache files are excluded from version control. Configure production with `APP_DEBUG=false` and deployment-specific secrets.
