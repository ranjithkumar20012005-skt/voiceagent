<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarded with hasTable because some existing installs already carry a
     * `users` table created by the original Laravel skeleton migration, whose
     * file has since been removed. Without the guard, `migrate` aborts on
     * those databases while still needing to run on a fresh one.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Admin');
            $table->string('email')->unique()->default('admin@gmail.com');
            $table->string('password')->default(bcrypt('Greet@123')); // hashed default password
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
