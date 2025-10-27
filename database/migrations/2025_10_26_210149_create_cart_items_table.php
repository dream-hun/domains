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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->string('domain_name');
            $table->enum('domain_type', ['registration', 'transfer', 'renewal'])->default('registration');
            $table->string('tld', 50);

            // Base Pricing
            $table->decimal('base_price', 10, 2);
            $table->string('base_currency', 3)->default('USD');

            // Additional Fees
            $table->decimal('eap_fee', 10, 2)->default(0);
            $table->decimal('premium_fee', 10, 2)->default(0);
            $table->decimal('privacy_fee', 10, 2)->default(0);

            // Configuration
            $table->integer('years')->default(1);
            $table->integer('quantity')->default(1);

            // Metadata
            $table->json('attributes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['user_id', 'domain_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
