<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ZERO_DECIMAL_CURRENCY_CODES = ['RWF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UGX', 'KES', 'TZS'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $convertibleCurrencyIds = DB::table('currencies')
            ->whereNotIn('code', self::ZERO_DECIMAL_CURRENCY_CODES)
            ->pluck('id')
            ->all();

        if ($convertibleCurrencyIds !== []) {
            DB::table('hosting_plan_pricing')
                ->whereIn('currency_id', $convertibleCurrencyIds)
                ->update([
                    'regular_price' => DB::raw('regular_price / 100'),
                    'renewal_price' => DB::raw('renewal_price / 100'),
                ]);

            $placeholders = implode(',', array_fill(0, count($convertibleCurrencyIds), '?'));
            DB::statement("
                UPDATE hosting_plan_price_histories h
                INNER JOIN hosting_plan_pricing p ON h.hosting_plan_pricing_id = p.id
                SET h.regular_price = h.regular_price / 100, h.renewal_price = h.renewal_price / 100
                WHERE p.currency_id IN ({$placeholders})
            ", $convertibleCurrencyIds);
        }

        Schema::table('hosting_plan_pricing', function (Blueprint $table): void {
            $table->decimal('regular_price', 10, 2)->change();
            $table->decimal('renewal_price', 10, 2)->change();
        });

        Schema::table('hosting_plan_price_histories', function (Blueprint $table): void {
            $table->decimal('regular_price', 10, 2)->change();
            $table->decimal('renewal_price', 10, 2)->change();
        });
    }
};
