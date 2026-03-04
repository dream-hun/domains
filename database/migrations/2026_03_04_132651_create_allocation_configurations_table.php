<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_configurations', function (Blueprint $table): void {
            $table->id();
            $table->float('insurance_percentage');
            $table->float('savings_percentage');
            $table->float('pathway_percentage');
            $table->float('administration_percentage');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_configurations');
    }
};
