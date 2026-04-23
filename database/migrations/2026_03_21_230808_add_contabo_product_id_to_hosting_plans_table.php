<?php

declare(strict_types=1);

use App\Models\HostingPlan;
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
        Schema::table('hosting_plans', function (Blueprint $table): void {
            $table->string('contabo_product_id')->nullable()->after('status');
        });

        // Fix sort_order and set Contabo product IDs for VPS plans
        HostingPlan::query()->where('slug', 'vps-starter')->update(['sort_order' => 1, 'contabo_product_id' => 'V91']);
        HostingPlan::query()->where('slug', 'vps-business')->update(['sort_order' => 2, 'contabo_product_id' => 'V94']);
        HostingPlan::query()->where('slug', 'vps-gold')->update(['sort_order' => 3, 'contabo_product_id' => 'V97']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_plans', function (Blueprint $table): void {
            $table->dropColumn('contabo_product_id');
        });

        HostingPlan::query()->where('slug', 'vps-starter')->update(['sort_order' => 0]);
        HostingPlan::query()->where('slug', 'vps-business')->update(['sort_order' => 0]);
        HostingPlan::query()->where('slug', 'vps-gold')->update(['sort_order' => 0]);
    }
};
