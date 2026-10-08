<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Call me tomorrow at eleven" as a first-class record, so a worker can pick
 * due callbacks up later. Until now this lived as two loose columns on the
 * customer and the call attempt, which cannot express more than one pending
 * callback or carry a reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('call_id')->nullable()->index();
            $table->timestamp('scheduled_at');
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->text('reason')->nullable();
            $table->string('status', 16)->default('scheduled'); // scheduled|due|completed|failed|cancelled
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callbacks');
    }
};
