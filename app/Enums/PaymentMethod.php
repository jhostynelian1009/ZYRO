<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case TestCard = 'test_card';
    case BankTransferDemo = 'bank_transfer_demo';
    case WalletDemo = 'wallet_demo';
}
