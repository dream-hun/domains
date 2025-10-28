<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

final class BillingController
{
    public function index()
    {
        return view('admin.payments.index');
    }
}
