<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where an agent's instant leads come in from.
 *
 * Each row is one intake endpoint with its own secret token, so a client can
 * connect a website form, a lead-ads provider or an automation tool without any
 * of them sharing a credential -- and a leaked token is revoked by deleting one
 * row rather than rotating everything.
 *
 * `field_map` records which incoming field holds the phone number and the name,
 * because every source names them differently and we will not guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_lead_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->unsignedBigInteger('agent_id')->index();

            $table->string('name');
            // website_form | meta_lead | instagram | automation | api | sheet
            $table->string('kind', 32)->default('website_form');
            $table->string('token', 64)->unique();
            $table->boolean('enabled')->default(true);

            $table->json('field_map')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedInteger('lead_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->timestamp('last_lead_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_lead_sources');
    }
};
