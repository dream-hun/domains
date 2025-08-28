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
        Schema::create('domain_prices', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('tld')->unique();
            $table->string('type')->default('international');
            $table->integer('register_price');
            $table->integer('renewal_price');
            $table->integer('transfer_price');
            $table->integer('redemption_price')->nullable();
            $table->integer('min_years')->default(1);
            $table->integer('max_years')->default(10);
            $table->string('status')->default('active');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
};
