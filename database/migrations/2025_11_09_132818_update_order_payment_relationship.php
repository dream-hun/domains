<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('order_id')
                ->after('id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('attempt_number')
                ->default(1)
                ->after('order_id');

            $table->string('stripe_session_id')
                ->nullable()
                ->after('stripe_payment_intent_id');

            $table->json('failure_details')
                ->nullable()
                ->after('metadata');

            $table->timestamp('last_attempted_at')
                ->nullable()
                ->after('failure_details');

            $table->index(['order_id', 'status']);
        });

        if (Schema::hasColumn('payments', 'stripe_payment_intent_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropUnique('payments_stripe_payment_intent_id_unique');
            });
        }

        DB::table('orders')
            ->whereNotNull('payment_id')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('payments')
                        ->where('id', $order->payment_id)
                        ->update([
                            'order_id' => $order->id,
                            'attempt_number' => 1,
                            'stripe_session_id' => $order->stripe_session_id,
                            'last_attempted_at' => now(),
                        ]);
                }
            }, 'id');

        DB::table('transactions')
            ->orderBy('id')
            ->chunkById(100, function ($transactions): void {
                foreach ($transactions as $transaction) {
                    $paymentId = DB::table('payments')
                        ->where('order_id', $transaction->order_id)
                        ->orderByDesc('attempt_number')
                        ->orderByDesc('id')
                        ->value('id');

                    if ($paymentId) {
                        DB::table('transactions')
                            ->where('id', $transaction->id)
                            ->update(['payment_id' => $paymentId]);
                    }
                }
            }, 'id');

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'payment_id')) {
                $table->dropConstrainedForeignId('payment_id');
            }
        });

        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('transactions', 'payment_id')) {
                $table->foreignId('payment_id')
                    ->nullable()
                    ->after('order_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->dropIndex(['order_id', 'status']);
            $table->dropColumn([
                'order_id',
                'attempt_number',
                'stripe_session_id',
                'failure_details',
                'last_attempted_at',
            ]);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->unique('stripe_payment_intent_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('payment_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('transactions', 'payment_id')) {
                $table->dropConstrainedForeignId('payment_id');
            }
        });

        DB::table('payments')
            ->whereNotNull('order_id')
            ->orderBy('order_id')
            ->chunkById(100, function ($payments): void {
                foreach ($payments as $payment) {
                    DB::table('orders')
                        ->where('id', $payment->order_id)
                        ->update([
                            'payment_id' => $payment->id,
                            'stripe_session_id' => $payment->stripe_session_id,
                        ]);
                }
            }, 'id');
    }
};
