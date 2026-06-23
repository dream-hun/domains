<?php

declare(strict_types=1);

namespace Darryldecode\Cart\Facades;

use Illuminate\Support\Facades\Facade;

class CartFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cart';
    }
}
