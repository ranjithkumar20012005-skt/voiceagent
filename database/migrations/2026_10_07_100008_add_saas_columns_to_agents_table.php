<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the agent row into a product object the customer owns.
 *
 * The provider identifiers move to generic `provider_*` names so a future
 * engine change does not mean a schema change. The original
 * platform_app_id / platform_app_version columns are copied across and then
 * LEFT IN PLACE -- dropping columns that hold live data is not worth the risk,
 * and nothing reads them once the model stops exposing them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('agent_ref', 32)->nullable()->after('workspace_id');
            $table->unsignedBigInteger('template_id')->nullable()->after('agent_ref')->index();

            $table->string('role')->nullable()->after('name');
            $table->string('calling_mode', 32)->default('instant_leads')->after('description');

            $table->string('voice', 64)->nullable()->after('default_language');
            $table->text('goal')->nullable()->after('instructions');
            $table->text('closing_message')->nullable()->after('goal');
            $table->text('objection_handling')->nullable()->after('closing_message');
            $table->json('faqs')->nullable()->after('objection_handling');

            $table->json('business_variables')->nullable();
            $table->json('custom_variables')->nullable();

            $table->string('provider', 32)->default('sarvam');
            $table->string('provider_agent_id', 191)->nullable()->index();
            $table->unsignedInteger('provider_agent_version')->nullable();
            $table->string('provider_deployment_id', 191)->nullable()->index();
            $table->json('provider_metadata')->nullable();

            $table->unsignedBigInteger('assigned_phone_number_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('last_synced_at')->nullable();
        });

        // Carry the existing platform identifiers over to the generic columns.
        DB::statement('UPDATE agents SET provider_agent_id = platform_app_id WHERE platform_app_id IS NOT NULL');
        DB::statement('UPDATE agents SET provider_agent_version = platform_app_version WHERE platform_app_version IS NOT NULL');

        // Existing agents were created and working, so they stay usable.
        DB::table('agents')->whereNull('agent_ref')->orderBy('id')->get(['id'])
            ->each(fn ($row) => DB::table('agents')->where('id', $row->id)
                ->update(['agent_ref' => 'agent_' . str_pad((string) $row->id, 2, '0', STR_PAD_LEFT)]));
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'agent_ref', 'template_id', 'role', 'calling_mode', 'voice', 'goal',
                'closing_message', 'objection_handling', 'faqs', 'business_variables',
                'custom_variables', 'provider', 'provider_agent_id', 'provider_agent_version',
                'provider_deployment_id', 'provider_metadata', 'assigned_phone_number_id',
                'error_message', 'last_synced_at',
            ]);
        });
    }
};
