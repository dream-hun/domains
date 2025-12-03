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
        $columnsToDrop = [
            'promotional_price',
            'discount_percentage',
            'promotional_start_date',
            'promotional_end_date',
        ];

        $existingColumns = array_filter($columnsToDrop, static fn (string $column): bool => Schema::hasColumn('hosting_plan_prices', $column));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('hosting_plan_prices', function (Blueprint $table) use ($existingColumns): void {
            $table->dropColumn($existingColumns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_plan_prices', function (Blueprint $table): void {
            $table->integer('promotional_price')->nullable()->after('regular_price');
            $table->integer('discount_percentage')->nullable()->after('renewal_price');
            $table->date('promotional_start_date')->nullable()->after('discount_percentage');
            $table->date('promotional_end_date')->nullable()->after('promotional_start_date');
        });
    }
};
