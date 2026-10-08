<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The remaining fields the Create Agent form collects.
 *
 * Everything else it needs -- role, calling_mode, voice, first_message,
 * instructions, goal, closing_message, objection_handling, faqs and the variable
 * bags -- already exists on the agents table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->json('secondary_languages')->nullable()->after('default_language');
            $table->unsignedSmallInteger('max_call_seconds')->default(420)->after('secondary_languages');
            // Kept narrow on purpose: the platform locks the model variant, so
            // temperature is the only generation knob we can offer.
            $table->decimal('temperature', 3, 2)->default(0.40)->after('max_call_seconds');
            $table->timestamp('provision_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['secondary_languages', 'max_call_seconds', 'temperature', 'provision_requested_at']);
        });
    }
};
