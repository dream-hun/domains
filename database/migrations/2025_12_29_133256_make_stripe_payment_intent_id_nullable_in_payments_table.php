<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('stripe_payment_intent_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            // First, update any NULL values to a placeholder to avoid constraint violation
            DB::table('payments')
                ->whereNull('stripe_payment_intent_id')
                ->update(['stripe_payment_intent_id' => Str::uuid()]);

            $table->string('stripe_payment_intent_id')->nullable(false)->change();
        });
    }
};
