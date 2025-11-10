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
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('payment_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            $table->string('type')->after('order_number')->default('registration'); // registration, renewal, transfer
            $table->decimal('subtotal', 10, 2)->after('total_amount');
            $table->decimal('tax', 10, 2)->default(0)->after('subtotal');
            $table->json('items')->nullable()->after('billing_postal_code'); // cart items snapshot
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->dropColumn(['payment_id', 'type', 'subtotal', 'tax', 'items']);
        });
    }
};
