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

        $orders = Order::with(['orderItems', 'user'])
            ->when(! $user->isAdmin(), function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.billing.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $user = Auth::user();

        // Check authorization: either admin or order owner
        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }

        $order->load(['orderItems', 'user']);

        return view('admin.billing.show', compact('order'));
    }

    public function invoice(Order $order): View
    {
        $user = Auth::user();

        // Check authorization: either admin or order owner
        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }

        $order->load(['orderItems', 'user']);

        return view('admin.billing.invoice', compact('order'));
    }

    public function downloadInvoice(Order $order): Response
    {
        $user = Auth::user();

        // Check authorization: either admin or order owner
        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }

        $order->load(['orderItems', 'user']);

        $pdf = Pdf::loadView('admin.billing.invoice-pdf', compact('order'));

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }

    public function viewInvoicePdf(Order $order): Response
    {
        $user = Auth::user();

        // Check authorization: either admin or order owner
        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }

        $order->load(['orderItems', 'user']);

        $pdf = Pdf::loadView('admin.billing.invoice-pdf', compact('order'));

        return $pdf->stream('invoice-'.$order->order_number.'.pdf');
    }
}
