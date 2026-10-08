<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-workspace metering.
 *
 * Every customer shares one provider account, so the provider's own totals
 * cannot tell us who used what. This table is the only record of that, and it
 * keeps our wholesale cost separate from what the customer is charged -- only
 * the latter is ever shown to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            $table->unsignedBigInteger('call_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();

            $table->unsignedInteger('duration_seconds')->default(0);
            $table->decimal('billable_minutes', 10, 2)->default(0);

            $table->string('provider', 32)->default('sarvam');
            $table->decimal('provider_cost', 12, 4)->nullable(); // internal only
            $table->decimal('customer_cost', 12, 4)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->timestamps();

            // One usage row per call: makes metering idempotent under webhook replay.
            $table->unique('call_id');
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
