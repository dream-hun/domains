<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('type')->default('registration');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->decimal('total_amount');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('coupon_code')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('items')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method');
            $table->string('payment_status')->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'order_number']);
        });
    }
};
