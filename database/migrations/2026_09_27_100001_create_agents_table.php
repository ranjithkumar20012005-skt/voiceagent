<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Agents registered in this application.
|
| An agent here is a pointer to an agent that already exists in the voice
| platform (its app id + version). The live prompt, voice and knowledge are
| managed there; `instructions` and `first_message` are the team's reference
| copy only and are never pushed upstream.
|
| Telephony (connection id, caller number) stays workspace-wide in the
| environment, so every agent shares the configured line.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->string('platform_app_id', 191)->nullable();
            $table->unsignedInteger('platform_app_version')->nullable();
            $table->string('default_language', 32)->nullable();
            $table->text('first_message')->nullable();
            $table->text('instructions')->nullable();
            $table->string('status', 16)->default('active');   // active | inactive
            $table->boolean('is_default')->default(false);
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamps();

            $table->index('status');
        });

        Schema::table('call_attempts', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('customer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('call_attempts', function (Blueprint $table) {
            $table->dropIndex(['agent_id']);
            $table->dropColumn('agent_id');
        });

        Schema::dropIfExists('agents');
    }
};
