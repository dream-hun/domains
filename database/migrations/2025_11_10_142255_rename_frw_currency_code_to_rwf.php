<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('currencies')
            ->where('code', 'FRW')
            ->update(['code' => 'RWF']);

        DB::table('currencies')
            ->where('symbol', 'FRW')
            ->update(['symbol' => 'RWF']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('currencies')
            ->where('code', 'RWF')
            ->update(['code' => 'FRW']);

        DB::table('currencies')
            ->where('symbol', 'RWF')
            ->update(['symbol' => 'FRW']);
    }
};
