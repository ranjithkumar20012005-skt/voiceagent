<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Our own authoritative pool of calling numbers.
 *
 * The voice platform has no API for listing the numbers a workspace owns, so
 * this table -- not the provider -- is the source of truth for what exists and
 * who holds it. Numbers are imported admin-side once; allocation to a workspace
 * and an agent happens here, under a row lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('provider', 32)->default('sarvam');
            $table->string('provider_number_id', 191)->nullable();
            $table->string('phone_number', 20)->unique();
            $table->string('country', 2)->default('IN');
            $table->json('capabilities')->nullable();
            $table->string('status', 16)->default('available'); // available|assigned|active|inactive
            $table->unsignedBigInteger('assigned_agent_id')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
