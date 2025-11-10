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
        Schema::create('domain_nameservers', function (Blueprint $table): void {

            $table->id();
            $table->foreignId('domain_id')->constrained('domains');
            $table->foreignId('nameserver_id')->constrained('nameservers');
            $table->unique(['domain_id', 'nameserver_id']);
        });
    }
};
