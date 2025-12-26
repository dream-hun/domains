<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists (from partial migration)
        if (! Schema::hasColumn('orders', 'uuid')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->uuid()->nullable()->after('id');
            });
        }

        // Backfill existing orders with empty or null UUIDs
        DB::table('orders')
            ->where(function ($query): void {
                $query->whereNull('uuid')
                    ->orWhere('uuid', '');
            })
            ->orderBy('id')
            ->each(function ($order): void {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        // Check if unique index already exists before adding it
        $indexes = DB::select("SHOW INDEXES FROM orders WHERE Column_name = 'uuid' AND Non_unique = 0");
        if (empty($indexes)) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->uuid('uuid')->nullable(false)->unique()->change();
            });
        } else {
            // Column exists but may not be NOT NULL
            Schema::table('orders', function (Blueprint $table): void {
                $table->uuid('uuid')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
