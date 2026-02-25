<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropForeign(['moderator_id']);
            $table->dropColumn(['moderator_id', 'moderator_note', 'moderation_date']);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->foreignId('moderator_id')->nullable()->references('id')->on('users');
            $table->longText('moderator_note')->nullable();
            $table->dateTime('moderation_date')->nullable();
        });
    }
};
