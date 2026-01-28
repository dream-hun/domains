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
        Schema::create('hosting_plan_price_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hosting_plan_price_id')->constrained('hosting_plan_prices')->onDelete('cascade');
            $table->integer('regular_price');
            $table->integer('renewal_price');
            $table->json('changes')->nullable();
            $table->json('old_values')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->longText('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('hosting_plan_price_id');
            $table->index('changed_by');
        });
    }
};
