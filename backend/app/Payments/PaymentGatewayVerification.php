<?php

namespace App\Payments;

final readonly class PaymentGatewayVerification
{
    /**
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        public bool $paid,
        public ?string $refId = null,
        public array $rawResponse = [],
    ) {
    }
}
