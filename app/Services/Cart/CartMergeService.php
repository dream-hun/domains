<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\CartItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CartMergeService
{
    /**
     * Merge guest cart into user cart upon authentication
     */
    public function merge(string $sessionId, int $userId): bool
    {
        try {
            return DB::transaction(function () use ($sessionId, $userId) {
                // Get session cart items
                $sessionItems = $this->getSessionItems();

                if (empty($sessionItems)) {
                    return true; // Nothing to merge
                }

                // Get user's existing cart items
                $userItems = CartItem::forUser($userId)->get();

                $mergedCount = 0;

                foreach ($sessionItems as $sessionItem) {
                    // Check if domain already exists in user cart
                    $existingItem = $userItems->firstWhere('domain_name', $sessionItem['domain_name']);

                    if ($existingItem) {
                        // Keep the item with higher quantity (years)
                        if ($sessionItem['years'] > $existingItem->years) {
                            $existingItem->update([
                                'years' => $sessionItem['years'],
                                'base_price' => $sessionItem['base_price'],
                                'base_currency' => $sessionItem['base_currency'],
                                'eap_fee' => $sessionItem['eap_fee'] ?? 0,
                                'premium_fee' => $sessionItem['premium_fee'] ?? 0,
                                'privacy_fee' => $sessionItem['privacy_fee'] ?? 0,
                                'attributes' => $sessionItem['attributes'] ?? null,
                            ]);
                            $mergedCount++;
                        }
                    } else {
                        // Add new item to user cart
                        CartItem::create([
                            'user_id' => $userId,
                            'session_id' => null,
                            'domain_name' => $sessionItem['domain_name'],
                            'domain_type' => $sessionItem['domain_type'] ?? 'registration',
                            'tld' => $sessionItem['tld'],
                            'base_price' => $sessionItem['base_price'],
                            'base_currency' => $sessionItem['base_currency'] ?? 'USD',
                            'eap_fee' => $sessionItem['eap_fee'] ?? 0,
                            'premium_fee' => $sessionItem['premium_fee'] ?? 0,
                            'privacy_fee' => $sessionItem['privacy_fee'] ?? 0,
                            'years' => $sessionItem['years'] ?? 1,
                            'quantity' => $sessionItem['quantity'] ?? 1,
                            'attributes' => $sessionItem['attributes'] ?? null,
                        ]);
                        $mergedCount++;
                    }
                }

                // Clear session cart
                session()->forget('cart');

                Log::info('Guest cart merged successfully', [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'items_merged' => $mergedCount,
                ]);

                return true;
            });
        } catch (Exception $e) {
            Log::error('Failed to merge guest cart', [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get session cart items
     */
    private function getSessionItems(): array
    {
        return session('cart.items', []);
    }
}
