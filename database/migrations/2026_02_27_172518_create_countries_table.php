<?php

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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('iso_code')->unique()->nullable();
            $table->string('iso_alpha2')->nullable();
            $table->string('name');
            $table->string('flag')->nullable();
            $table->string('capital')->nullable();
            $table->string('region')->nulllable();
            $table->timestamps();
        });
    }

};
