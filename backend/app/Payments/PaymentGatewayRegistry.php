<?php

namespace App\Payments;

use InvalidArgumentException;

class PaymentGatewayRegistry
{
    /**
     * @var array<string, PaymentGateway>
     */
    private array $gateways = [];

    /**
     * @param iterable<PaymentGateway> $gateways
     */
    public function __construct(iterable $gateways = [])
    {
        foreach ($gateways as $gateway) {
            $this->register($gateway);
        }
    }

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->key()] = $gateway;
    }

    public function get(string $key): PaymentGateway
    {
        return $this->gateways[$key] ?? throw new InvalidArgumentException("Payment gateway [{$key}] is not registered.");
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->gateways);
    }
}
