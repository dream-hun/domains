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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('contact_id')->nullable();
            $table->string('contact_type')->default('registrant');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('title')->nullable();
            $table->string('organization')->nullable();
            $table->string('address_one');
            $table->string('address_two')->nullable();
            $table->string('city');
            $table->string('state_province');
            $table->string('postal_code');
            $table->string('country_code');
            $table->string('phone');
            $table->string('phone_extension')->nullable();
            $table->string('fax_number')->nullable();
            $table->string('fax_ext')->nullable();
            $table->string('email');
            $table->foreignId('user_id')->nullable()->index();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });
    }
};
