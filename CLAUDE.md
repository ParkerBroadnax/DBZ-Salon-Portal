# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install
cp .env.example .env              # defaults to SQLite at var/salon.sqlite
php bin/init-db.php               # create schema + seed services (safe to rerun)
php -S localhost:8000 -t public   # dev server; web root is public/
docker compose up --build         # Apache + MariaDB at http://localhost:8080

composer test                                              # PHPUnit, all tests
vendor/bin/phpunit tests/BookingServiceTest.php            # one file
vendor/bin/phpunit --filter testRejectsImpossibleDate      # one test
composer lint                     # php -l over public, src, bin, templates

php bin/export-daily.php --date=YYYY-MM-DD [--out=exports]
python3 scripts/summarize_exports.py exports --days 7
```

CI (`.github/workflows/ci.yml`) runs lint + tests on PHP 8.1 and 8.3, and runs the export job twice for the same date to check it is idempotent, then runs the Python summarizer. Code must stay compatible with PHP 8.1.

The admin page (`public/admin/`) is disabled until `ADMIN_PASSWORD_HASH` is set (generate with `php -r 'echo password_hash("pw", PASSWORD_DEFAULT), PHP_EOL;'`, single-quote it in `.env` because it contains `$`).

## Architecture

Plain PHP pages, no framework or router. Each page in `public/` does `require __DIR__ . '/../src/bootstrap.php'` and then includes partials from `templates/`.

- **`src/bootstrap.php`** is the shared request setup: autoload (falls back to a manual `Salon\` autoloader if `vendor/` is missing), loads `.env`, configures error display from `APP_DEBUG`, starts the session, and creates the globals `$db`, `$serviceRepo` and `$services` that pages and templates rely on. It also defines the form handlers `handle_booking()` (used by `index.php` and `services.php`), `handle_contact()` (used by `contact.php`) and `take_flash()`. Both forms use Post/Redirect/Get with flash messages and previous input in `$_SESSION`.
- **`src/`** (`Salon\` namespace): `BookingService` (validate + insert, retrying on appointment-number collisions via SQLSTATE 23000), `MessageService` (contact form messages: validate, insert, list, mark read), `ServiceRepository`, `ReportRepository` (admin dashboard and export queries), `Database`, `Env`, `Csrf`. `src/helpers.php` defines the global `e()` HTML-escape function, which is autoloaded via composer `files`.
- **`bin/`** CLI scripts also go through `bootstrap.php` (except `init-db.php`, which loads only `Env` and `Database`).

### Conventions that span files

- **Two databases, two schemas.** MySQL/MariaDB uses `db/schema.sql` (Docker loads it automatically); SQLite uses `db/schema.sqlite.sql` (local runs and tests). `db/seed.sql` is shared. Any schema change must be made in both files. SQL in `src/` must be portable across MySQL and SQLite.
- **Appointment statuses** (`pending`, `confirmed`, `completed`, `cancelled`) are defined in the CHECK constraint of both schemas and again in `ReportRepository::setStatus()`. Keep all three in sync.
- **Config** is read only through `Env::get()`. Real environment variables override `.env`. Relative SQLite paths in `DB_DSN` are resolved against the project root (`Database::resolveSqlitePath`), not the web server's working directory.
- **Security baseline** (the reason for the `modernize` rewrite, see README): every query uses PDO prepared statements (emulated prepares off), every value printed in HTML goes through `e()`, and every POST form includes `Csrf::field()` and is checked with `Csrf::valid()`.
- **Tests** use `Salon\Tests\TestDb::create()`, which builds an in-memory SQLite database from `schema.sqlite.sql` + `seed.sql`. `BookingService` takes an optional `$today` so date validation can be tested deterministically.
- **Export job** (`bin/export-daily.php`) must stay idempotent (rerunning a date replaces `exports/date=YYYY-MM-DD/`), atomic (write to a temp dir, rename into place, `_SUCCESS` written last) and aggregate-only (no names, emails or phones). `scripts/summarize_exports.py` uses only the standard library, reads only partitions with `_SUCCESS`, and exits non-zero on bad rows.

## Known gaps (from README)

- Theme images such as `images/bg_1.png` were never committed.
