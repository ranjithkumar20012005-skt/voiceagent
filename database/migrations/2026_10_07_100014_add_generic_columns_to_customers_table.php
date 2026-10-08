<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the customer record usable outside insurance renewals.
 *
 * The existing policy columns stay exactly where they are: live data sits in
 * them and the renewal automation still reads them. New verticals use
 * custom_fields instead of growing more columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->string('company')->nullable()->after('email');
            $table->string('location')->nullable()->after('company');
            $table->string('source', 48)->nullable()->after('location');
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn(['email', 'company', 'location', 'source', 'tags', 'custom_fields']);
        });
    }
};
