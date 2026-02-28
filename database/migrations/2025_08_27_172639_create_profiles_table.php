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
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('player_id')->references('id')->on('users');
            $table->date('date_of_birth');
            $table->string('profile_image')->nullable();
            $table->foreignId('country_id')->constrained();
            $table->string('city');
            $table->string('phone_number');
            $table->longText('bio');
            $table->string('position');
            $table->timestamps();

            $table->index(['uuid', 'player_id', 'id']);
        });
    }
};
