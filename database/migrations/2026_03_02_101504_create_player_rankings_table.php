<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_rankings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_id')->constrained('users')->cascadeOnDelete();
            $table->string('format');
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('total_games')->default(0);
            $table->unsignedInteger('recent_games')->default(0);
            $table->decimal('score', 12, 4)->default(0);
            $table->unsignedInteger('rank')->default(0);
            $table->foreignId('ranking_configuration_id')->constrained('ranking_configurations')->cascadeOnDelete();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['player_id', 'format', 'calculated_at']);
            $table->index(['format', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_rankings');
    }
};
