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
        Schema::table('domains', function (Blueprint $table): void {
            $table->decimal('custom_price', 10, 2)->nullable()->after('is_locked');
            $table->string('custom_price_currency', 3)->nullable()->after('custom_price');
            $table->boolean('is_custom_price')->default(false)->after('custom_price_currency');
            $table->text('custom_price_notes')->nullable()->after('is_custom_price');
            $table->foreignId('created_by_admin_id')->nullable()->after('custom_price_notes')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->after('created_by_admin_id')
                ->constrained('subscriptions')->nullOnDelete();
        });
    }
};
