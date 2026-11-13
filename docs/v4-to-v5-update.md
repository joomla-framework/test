## Updating from v4 to v5

The following changes were made to the Test package between v4 and v5.

### Test databases that do not look like test databases are no longer dropped

`DatabaseManager::dropDatabase()` and `DatabaseManager::clearTables()`, and therefore `DatabaseTestCase`, refuse to
drop or empty a database that is not recognisably a test database and throw
`Joomla\Test\Exception\DestructiveOperationNotAllowed`. In 4.x they triggered an `E_USER_DEPRECATED` notice and went
ahead. A test database is an in-memory SQLite database or a database whose name contains "test" (case-insensitive).
If your test database is named differently (joomla/database, for example, uses `joomla_ut`), either rename it, set
the environment variable `JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE=yes` (e.g. `<env name="JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE"
value="yes"/>` in `phpunit.xml`), or override `DatabaseManager::isTestDatabase()`.
