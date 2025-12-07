<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Subscription;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function domains(): Factory|View
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $domains = Domain::with('owner')->get();

        return view('admin.products.domains', ['domains' => $domains]);
    }

    public function hosting(): Factory|View
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $subscriptions = Subscription::with(['user', 'plan', 'planPrice'])
            ->where('user_id', auth()->user()->id)->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.hosting', ['subscriptions' => $subscriptions]);
    }
}
