<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two fields the result pages need that were not being kept.
 *
 * `summary` holds a summary only when the platform actually sends one -- it is
 * never generated here, so a null means "the provider gave us none" and the page
 * says so rather than inventing something.
 *
 * `language` is likewise only filled when the payload names one, so the analytics
 * breakdown can be hidden instead of guessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_attempts', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('transcript');
            $table->string('language', 48)->nullable()->after('summary');

            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::table('call_attempts', function (Blueprint $table) {
            $table->dropIndex(['language']);
            $table->dropColumn(['summary', 'language']);
        });
    }
};
