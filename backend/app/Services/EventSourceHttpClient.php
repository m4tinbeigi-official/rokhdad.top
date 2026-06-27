<?php

namespace App\Services;

use App\Models\EventSource;
use App\Models\RequestLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * سرویس HTTP برای منابع رویداد
 *
 * ویژگی‌ها:
 * - ثبت تمام درخواست‌ها در جدول request_logs
 * - تشخیص خودکار مسدود شدن (403/429/503)
 * - retry خودکار از طریق proxy_url منبع
 * - ثبت زمان پاسخ (duration_ms)
 */
class EventSourceHttpClient
{
    protected EventSource $source;

    public function __construct(EventSource $source)
    {
        $this->source = $source;
    }

    /**
     * ارسال درخواست GET
     */
    public function get(string $url, array $query = []): Response
    {
        return $this->request('GET', $url, $query);
    }

    /**
     * ارسال درخواست POST
     */
    public function post(string $url, array $data = []): Response
    {
        return $this->request('POST', $url, $data);
    }

    /**
     * ارسال درخواست با لاگ‌گیری و پشتیبانی پروکسی
     */
    protected function request(string $method, string $url, array $params = []): Response
    {
        $startMs   = (int) (microtime(true) * 1000);
        $proxyUrl  = $this->source->proxy_url;
        $usedProxy = false;

        try {
            // ارسال درخواست اولیه بدون پروکسی
            $response = $this->send($method, $url, $params);

            // اگر مسدود شده و پروکسی وجود دارد → retry با پروکسی
            if ($this->isBlocked($response) && $proxyUrl) {
                Log::warning('EventSourceHttpClient: blocked, retrying via proxy', [
                    'source' => $this->source->source_key,
                    'url'    => $url,
                    'status' => $response->status(),
                ]);
                $response  = $this->send($method, $url, $params, $proxyUrl);
                $usedProxy = true;
            }

            $this->logRequest(
                url: $url,
                method: $method,
                status: $response->status(),
                body: $response->body(),
                error: null,
                usedProxy: $usedProxy,
                proxy: $usedProxy ? $proxyUrl : null,
                durationMs: (int) (microtime(true) * 1000) - $startMs,
            );

            return $response;

        } catch (\Exception $e) {
            $durationMs = (int) (microtime(true) * 1000) - $startMs;

            // اگر خطا داشت و هنوز پروکسی امتحان نشده
            if ($proxyUrl && !$usedProxy) {
                try {
                    $response = $this->send($method, $url, $params, $proxyUrl);
                    $this->logRequest($url, $method, $response->status(), $response->body(), null, true, $proxyUrl, $durationMs);
                    return $response;
                } catch (\Exception $proxyEx) {
                    $this->logRequest($url, $method, null, null, $proxyEx->getMessage(), true, $proxyUrl, $durationMs);
                    throw $proxyEx;
                }
            }

            $this->logRequest($url, $method, null, null, $e->getMessage(), false, null, $durationMs);
            throw $e;
        }
    }

    /**
     * اجرای واقعی درخواست HTTP
     */
    protected function send(string $method, string $url, array $params = [], ?string $proxy = null): Response
    {
        $client = Http::timeout(30)->retry(2, 500);

        if ($proxy) {
            $client = $client->withOptions(['proxy' => $proxy]);
        }

        return match (strtoupper($method)) {
            'POST'  => $client->post($url, $params),
            default => $client->get($url, $params),
        };
    }

    /**
     * آیا پاسخ مسدود شده؟
     */
    protected function isBlocked(Response $response): bool
    {
        return in_array($response->status(), [403, 429, 503]);
    }

    /**
     * ذخیره لاگ در دیتابیس
     */
    protected function logRequest(
        string  $url,
        string  $method,
        ?int    $status,
        ?string $body,
        ?string $error,
        bool    $usedProxy,
        ?string $proxy,
        int     $durationMs,
    ): void {
        try {
            RequestLog::create([
                'event_source_id' => $this->source->id,
                'url'             => $url,
                'method'          => strtoupper($method),
                'status_code'     => $status,
                'response_body'   => $body ? mb_substr($body, 0, 2000) : null,
                'error_message'   => $error,
                'used_proxy'      => $usedProxy,
                'proxy_url'       => $proxy,
                'duration_ms'     => $durationMs,
            ]);
        } catch (\Exception $e) {
            Log::error('EventSourceHttpClient: log write failed', ['error' => $e->getMessage()]);
        }
    }
}
