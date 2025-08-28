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
        Schema::create('domain_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains');
            $table->foreignId('contact_id')->constrained('contacts');
            $table->string('type');
            $table->foreignId('user_id')->constrained('users');
        });
    }
};
