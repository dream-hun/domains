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
        Schema::create('hosting_plan_features', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('hosting_plan_id')->constrained('hosting_plans');
            $table->foreignId('hosting_feature_id')->constrained('hosting_features');
            $table->string('feature_value')->nullable()->comment('e.g., "3", "Unmetered", "true"');
            $table->boolean('is_unlimited')->default(false);
            $table->text('custom_text')->nullable()->comment('Override display text');
            $table->boolean('is_included')->default(true)->comment('Feature included or not');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
