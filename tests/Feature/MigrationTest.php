<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Saucebase\Core\Tests\TestCase;

/**
 * Core's migrations run against a users table the application owns.
 *
 * That makes their filenames load-bearing. `loadMigrationsFrom()` merges every
 * registered path and orders the result by migration name, not by which package
 * registered first — so `add_locale_to_users_table` only works because it sorts
 * after the application's `create_users_table`. Renumber either side and the
 * migration fails on a table that does not exist yet.
 */
class MigrationTest extends TestCase
{
    public function test_locale_column_is_added_to_the_applications_users_table(): void
    {
        // The assertion that pins ordering: this column can only exist if core's
        // migration ran *after* the users table was created.
        $this->assertTrue(Schema::hasColumn('users', 'locale'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function shippedTables(): array
    {
        return [
            'roles' => ['roles'],
            'permissions' => ['permissions'],
            'settings' => ['settings'],
            'media' => ['media'],
        ];
    }

    #[DataProvider('shippedTables')]
    public function test_core_migrations_create_their_tables(string $table): void
    {
        $this->assertTrue(
            Schema::hasTable($table),
            "Core ships a migration for [{$table}], but the table was not created.",
        );
    }
}
