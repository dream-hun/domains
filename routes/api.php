<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DomainSearchController;
use Illuminate\Support\Facades\Route;

Route::post('/domains/search', DomainSearchController::class)->name('api.domains.search');
