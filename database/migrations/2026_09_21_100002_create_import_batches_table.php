<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('file_type', 16)->nullable();     // csv | xlsx | xls
            $table->unsignedBigInteger('file_size')->nullable();

            // pending_mapping | queued | processing | completed | failed
            $table->string('status', 32)->default('pending_mapping');

            $table->json('column_map')->nullable();          // our field => sheet header
            $table->json('detected_headers')->nullable();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('rejected_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);

            $table->json('rejection_samples')->nullable();   // first N rejects, for the UI
            $table->text('error_message')->nullable();

            $table->foreignId('user_id')->nullable()->index();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
