<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-workspace settings.
 *
 * app_settings stays as it is -- it is keyed only by `key`, so it can only ever
 * hold platform-wide values, and rewriting its primary key would mean dropping
 * and recreating a table that holds live data. This table carries everything a
 * customer can change about their own workspace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_settings');
    }
};
