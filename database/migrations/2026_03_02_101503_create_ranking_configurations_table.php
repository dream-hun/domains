<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_configurations', function (Blueprint $table): void {
            $table->id();
            $table->decimal('win_weight', 8, 4)->default(3.0);
            $table->decimal('loss_weight', 8, 4)->default(1.0);
            $table->decimal('game_count_weight', 8, 4)->default(0.5);
            $table->decimal('frequency_weight', 8, 4)->default(2.0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_configurations');
    }
};
