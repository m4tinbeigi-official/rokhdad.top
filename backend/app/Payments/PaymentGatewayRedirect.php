<?php

namespace App\Payments;

final readonly class PaymentGatewayRedirect
{
    /**
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        public string $authority,
        public string $redirectUrl,
        public array $rawResponse = [],
    ) {
    }
}
