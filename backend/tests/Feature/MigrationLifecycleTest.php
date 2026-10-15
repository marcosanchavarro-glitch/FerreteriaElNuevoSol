<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationLifecycleTest extends TestCase
{
    public function test_all_migrations_can_be_rolled_back_and_applied_again(): void
    {
        config(['database.connections.migration_audit' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        try {
            $this->artisan('migrate', ['--database' => 'migration_audit', '--force' => true])->assertSuccessful();
            $this->assertTrue(Schema::connection('migration_audit')->hasTable('order_items'));
            $this->artisan('migrate:rollback', ['--database' => 'migration_audit', '--force' => true])->assertSuccessful();
            foreach (['users', 'products', 'orders', 'notifications', 'activity_logs'] as $table) {
                $this->assertFalse(Schema::connection('migration_audit')->hasTable($table));
            }
            $this->artisan('migrate', ['--database' => 'migration_audit', '--force' => true])->assertSuccessful();
            $this->assertTrue(Schema::connection('migration_audit')->hasTable('activity_logs'));
        } finally {
            DB::purge('migration_audit');
        }
    }
}
