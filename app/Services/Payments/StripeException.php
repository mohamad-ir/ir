<?php

namespace App\Services\Payments;

use RuntimeException;

class StripeException extends RuntimeException
{
    public function __construct(string $message, int $status = 0, public ?string $stripeCode = null, public ?string $stripeType = null)
    {
        parent::__construct($message, $status);
    }
}
