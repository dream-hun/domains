<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // SQLite doesn't support MODIFY COLUMN with ENUM, and doesn't enforce ENUMs anyway
        // For MySQL/MariaDB, we need to modify the enum
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('stripe', 'mtn_mobile_money', 'kpay') DEFAULT 'stripe'");
        }

        // For SQLite and other databases, the enum constraint is not enforced at the database level
        // The application will handle validation
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('stripe', 'mtn_mobile_money') DEFAULT 'stripe'");
        }
    }
};
