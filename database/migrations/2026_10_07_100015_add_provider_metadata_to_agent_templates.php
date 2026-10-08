<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Room for the provider-side details a template carries beyond its agent id --
 * the published test-suite id, for instance, which the checks runner needs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_templates', function (Blueprint $table) {
            $table->json('provider_metadata')->nullable()->after('provider_agent_version');
        });
    }

    public function down(): void
    {
        Schema::table('agent_templates', fn (Blueprint $table) => $table->dropColumn('provider_metadata'));
    }
};
