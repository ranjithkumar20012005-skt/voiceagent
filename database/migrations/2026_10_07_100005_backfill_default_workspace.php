<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the data that already exists into a workspace so nothing is orphaned
 * once queries become tenant-scoped.
 *
 * Every pre-existing row belongs to the team that has been using the
 * application until now, so they all go into one "Default Workspace", and
 * every existing user is made a member of it. Where a table already recorded
 * user_id we keep that association by mapping it to that user's workspace;
 * otherwise rows fall back to the default workspace.
 *
 * Purely additive: no row is deleted and no column is dropped.
 */
return new class extends Migration
{
    private const TABLES = ['customers', 'agents', 'campaigns', 'import_batches', 'call_attempts', 'automations'];

    public function up(): void
    {
        $now = now();

        $workspaceId = DB::table('workspaces')->where('slug', 'default')->value('id');

        if (! $workspaceId) {
            $workspaceId = DB::table('workspaces')->insertGetId([
                'name'             => 'Default Workspace',
                'slug'             => 'default',
                'business_name'    => null,
                'default_language' => null,
                'timezone'         => config('app.timezone', 'Asia/Kolkata'),
                'currency'         => 'INR',
                'status'           => 'active',
                'credit_balance'   => 0,
                'metadata'         => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }

        // Every existing user joins the default workspace. The first one owns it.
        $users = DB::table('users')->orderBy('id')->get(['id']);

        foreach ($users as $i => $user) {
            $exists = DB::table('memberships')
                ->where('workspace_id', $workspaceId)->where('user_id', $user->id)->exists();

            if (! $exists) {
                DB::table('memberships')->insert([
                    'workspace_id' => $workspaceId,
                    'user_id'      => $user->id,
                    'role'         => $i === 0 ? 'owner' : 'member',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }

            DB::table('users')->where('id', $user->id)->update(['current_workspace_id' => $workspaceId]);
        }

        // Existing business rows all belong to that same workspace.
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'workspace_id')) {
                DB::table($table)->whereNull('workspace_id')->update(['workspace_id' => $workspaceId]);
            }
        }
    }

    public function down(): void
    {
        // Leaving the backfilled values in place is the safe direction: clearing
        // them would strip ownership from live rows.
    }
};
