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
        Schema::create('feature_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('icon')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('slug');
        });
    }
};
