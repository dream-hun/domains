<?php

declare(strict_types=1);

namespace App\Livewire;

use Cknow\Money\Money;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Livewire\Component;

final class NavbarComponent extends Component
{
    protected $listeners = ['refreshCart' => '$refresh', 'refreshNotifications' => '$refresh'];

    public function getCartItemsCountProperty(): int
    {
        return Cart::getContent()->count();
    }

    public function getFormattedTotalProperty(): string
    {
        return Money::RWF(Cart::getTotal())->format();
    }

    public function getUnreadNotificationsCountProperty(): int
    {
        return auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    public function getRecentNotificationsProperty()
    {
        if (!auth()->check()) {
            return collect();
        }

        return auth()->user()->notifications()->latest()->take(5)->get();
    }

    public function markNotificationAsRead($notificationId): void
    {
        if (!auth()->check()) {
            return;
        }

        \Log::info('Marking notification as read', ['notification_id' => $notificationId]);
        
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->dispatch('refreshNotifications');
            \Log::info('Notification marked as read successfully');
        } else {
            \Log::warning('Notification not found', ['notification_id' => $notificationId]);
        }
    }

    public function markAllNotificationsAsRead(): void
    {
        if (!auth()->check()) {
            return;
        }

        \Log::info('Marking all notifications as read');
        
        auth()->user()->unreadNotifications->markAsRead();
        $this->dispatch('refreshNotifications');
        
        \Log::info('All notifications marked as read successfully');
    }

    public function render(): object
    {
        return view('livewire.navbar-component');
    }
}
