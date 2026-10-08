<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| A client's ask for the team to build an agent for them, as an alternative
| to building it themselves in the Agents tab.
|
| This never turns into an Agent row by itself -- staff read the brief here
| and create the agent the normal way (the same builder a client would use,
| or the internal client page), then come back and set fulfilled_agent_id so
| the client can see which agent answered their request.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_build_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('details');
            $table->string('status', 16)->default('pending'); // pending | in_progress | fulfilled | declined
            $table->foreignId('fulfilled_agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_build_requests');
    }
};
