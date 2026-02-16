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
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('domain_id')->nullable()->constrained()->onDelete('set null');
            $table->string('domain_name');
            $table->string('domain_type');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('years')->default(1);
            $table->decimal('total_amount', 10, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
};
