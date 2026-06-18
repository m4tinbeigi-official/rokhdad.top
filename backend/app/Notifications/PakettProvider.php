<?php

namespace App\Notifications;

class PakettProvider
{
    private string $apiKey;

    private string $baseUrl = 'https://app.pakett.ir/api/v1';

    private string $fromEmail;

    private string $fromName;

    public function __construct()
    {
        $this->apiKey = (string) config('services.pakett.api_key', '');
        $this->fromEmail = (string) config('services.pakett.from_email', config('mail.from.address', 'noreply@rokhdad.top'));
        $this->fromName = (string) config('services.pakett.from_name', config('mail.from.name', 'رخداد'));
    }

    /**
     * Send a transactional email via Pakett.
     *
     * @param  array<string, string>  $variables  Template variable substitutions.
     */
    public function sendTransactional(
        string $toEmail,
        string $toName,
        string $subject,
        string $templateId,
        array $variables = [],
    ): array {
        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}", 'Accept' => 'application/json'])
            ->post("{$this->baseUrl}/send/template", [
                'from' => ['email' => $this->fromEmail, 'name' => $this->fromName],
                'to' => [['email' => $toEmail, 'name' => $toName]],
                'subject' => $subject,
                'template_id' => $templateId,
                'variables' => $variables,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Send a plain HTML email via Pakett.
     */
    public function sendHtml(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
    ): array {
        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}", 'Accept' => 'application/json'])
            ->post("{$this->baseUrl}/send", [
                'from' => ['email' => $this->fromEmail, 'name' => $this->fromName],
                'to' => [['email' => $toEmail, 'name' => $toName]],
                'subject' => $subject,
                'html' => $html,
            ]);

        return $response->json() ?? [];
    }
}
