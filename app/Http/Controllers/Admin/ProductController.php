<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Subscription;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function domains()
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $domains = Domain::all();

        return view('admin.products.domains', ['domains' => $domains]);
    }

    public function hosting()
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $subscriptions = Subscription::with(['user', 'plan', 'planPrice'])->get();

        return view('admin.products.hosting', ['subscriptions' => $subscriptions]);
    }
}
