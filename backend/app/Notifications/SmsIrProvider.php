<?php

namespace App\Notifications;

use App\Models\NotificationLog;

class SmsIrProvider
{
    private string $apiKey;

    private string $baseUrl = 'https://api.sms.ir/v1';

    public function __construct()
    {
        $this->apiKey = (string) config('services.smsir.api_key', '');
    }

    /**
     * Send an OTP (verify) message via sms.ir FastSend.
     *
     * @param  array<int, array{name: string, value: string}>  $parameters
     */
    public function sendVerify(string $mobile, int $templateId, array $parameters = []): array
    {
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withHeaders(['x-api-key' => $this->apiKey, 'Accept' => 'application/json'])
            ->post("{$this->baseUrl}/send/verify", [
                'mobile' => $mobile,
                'templateId' => $templateId,
                'parameters' => $parameters,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Send a plain-text bulk SMS.
     *
     * @param  list<string>  $mobiles
     */
    public function sendBulk(array $mobiles, string $message, ?int $lineNumber = null): array
    {
        $payload = [
            'lineNumber' => $lineNumber ?? (int) config('services.smsir.line_number', 30007732),
            'sendDateTime' => null,
            'messages' => array_fill(0, count($mobiles), $message),
            'mobiles' => $mobiles,
        ];

        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->withHeaders(['x-api-key' => $this->apiKey, 'Accept' => 'application/json'])
            ->post("{$this->baseUrl}/send/bulk", $payload);

        return $response->json() ?? [];
    }
}
