<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes a scheduled run belong to an agent and a list.
 *
 * The automation table already carried a run time, a timezone, a calling window,
 * a per-run cap, retry limits and a minimum gap between calls -- everything a
 * recurring campaign needs. What it had no idea about was *which* agent should
 * place the calls and *which* list to work through, because it was written for a
 * single insurance renewal sweep.
 *
 * `only_unreached` is the follow-up switch: a run that calls back the people the
 * last run could not reach, rather than starting the whole list again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_id')->nullable()->after('workspace_id')->index();
            $table->unsignedBigInteger('import_batch_id')->nullable()->after('agent_id')->index();
            $table->boolean('only_unreached')->default(false)->after('skip_active_callback');
            $table->json('run_days')->nullable()->after('run_at');
        });
    }

    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropColumn(['agent_id', 'import_batch_id', 'only_unreached', 'run_days']);
        });
    }
};
