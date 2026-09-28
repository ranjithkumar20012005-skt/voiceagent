<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->boolean('enabled')->default(false);

            $table->string('frequency', 32)->default('daily');
            $table->string('run_at', 5)->default('08:00');      // HH:MM
            $table->string('timezone', 64)->default('Asia/Kolkata');

            // Eligibility filters
            $table->unsignedSmallInteger('expiry_within_days')->default(30);
            $table->json('customer_statuses')->nullable();       // e.g. ["pending"]
            $table->boolean('skip_do_not_call')->default(true);
            $table->boolean('skip_already_renewed')->default(true);
            $table->boolean('skip_active_callback')->default(true);
            $table->unsignedSmallInteger('min_days_between_calls')->default(1);

            // Dispatch settings
            $table->unsignedInteger('max_calls_per_run')->default(200);
            $table->unsignedSmallInteger('max_retries')->default(2);
            $table->string('window_start', 5)->default('09:00');
            $table->string('window_end', 5)->default('19:00');
            $table->decimal('attempts_per_second', 6, 2)->default(1.0);

            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status', 32)->nullable();
            $table->text('last_run_message')->nullable();
            $table->unsignedInteger('last_run_count')->default(0);

            $table->timestamps();

            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
