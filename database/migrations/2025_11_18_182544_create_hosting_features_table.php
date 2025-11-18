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
        Schema::create('hosting_features', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name')->comment('e.g., "Websites", "SSD Storage", "Bandwidth"');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable()->comment('Icon class or path');
            $table->string('category')->comment('e.g., "resources", "security", "email", "performance"');
            $table->foreignId('feature_category_id')->nullable()->constrained('feature_categories');
            $table->string('value_type')->comment('boolean, numeric, text, unlimited');
            $table->string('unit')->nullable()->comment('e.g., "GB", "accounts", "websites"');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_highlighted')->default(false)->comment('Show prominently');
            $table->timestamps();

            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosting_features');
    }
};
