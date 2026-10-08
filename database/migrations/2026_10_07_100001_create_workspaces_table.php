<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A workspace is one customer business: the tenant boundary for every
 * business record in the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('business_name')->nullable();
            $table->string('default_language', 32)->nullable();
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->string('currency', 3)->default('INR');
            $table->string('status', 16)->default('active');
            $table->decimal('credit_balance', 14, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
