<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('pawapay_deposit_id')->nullable()->after('stripe_charge_id');
            $table->dropColumn(['kpay_transaction_id', 'kpay_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('kpay_transaction_id')->nullable();
            $table->string('kpay_ref_id')->nullable();
            $table->dropColumn('pawapay_deposit_id');
        });
    }
};
