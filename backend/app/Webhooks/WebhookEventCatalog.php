<?php

namespace App\Webhooks;

final class WebhookEventCatalog
{
    public const REGISTRATION_CREATED = 'registration.created';
    public const REGISTRATION_CONFIRMED = 'registration.confirmed';
    public const PAYMENT_PAID = 'payment.paid';
    public const PAYMENT_FAILED = 'payment.failed';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::REGISTRATION_CREATED,
            self::REGISTRATION_CONFIRMED,
            self::PAYMENT_PAID,
            self::PAYMENT_FAILED,
        ];
    }
}
