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
        Schema::create('tld_pricing', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('tld_id')->nullable()->constrained('tld');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->integer('register_price');
            $table->integer('renew_price');
            $table->integer('redemption_price')->nullable();
            $table->integer('transfer_price')->nullable();
            $table->boolean('is_current')->default(true);
            $table->dateTime('effective_date');
            $table->timestamps();

            $table->index(['tld_id', 'currency_id']);
        });
    }
};
