<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Payment Successful',
            self::FAILED => 'Payment Failed',
            self::REFUNDED => 'Payment Refunded',
        };
    }
}
