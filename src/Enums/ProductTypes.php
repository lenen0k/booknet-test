<?php

namespace App\Enums;

enum ProductTypes: string
{
    case BOOK = 'book';
    case REWARD = 'reward';
    case WALLET_REFILL = 'walletRefill';
}
