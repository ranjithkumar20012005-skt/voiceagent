<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('customer_identifier')->nullable()->comment('Client-side ID from the imported sheet');
            $table->string('name')->nullable();
            $table->string('phone_number', 20)->comment('E.164');
            $table->string('policy_number')->nullable();
            $table->string('registered_mobile', 20)->nullable();
            $table->date('policy_expiry_date')->nullable();
            $table->decimal('renewal_premium', 12, 2)->nullable();
            $table->string('preferred_language', 32)->nullable();

            // Lifecycle: pending | queued | in_progress | contacted | closed
            $table->string('customer_status', 32)->default('pending');

            // Latest business outcome, mirrored from the most recent completed call.
            $table->string('last_outcome', 32)->nullable();
            $table->string('last_connectivity', 32)->nullable();

            $table->timestamp('last_call_at')->nullable();
            $table->timestamp('next_callback_at')->nullable();
            $table->unsignedInteger('call_count')->default(0);

            $table->boolean('do_not_call')->default(false);
            $table->text('notes')->nullable();

            $table->foreignId('import_batch_id')->nullable()->index();

            $table->timestamps();

            $table->index('phone_number');
            $table->index('policy_number');
            $table->index('customer_status');
            $table->index('last_outcome');
            $table->index('next_callback_at');
            $table->index('policy_expiry_date');
            $table->index('do_not_call');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
