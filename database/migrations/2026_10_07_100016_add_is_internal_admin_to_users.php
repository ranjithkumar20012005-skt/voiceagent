<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marks the accounts belonging to our own team.
 *
 * Internal staff reach the admin area, where client workspaces are created and
 * mapped to the hosted agents we build. Client users never have this flag, so the
 * admin area is invisible to them.
 *
 * The existing seeded admin account is promoted, because it is the account our
 * team has been using; every other existing user stays a plain client user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_internal_admin')->default(false)->index();
        });

        DB::table('users')->where('email', 'admin@gmail.com')->update(['is_internal_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_internal_admin'));
    }
};
