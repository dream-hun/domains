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
        Schema::create('domains', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->string('name')->unique();
            $table->string('auth_code')->nullable();
            $table->integer('years')->default(1);
            $table->string('status');
            $table->boolean('auto_renew')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('registered_at');
            $table->timestamp('expires_at');
            $table->timestamp('last_renewed_at')->nullable();
            $table->foreignId('tld_pricing_id')->constrained('tld_pricing');
            $table->decimal('custom_price')->nullable();
            $table->string('custom_price_currency', 3)->nullable();
            $table->boolean('is_custom_price')->default(false);
            $table->text('custom_price_notes')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users');
            $table->timestamps();

            $table->index(['owner_id', 'name']);
        });
    }
};
