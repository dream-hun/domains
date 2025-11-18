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
        Schema::create('hosting_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->string('description')->nullable();
            $table->string('status');
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
    }
};
