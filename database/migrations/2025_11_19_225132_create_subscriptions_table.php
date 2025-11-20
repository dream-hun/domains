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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('hosting_plan_id')->constrained('hosting_plans');
            $table->foreignId('hosting_plan_price_id')->constrained('hosting_plan_prices');
            $table->json('product_snapshot');
            $table->string('billing_cycle');
            $table->string('domain');
            $table->string('status');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('next_renewal_at')->nullable();
            $table->string('provider_resource_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }
};
