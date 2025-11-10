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
        Schema::create('transfers', function (Blueprint $table): void {
            $table->id();
            $table->uuid();
            $table->foreignId('domain_id')->constrained();
            $table->foreignId('sender_id')->references('id')->on('users');
            $table->string('auth_code');
            $table->string('recipient_email');
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('accepted_by')->nullable()->references('id')->on('users');
            $table->timestamps();
        });
    }
};
