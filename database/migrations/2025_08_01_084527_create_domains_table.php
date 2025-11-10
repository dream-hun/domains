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
            $table->string('registrar')->nullable();
            $table->string('provider')->nullable()->comment('Service used for registration (EPP, Namecheap)');
            $table->integer('years')->default(1);
            $table->string('status');
            $table->boolean('auto_renew')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('registered_at');
            $table->timestamp('expires_at');
            $table->timestamp('last_renewed_at')->nullable();
            $table->foreignId('domain_price_id')->constrained('domain_prices');
            $table->foreignId('owner_id')->constrained('users');
            $table->timestamps();
        });
    }
};
