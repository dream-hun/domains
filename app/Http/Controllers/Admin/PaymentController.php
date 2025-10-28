<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

final class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.payments.index');

    }

    public function edit()
    {
        return view('admin.payments.edit');
    }
}
