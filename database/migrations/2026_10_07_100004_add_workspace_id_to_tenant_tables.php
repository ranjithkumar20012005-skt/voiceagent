<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the tenant key to every table that holds business data. Nullable at
 * this point: the next migration backfills existing rows before anything
 * starts relying on it. No existing column is touched or dropped.
 */
return new class extends Migration
{
    /** Tables that gain a workspace_id, and the column it goes after. */
    private const TABLES = [
        'customers'     => 'id',
        'agents'        => 'id',
        'campaigns'     => 'id',
        'import_batches' => 'id',
        'call_attempts' => 'id',
        'automations'   => 'id',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $after) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'workspace_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($after) {
                $t->unsignedBigInteger('workspace_id')->nullable()->after($after)->index();
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'workspace_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('workspace_id'));
            }
        }
    }
};
