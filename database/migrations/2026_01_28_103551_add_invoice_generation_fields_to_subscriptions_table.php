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
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('last_invoice_generated_at')->nullable()->after('last_renewal_attempt_at');
            $table->timestamp('next_invoice_due_at')->nullable()->after('last_invoice_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'last_invoice_generated_at',
                'next_invoice_due_at',
            ]);
        });
    }
};
