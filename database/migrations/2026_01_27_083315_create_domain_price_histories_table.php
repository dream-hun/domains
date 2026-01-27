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
        Schema::create('domain_price_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('domain_price_id')->constrained('domain_prices');
            $table->integer('register_price');
            $table->integer('renewal_price');
            $table->integer('transfer_price');
            $table->integer('redemption_price')->nullable();
            $table->json('changes')->nullable();
            $table->json('old_values')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->longText('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }
};
