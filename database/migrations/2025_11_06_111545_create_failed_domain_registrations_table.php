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
        Schema::create('failed_domain_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->string('domain_name');
            $table->text('failure_reason');
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->enum('status', ['pending', 'retrying', 'resolved', 'abandoned'])->default('pending');
            $table->json('contact_ids')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_retry_at']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_domain_registrations');
    }
};
