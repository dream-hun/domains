<?php

declare(strict_types=1);

namespace App\Enums;

enum CartItemType: string
{
    case Domain = 'domain';
    case Registration = 'registration';
    case Renewal = 'renewal';
    case SubscriptionRenewal = 'subscription_renewal';
    case Hosting = 'hosting';
    case Transfer = 'transfer';
}
