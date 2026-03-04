<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 8, 2)->default(1.00);
            $table->decimal('insurance_amount', 8, 4);
            $table->decimal('savings_amount', 8, 4);
            $table->decimal('pathway_amount', 8, 4);
            $table->decimal('administration_amount', 8, 4);
            $table->foreignId('allocation_configuration_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
