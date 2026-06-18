<?php

namespace App\Notifications;

use App\Models\NotificationLog;

class NotificationService
{
    public function __construct(
        private SmsIrProvider $sms,
        private PakettProvider $email,
    ) {}

    /**
     * Send an OTP code via sms.ir and log the result.
     *
     * @param  array<int, array{name: string, value: string}>  $parameters
     */
    public function sendOtp(
        string $mobile,
        int $templateId,
        array $parameters = [],
        ?int $userId = null,
    ): NotificationLog {
        $log = NotificationLog::query()->create([
            'user_id' => $userId,
            'channel' => 'sms',
            'provider' => 'sms.ir',
            'recipient' => $mobile,
            'type' => 'otp',
            'status' => 'queued',
        ]);

        try {
            $response = $this->sms->sendVerify($mobile, $templateId, $parameters);
            $success = ($response['status'] ?? null) === 1;

            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'provider_response' => $response,
                'sent_at' => $success ? now() : null,
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'provider_response' => ['error' => $e->getMessage()]]);
        }

        return $log;
    }

    /**
     * Send a bulk SMS and log the result.
     *
     * @param  list<string>  $mobiles
     */
    public function sendSms(
        array $mobiles,
        string $message,
        string $type = 'general',
        ?int $userId = null,
    ): NotificationLog {
        $log = NotificationLog::query()->create([
            'user_id' => $userId,
            'channel' => 'sms',
            'provider' => 'sms.ir',
            'recipient' => implode(',', $mobiles),
            'type' => $type,
            'message' => $message,
            'status' => 'queued',
        ]);

        try {
            $response = $this->sms->sendBulk($mobiles, $message);
            $success = ($response['status'] ?? null) === 1;

            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'provider_response' => $response,
                'sent_at' => $success ? now() : null,
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'provider_response' => ['error' => $e->getMessage()]]);
        }

        return $log;
    }

    /**
     * Send a transactional email via Pakett and log the result.
     *
     * @param  array<string, string>  $variables
     */
    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $templateId,
        array $variables = [],
        string $type = 'general',
        ?int $userId = null,
    ): NotificationLog {
        $log = NotificationLog::query()->create([
            'user_id' => $userId,
            'channel' => 'email',
            'provider' => 'pakett',
            'recipient' => $toEmail,
            'type' => $type,
            'message' => $subject,
            'status' => 'queued',
        ]);

        try {
            $response = $this->email->sendTransactional($toEmail, $toName, $subject, $templateId, $variables);
            $success = isset($response['id']) || ($response['status'] ?? null) === 'sent';

            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'provider_message_id' => $response['id'] ?? null,
                'provider_response' => $response,
                'sent_at' => $success ? now() : null,
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'provider_response' => ['error' => $e->getMessage()]]);
        }

        return $log;
    }
}
