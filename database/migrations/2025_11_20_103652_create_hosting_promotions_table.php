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
        Schema::create('hosting_promotions', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('hosting_plan_id')->constrained('hosting_plans')->cascadeOnDelete();
            $table->string('billing_cycle');
            $table->decimal('discount_percentage', 5, 2)->unsigned();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['hosting_plan_id', 'billing_cycle']);
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosting_promotions');
    }
};
