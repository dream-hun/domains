<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing foreign keys and recreate with cascade delete
        Schema::table('domain_contacts', function (Illuminate\Database\Schema\Blueprint $table): void {
            $table->dropForeign(['domain_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['user_id']);

            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_contacts', function (Illuminate\Database\Schema\Blueprint $table): void {
            $table->dropForeign(['domain_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['user_id']);

            $table->foreign('domain_id')->references('id')->on('domains');
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
