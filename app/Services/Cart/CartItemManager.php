<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\CartItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class CartItemManager
{
    /**
     * Create a new cart item
     */
    public function create(array $data): CartItem|array
    {
        if ($this->getStorageStrategy() === 'database') {
            return $this->createInDatabase($data);
        }

        return $this->createInSession($data);
    }

    /**
     * Update an existing cart item
     */
    public function update(int|string $id, array $data): bool
    {
        if ($this->getStorageStrategy() === 'database') {
            return $this->updateInDatabase($id, $data);
        }

        return $this->updateInSession($id, $data);
    }

    /**
     * Delete a cart item
     */
    public function delete(int|string $id): bool
    {
        if ($this->getStorageStrategy() === 'database') {
            return $this->deleteFromDatabase($id);
        }

        return $this->deleteFromSession($id);
    }

    /**
     * Find a single cart item
     */
    public function find(int|string $id): CartItem|array|null
    {
        if ($this->getStorageStrategy() === 'database') {
            return $this->findInDatabase($id);
        }

        return $this->findInSession($id);
    }

    /**
     * Get all cart items
     */
    public function all(): Collection
    {
        if ($this->getStorageStrategy() === 'database') {
            return $this->allFromDatabase();
        }

        return $this->allFromSession();
    }

    /**
     * Get count of cart items
     */
    public function count(): int
    {
        return $this->all()->count();
    }

    /**
     * Create item in database
     */
    private function createInDatabase(array $data): CartItem
    {
        $data['user_id'] = Auth::id();
        $data['session_id'] = null;

        return CartItem::create($data);
    }

    /**
     * Create item in session
     */
    private function createInSession(array $data): array
    {
        $items = session('cart.items', []);
        $id = Str::uuid()->toString();

        $data['id'] = $id;
        $data['created_at'] = now()->toISOString();
        $data['updated_at'] = now()->toISOString();

        $items[$id] = $data;
        session(['cart.items' => $items]);

        return $data;
    }

    /**
     * Update item in database
     */
    private function updateInDatabase(int|string $id, array $data): bool
    {
        $item = CartItem::forUser(Auth::id())->find($id);

        if (! $item) {
            return false;
        }

        return $item->update($data);
    }

    /**
     * Update item in session
     */
    private function updateInSession(string $id, array $data): bool
    {
        $items = session('cart.items', []);

        if (! isset($items[$id])) {
            return false;
        }

        $items[$id] = array_merge($items[$id], $data);
        $items[$id]['updated_at'] = now()->toISOString();

        session(['cart.items' => $items]);

        return true;
    }

    /**
     * Delete item from database
     */
    private function deleteFromDatabase(int|string $id): bool
    {
        $item = CartItem::forUser(Auth::id())->find($id);

        if (! $item) {
            return false;
        }

        return (bool) $item->delete();
    }

    /**
     * Delete item from session
     */
    private function deleteFromSession(string $id): bool
    {
        $items = session('cart.items', []);

        if (! isset($items[$id])) {
            return false;
        }

        unset($items[$id]);
        session(['cart.items' => $items]);

        return true;
    }

    /**
     * Find item in database
     */
    private function findInDatabase(int|string $id): ?CartItem
    {
        return CartItem::forUser(Auth::id())->find($id);
    }

    /**
     * Find item in session
     */
    private function findInSession(string $id): ?array
    {
        $items = session('cart.items', []);

        return $items[$id] ?? null;
    }

    /**
     * Get all items from database
     */
    private function allFromDatabase(): Collection
    {
        return CartItem::forUser(Auth::id())->get();
    }

    /**
     * Get all items from session
     */
    private function allFromSession(): Collection
    {
        $items = session('cart.items', []);

        return collect($items)->map(function ($item) {
            // Convert array to object for consistent interface
            return (object) $item;
        });
    }

    /**
     * Determine storage strategy
     */
    private function getStorageStrategy(): string
    {
        return Auth::check() ? 'database' : 'session';
    }
}
