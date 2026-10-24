# DBZ Salon Portal

A booking site for a beauty salon, built with PHP, MySQL and jQuery. Customers pick a service, date and time; staff manage appointments from an admin page; a nightly job exports booking metrics for reporting.

## Background

This project began from a tutorial-style salon template that I customized while learning PHP and MySQL. Revisiting it in 2026, I rebuilt the backend to fix the problems I can now see in the original:

| Original | Now |
| --- | --- |
| Form input pasted directly into SQL (SQL injection) | PDO prepared statements |
| Database values printed into HTML unescaped (XSS) | Every value escaped with `e()` |
| Connected as MySQL `root` with no password, hard-coded | Credentials from environment / `.env` |
| `error_reporting(0)` hid every error | Errors logged; shown only with `APP_DEBUG=1` |
| Booking handler copy-pasted into two pages | One validated `BookingService` |
| Random appointment numbers that could collide | `random_int` + unique key + retry |
| No schema (`beauty.sql` was empty) | `db/schema.sql` with keys, constraints, indexes |
| Admin link pointed to a missing folder | Password-protected admin page |
| No tests | PHPUnit suite + GitHub Actions CI |

## Run it

**Quickest (SQLite, no database server):**

```bash
cp .env.example .env
php bin/init-db.php
php -S localhost:8000 -t public
```

**With MariaDB in Docker:**

```bash
docker compose up --build    # http://localhost:8080
```

**Admin page:** generate a password hash and put it in `.env` (or export it for Docker):

```bash
php -r 'echo password_hash("your-password", PASSWORD_DEFAULT), PHP_EOL;'
```

## Reporting pipeline

```bash
php bin/export-daily.php --date=2026-12-05
python3 scripts/summarize_exports.py exports --days 7
```

`export-daily.php` writes one folder per day (`exports/date=YYYY-MM-DD/`) with booking counts and booked value per service and per status. It is built the way a data platform batch job should be:

- **Idempotent:** rerunning a day replaces that day's folder instead of duplicating rows.
- **Atomic:** files are written to a temp folder and renamed into place, with a `_SUCCESS` marker last.
- **Privacy-aware:** only aggregates are exported, never names, emails or phone numbers.

`summarize_exports.py` reads only complete partitions, validates each row, and exits non-zero if it finds bad data.

## Tests

```bash
composer install
composer test
```

Tests run against an in-memory SQLite copy of the schema and cover validation, injection safety, unique appointment numbers and the reporting queries. CI also runs the export job end to end.

## Layout

```
public/       web root: pages, admin/, css/, js/
templates/    shared header, footer, form partials
src/          PHP classes (booking, reports, database, CSRF)
db/           schema (MySQL and SQLite) and sample services
bin/          CLI: database setup, daily export
scripts/      Python reporting
tests/        PHPUnit
```

## Known gaps

- The hero and gallery images referenced by the theme (`images/bg_1.png` and others) were never committed, so those sections show without backgrounds.
- The contact form has no backend yet: its fields have no names and submissions go nowhere. Good next feature: store messages in a `messages` table and list them in admin.
