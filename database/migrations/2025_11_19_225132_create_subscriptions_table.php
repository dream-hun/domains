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
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('hosting_plan_id')->constrained('hosting_plans');
            $table->foreignId('hosting_plan_pricing_id')->constrained('hosting_plan_pricing');
            $table->json('product_snapshot');
            $table->string('billing_cycle');
            $table->string('domain')->nullable();
            $table->string('status');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('next_renewal_at')->nullable();
            $table->string('provider_resource_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('last_renewal_attempt_at')->nullable();
            $table->decimal('custom_price')->nullable();
            $table->string('custom_price_currency', 3)->nullable();
            $table->boolean('is_custom_price')->default(false);
            $table->timestamp('last_invoice_generated_at')->nullable();
            $table->timestamp('next_invoice_due_at')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('custom_price_notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_by_admin_id']);
        });
    }
};
