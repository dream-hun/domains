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
        Schema::create('games', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->string('format')->default('5v5');
            $table->foreignId('court_id')->nullable()->constrained();
            $table->foreignId('player_id')->references('id')->on('users');
            $table->foreignId('moderator_id')->nullable()->references('id')->on('users');
            $table->string('title');
            $table->string('vimeo_uri')->nullable();
            $table->string('vimeo_status')->nullable();
            $table->dateTime('played_at');
            $table->string('status')->default('pending');
            $table->longText('moderator_note')->nullable();
            $table->dateTime('moderation_date')->nullable();
            $table->timestamps();
        });
    }
};
