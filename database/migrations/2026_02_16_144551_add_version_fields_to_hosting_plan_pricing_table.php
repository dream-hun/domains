<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_plan_pricing', function (Blueprint $table): void {
            $table->boolean('is_current')->default(true)->after('status');
            $table->date('effective_date')->nullable()->after('is_current');
        });

        DB::table('hosting_plan_pricing')->update([
            'is_current' => true,
            'effective_date' => now()->toDateString(),
        ]);

        Schema::table('hosting_plan_pricing', function (Blueprint $table): void {
            $table->date('effective_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('hosting_plan_pricing', function (Blueprint $table): void {
            $table->dropColumn(['is_current', 'effective_date']);
        });
    }
};
