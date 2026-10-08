<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The master agents we maintain on the voice platform, admin-managed.
 *
 * A customer picks one of these when creating an agent; their own wording and
 * business details then ride along per call as variables and overrides. The
 * provider identifiers live here and are never rendered to a customer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 64)->nullable();
            $table->text('description')->nullable();

            $table->string('provider', 32)->default('sarvam');
            $table->string('provider_agent_id', 191)->nullable();
            $table->unsignedInteger('provider_agent_version')->nullable();

            $table->json('supported_languages')->nullable();
            $table->json('default_variables')->nullable();
            $table->json('supported_calling_modes')->nullable();

            $table->string('status', 16)->default('active'); // active|unavailable|archived
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_templates');
    }
};
