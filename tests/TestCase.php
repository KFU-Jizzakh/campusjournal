<?php

namespace Tests;

use App\Support\DashboardInbox;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests universally assume the role/permission tables
        // exist (RoleSeeder is idempotent) — individual test files no
        // longer seed it in their own beforeEach hooks. Hooks declared
        // in Pest.php are NOT a viable home for this: ->in() scoping
        // silently never matched, so the hook never ran.
        $this->seed(RoleSeeder::class);

        // DashboardInbox memoizes its collections in static per-request
        // state; flush it so inbox results never leak between tests in
        // one process.
        DashboardInbox::flush();
    }
}
