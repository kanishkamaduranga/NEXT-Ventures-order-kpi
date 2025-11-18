<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('refund_id')->unique(); // Unique identifier for idempotency
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['full', 'partial'])->default('full');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('reason')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('payment_reference')->nullable(); // Reference from payment gateway
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['customer_id']);
            $table->index(['refund_id']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

