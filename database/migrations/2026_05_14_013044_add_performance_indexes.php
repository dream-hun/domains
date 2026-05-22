<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tld', function (Blueprint $table): void {
            $table->index('type');
            $table->index('status');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index('status');
            $table->index('payment_status');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->index('user_id');
            $table->index('status');
        });

        Schema::table('domain_contacts', function (Blueprint $table): void {
            $table->index('domain_id');
            $table->index('contact_id');
        });

        Schema::table('domain_nameservers', function (Blueprint $table): void {
            $table->index('domain_id');
        });
    }

    public function down(): void
    {
        Schema::table('tld', function (Blueprint $table): void {
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('domain_contacts', function (Blueprint $table): void {
            $table->dropIndex(['domain_id']);
            $table->dropIndex(['contact_id']);
        });

        Schema::table('domain_nameservers', function (Blueprint $table): void {
            $table->dropIndex(['domain_id']);
        });
    }
};
