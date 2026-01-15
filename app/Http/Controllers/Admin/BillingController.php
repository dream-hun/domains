<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use App\Models\Order;
use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class BillingController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        $orders = Order::query()
            ->with(['orderItems', 'user.roles', 'payments'])
            ->unless($user->isAdmin(), function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })->latest()
            ->paginate(15);

        return view('admin.billing.index', ['orders' => $orders]);
    }

    public function show(Order $order): View
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        abort_if(! $user->isAdmin() && $order->user_id !== $user->id, 403);

        $order->load(['orderItems', 'user', 'payments']);

        $this->preloadSubscriptionsAndPlans($order);

        return view('admin.billing.show', ['order' => $order]);
    }

    public function invoice(Order $order): View
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        // Check authorization: either admin or order owner
        abort_if(! $user->isAdmin() && $order->user_id !== $user->id, 403);

        $order->load(['orderItems', 'user', 'payments']);

        $this->preloadSubscriptionsAndPlans($order);

        return view('admin.billing.invoice', ['order' => $order]);
    }

    public function downloadInvoice(Order $order): Response
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        // Check authorization: either admin or order owner
        abort_if(! $user->isAdmin() && $order->user_id !== $user->id, 403);

        $order->load(['orderItems', 'user', 'payments']);

        $this->preloadSubscriptionsAndPlans($order);

        $pdf = Pdf::loadView('admin.billing.invoice-pdf', ['order' => $order]);

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }

    public function viewInvoicePdf(Order $order): Response
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        abort_if(! $user->isAdmin() && $order->user_id !== $user->id, 403);

        $order->load(['orderItems', 'user', 'payments']);

        $this->preloadSubscriptionsAndPlans($order);

        $pdf = Pdf::loadView('admin.billing.invoice-pdf', ['order' => $order]);

        return $pdf->stream('invoice-'.$order->order_number.'.pdf');
    }

    private function preloadSubscriptionsAndPlans(Order $order): void
    {
        $subscriptionIds = [];
        $hostingPlanIds = [];

        foreach ($order->orderItems as $item) {
            $metadata = $item->metadata ?? [];

            if (isset($metadata['subscription_id'])) {
                $subscriptionIds[] = $metadata['subscription_id'];
            }

            if (isset($metadata['hosting_plan_id'])) {
                $hostingPlanIds[] = $metadata['hosting_plan_id'];
            }
        }

        if ($subscriptionIds !== []) {
            $subscriptions = Subscription::query()
                ->with('plan')
                ->whereIn('id', array_unique($subscriptionIds))
                ->get()
                ->keyBy('id');

            foreach ($order->orderItems as $item) {
                $metadata = $item->metadata ?? [];
                if (isset($metadata['subscription_id']) && isset($subscriptions[$metadata['subscription_id']])) {
                    $item->setAttribute('_preloaded_subscription', $subscriptions[$metadata['subscription_id']]);
                }
            }
        }

        if ($hostingPlanIds !== []) {
            $plans = HostingPlan::query()
                ->whereIn('id', array_unique($hostingPlanIds))
                ->get()
                ->keyBy('id');

            foreach ($order->orderItems as $item) {
                $metadata = $item->metadata ?? [];
                if (isset($metadata['hosting_plan_id']) && isset($plans[$metadata['hosting_plan_id']])) {
                    $item->setAttribute('_preloaded_plan', $plans[$metadata['hosting_plan_id']]);
                }
            }
        }
    }
}
