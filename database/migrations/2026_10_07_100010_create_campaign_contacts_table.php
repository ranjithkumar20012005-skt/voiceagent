<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per person in a campaign, so progress, retries and de-duplication are
 * tracked per contact instead of inferred from a JSON list of cohort ids.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('status', 24)->default('pending'); // pending|queued|dispatched|completed|failed|skipped
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_attempt_id', 191)->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            // A person appears at most once per campaign: the de-dupe guarantee.
            $table->unique(['campaign_id', 'customer_id']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_contacts');
    }
};
