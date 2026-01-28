<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // SQLite doesn't support MODIFY COLUMN with ENUM, and doesn't enforce ENUMs anyway
        // For MySQL/MariaDB, we need to modify the enum
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('stripe', 'mtn_mobile_money', 'kpay', 'manual') DEFAULT 'stripe'");
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'cancelled', 'refunded', 'manual') DEFAULT 'pending'");
        }
    }
};
