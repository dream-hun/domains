<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_moderations', function (Blueprint $table): void {
            $table->boolean('is_override')->default(false)->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('game_moderations', function (Blueprint $table): void {
            $table->dropColumn('is_override');
        });
    }
};
