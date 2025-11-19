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
        Schema::create('hosting_plan_prices', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('hosting_plan_id')->constrained('hosting_plans');
            $table->string('billing_cycle');
            $table->integer('regular_price');
            $table->integer('promotional_price')->nullable();
            $table->integer('renewal_price');
            $table->integer('discount_percentage')->nullable();
            $table->date('promotional_start_date')->nullable();
            $table->date('promotional_end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('hosting_plan_id');
            $table->index('status');

        });
    }
};
