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
            $table->decimal('custom_price', 10, 2)->nullable()->after('hosting_plan_price_id');
            $table->string('custom_price_currency', 3)->nullable()->after('custom_price');
            $table->boolean('is_custom_price')->default(false)->after('custom_price_currency');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete()->after('is_custom_price');
            $table->text('custom_price_notes')->nullable()->after('created_by_admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['created_by_admin_id']);
            $table->dropColumn([
                'custom_price',
                'custom_price_currency',
                'is_custom_price',
                'created_by_admin_id',
                'custom_price_notes',
            ]);
        });
    }
};
