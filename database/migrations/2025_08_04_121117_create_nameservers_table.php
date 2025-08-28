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
        Schema::create('nameservers', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('domain_id')->constrained();
            $table->string('name');
            $table->string('type')->default('default');
            $table->string('ipv4')->nullable();
            $table->string('ipv6')->nullable();
            $table->integer('priority')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
};
