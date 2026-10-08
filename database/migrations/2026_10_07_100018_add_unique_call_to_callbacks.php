<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One callback per call.
 *
 * The provider retries webhook delivery, so the same completed call can arrive
 * several times. This constraint is what makes the callback write idempotent at
 * the database level rather than relying on the application getting it right.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('callbacks', function (Blueprint $table) {
            $table->unique('call_id');
        });
    }

    public function down(): void
    {
        Schema::table('callbacks', function (Blueprint $table) {
            $table->dropUnique(['call_id']);
        });
    }
};
