<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // use CreatesApplication;

    // NOTE: this used to force `config(['database.default' => 'mysql'])` +
    // DB::reconnect('mysql') here. That is incompatible with phpunit.xml's
    // testing environment, which sets DB_CONNECTION=sqlite / DB_DATABASE=:memory:
    // — DB_DATABASE is shared by every connection's config() lookup, so the
    // 'mysql' connection ended up trying to open a database literally named
    // ":memory:" ("SQLSTATE[HY000] [1049] Unknown database ':memory:'"), and
    // once that connection was cached, later tests intermittently hit a stale
    // SQLite connection with a mismatched transaction-nesting state ("cannot
    // start a transaction within a transaction"). Removing the override lets
    // every Feature test run against phpunit.xml's intended SQLite in-memory
    // database via RefreshDatabase, as the rest of the test suite (and
    // CLAUDE.md's documented `php artisan test` workflow) already assumes.
}
