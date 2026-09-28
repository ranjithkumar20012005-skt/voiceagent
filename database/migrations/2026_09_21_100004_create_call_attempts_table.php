<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable();

            // Sarvam identifiers. attempt_id is the idempotency key for webhooks.
            $table->string('attempt_id')->nullable();
            $table->string('campaign_id')->nullable();
            $table->string('cohort_id')->nullable();
            $table->string('interaction_id')->nullable();

            $table->string('direction', 16)->default('outbound');

            // Local lifecycle: queued | dispatched | completed | failed
            $table->string('status', 32)->default('queued');

            // Straight from Sarvam campaign webhooks: completed | partial | failed
            $table->string('completion_status', 32)->nullable();

            // Canonical connectivity: connected | no_answer | busy | failed
            $table->string('connectivity_status', 32)->nullable();

            // Canonical business outcome, derived from agent output variables.
            $table->string('call_disposition', 32)->nullable();

            $table->boolean('lead_generated')->default(false);
            $table->boolean('callback_required')->default(false);
            $table->timestamp('callback_at')->nullable();

            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('retry_attempt')->default(0);

            $table->string('agent_phone_number', 20)->nullable();
            $table->string('customer_phone_number', 20)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->json('initial_agent_variables')->nullable();
            $table->json('final_agent_variables')->nullable();
            $table->json('output_agent_variables')->nullable();
            $table->json('transcript')->nullable();
            $table->json('raw_webhook_payload')->nullable();

            $table->foreignId('user_id')->nullable();
            $table->timestamp('webhook_received_at')->nullable();

            $table->timestamps();

            // attempt_id is unique where present -- the webhook idempotency key.
            $table->unique('attempt_id');
            $table->index('interaction_id');
            $table->index('customer_id');
            $table->index('campaign_id');
            $table->index('cohort_id');
            $table->index('status');
            $table->index('connectivity_status');
            $table->index('call_disposition');
            $table->index('callback_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_attempts');
    }
};
