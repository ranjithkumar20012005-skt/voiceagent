<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an agent is allowed to call.
 *
 * Calling hours were a single pair of environment variables for the whole
 * server, which cannot work once each client runs their own agent: a clinic
 * taking inbound calls around the clock and an outbound renewals campaign that
 * must not ring anyone at 9pm are the same setting today.
 *
 * `is_always_on` is the 24/7 case and skips the window entirely.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->boolean('is_always_on')->default(false)->after('calling_mode');
            $table->string('calling_window_start', 5)->nullable()->after('is_always_on');
            $table->string('calling_window_end', 5)->nullable()->after('calling_window_start');
            $table->json('calling_days')->nullable()->after('calling_window_end');
            $table->string('timezone', 64)->nullable()->after('calling_days');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['is_always_on', 'calling_window_start', 'calling_window_end', 'calling_days', 'timezone']);
        });
    }
};
