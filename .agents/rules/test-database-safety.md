---
trigger: always_on
---

# Test Database Safety

Automated tests must NEVER run against the primary development database (e.g. MySQL `prpims_sdn_db`).

## Requirements

1. **Strict SQLite In-Memory Isolation:** All automated tests (`php artisan test`, `vendor/bin/phpunit`) must execute strictly against `:memory:` SQLite.
2. **Never Test with Cached Configs:** Never run tests while `bootstrap/cache/config.php` exists. If configuration was cached via `php artisan optimize` or `php artisan config:cache`, execute `php artisan optimize:clear` before running tests.
3. **Keep `.env.testing` Intact:** Ensure `.env.testing` specifies `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.
4. **Preserve Guard in `TestCase.php`:** Do not remove the database connection safety guard in `tests/TestCase.php`.
5. **Immediate Recovery:** If the development database is ever inadvertently refreshed, immediately re-run `php artisan migrate --seed` to restore all default personnel accounts and sample records.
