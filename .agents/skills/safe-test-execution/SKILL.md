---
name: safe-test-execution
description: "Prevents automated tests from wiping or mutating local development databases. Covers test database isolation, config cache gotchas with RefreshDatabase, .env.testing enforcement, PHPUnit 10+ server tag requirements, and pre-test safety checks in Laravel."
license: MIT
metadata:
  author: PRPIMS-SDN
---

# Safe Test Execution & Test Database Isolation

This skill enforces strict database isolation for automated testing in Laravel. It prevents tests that use `RefreshDatabase`, `DatabaseMigrations`, or database seeders from accidentally connecting to and wiping the primary local development database (e.g. MySQL `prpims_sdn_db`).

---

## 1. Golden Rules of Test Database Isolation

1. **NEVER test against the development database:** All unit and feature tests must execute exclusively against an in-memory SQLite database (`:memory:`) or an explicitly isolated testing database.
2. **Clear cached configurations before testing:** If `config.php` exists in `bootstrap/cache/`, Laravel ignores `.env.testing` and `phpunit.xml`, locking tests to the development MySQL connection.
3. **Use `<server>` tags in PHPUnit 10+:** In PHPUnit 10, 11, and 12, `<env force="true">` is deprecated or ignored; always use `<server>` tags or a dedicated `.env.testing` file.
4. **Programmatic Fail-Safe in `TestCase`:** Always maintain a programmatic guard in `tests/TestCase.php` that prevents test execution if the database connection resolves to a live/dev database.

---

## 2. The Config Cache Trap (Root Cause & Prevention)

### The Problem
When `php artisan optimize` or `php artisan config:cache` is executed during development, Laravel bundles all config files into `bootstrap/cache/config.php`. In that file, `DB_CONNECTION` is hardcoded to `mysql` and `DB_DATABASE` is hardcoded to `prpims_sdn_db`.

When tests are subsequently run:
- Laravel loads `bootstrap/cache/config.php` directly.
- It bypasses `.env.testing`.
- It bypasses `<env>` and `<server>` tags in `phpunit.xml`.
- Tests using `RefreshDatabase` will run migrations and wipe the local MySQL database.

### The Prevention
Before running test suites, always ensure configuration caches are cleared:
```bash
php artisan optimize:clear
```
Or check if `bootstrap/cache/config.php` exists. If present, delete it or clear it prior to test runs.

---

## 3. Required Environment & Configuration Setup

### A. Dedicated `.env.testing` File
The repository must maintain a committed or documented `.env.testing` file at the root:
```env
APP_ENV=testing
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=4
CACHE_STORE=array
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
MAIL_MAILER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
PULSE_ENABLED=false
TELESCOPE_ENABLED=false
NIGHTWATCH_ENABLED=false
```

### B. `phpunit.xml` Configuration
In `phpunit.xml`, use `<server>` variables inside `<php>` to guarantee precedence:
```xml
<php>
    <server name="APP_ENV" value="testing"/>
    <server name="DB_CONNECTION" value="sqlite"/>
    <server name="DB_DATABASE" value=":memory:"/>
    <server name="SESSION_DRIVER" value="array"/>
    <server name="CACHE_STORE" value="array"/>
    <server name="QUEUE_CONNECTION" value="sync"/>
    <server name="MAIL_MAILER" value="array"/>
</php>
```

### C. Fail-Safe Guard in `tests/TestCase.php`
Every test case inheriting from `Tests\TestCase` must have this safety guard in `setUp()`:
```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Safety guard: ensure tests NEVER run against local MySQL database
        if (config('database.default') !== 'sqlite') {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
            ]);
            DB::purge();
        }
    }
}
```

---

## 4. Pre-Flight Checklist Before Running Tests

Whenever an agent or developer prepares to run `php artisan test` or `vendor/bin/phpunit`:

- [ ] **Check 1:** Confirm `bootstrap/cache/config.php` does not exist (`php artisan optimize:clear`).
- [ ] **Check 2:** Confirm `.env.testing` is present and specifies `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.
- [ ] **Check 3:** Confirm `tests/TestCase.php` contains the SQLite memory connection safety guard.
- [ ] **Check 4:** If CSRF errors (status `419`) occur during HTTP feature testing, ensure session and token verification are properly scoped to the testing array driver.

---

## 5. Recovery Procedure (If Accidental Wipe Occurs)

If a developer or agent accidentally executes tests against the local database:
1. Re-run migrations and default seeders:
   ```bash
   php artisan migrate --seed
   ```
2. Verify all personnel accounts and default credentials:
   ```bash
   php artisan tinker --execute 'echo "Users: " . App\Models\User::count();'
   ```
3. Clear all cached configurations:
   ```bash
   php artisan optimize:clear
   ```
