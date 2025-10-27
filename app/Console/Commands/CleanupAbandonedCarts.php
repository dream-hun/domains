<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CartItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CleanupAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:cleanup {--days=90 : Number of days after which cart items are considered abandoned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove abandoned cart items older than specified days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up cart items older than {$days} days (before {$cutoffDate->toDateString()})...");

        // Delete cart items that are:
        // 1. Older than the cutoff date
        // 2. Don't belong to users who have placed orders after the cart item was created
        $deleted = CartItem::where('created_at', '<', $cutoffDate)
            ->whereDoesntHave('user.orders', function ($query) {
                $query->where('created_at', '>', DB::raw('cart_items.created_at'));
            })
            ->delete();

        $this->info("Deleted {$deleted} abandoned cart items.");

        return self::SUCCESS;
    }
}
