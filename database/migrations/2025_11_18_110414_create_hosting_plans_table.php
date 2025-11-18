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
        Schema::create('hosting_plans', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->string('tagline');
            $table->boolean('is_popular')->default(false);
            $table->string('status');
            $table->integer('sort_order')->default(0);
            $table->foreignId('category_id')->constrained('hosting_categories');
            $table->timestamps();

            $table->index('slug');
            $table->index('status');
            $table->index('category_id');

        });
    }
};
