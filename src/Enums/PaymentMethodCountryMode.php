<?php

namespace App\Enums;

enum PaymentMethodCountryMode: string
{
    case DENY = 'deny';
    case ALLOW = 'allow';
}
