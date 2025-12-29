<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

        return view('admin.billing.show', ['order' => $order]);
    }

    public function invoice(Order $order): View
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        // Check authorization: either admin or order owner
        abort_if(! $user->isAdmin() && $order->user_id !== $user->id, 403);

        $order->load(['orderItems', 'user', 'payments']);

        return view('admin.billing.invoice', ['order' => $order]);
    }

    public function downloadInvoice(Order $order): Response
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        // Check authorization: either admin or order owner
        abort_if(! $user->isAdmin() && $order->user_id !== $user->id, 403);

        $order->load(['orderItems', 'user', 'payments']);

        $pdf = Pdf::loadView('admin.billing.invoice-pdf', ['order' => $order]);

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }

    public function viewInvoicePdf(Order $order): Response
    {
        $user = Auth::user();

        $user->loadMissing('roles');

        abort_if(! $user->isAdmin() && $order->user_id !== $user->id, 403);

        $order->load(['orderItems', 'user', 'payments']);

        $pdf = Pdf::loadView('admin.billing.invoice-pdf', ['order' => $order]);

        return $pdf->stream('invoice-'.$order->order_number.'.pdf');
    }
}
