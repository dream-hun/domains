<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

final class PaymentController extends Controller
{
    public function index(): Factory|View
    {
        return view('admin.payments.index');

    }

    public function edit(): Factory|View
    {
        return view('admin.payments.edit');
    }
}
