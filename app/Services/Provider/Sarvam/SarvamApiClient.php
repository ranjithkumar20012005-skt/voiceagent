<?php

namespace App\Services\Provider\Sarvam;

use App\Services\SarvamException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal client for the platform services the original SarvamVoiceService does
 * not cover: deployments (app-authoring) and checks (evals).
 *
 * A separate class on purpose -- SarvamVoiceService is working, in production
 * and covered by the webhook path, so it is left untouched. The credential
 * handling is the same shape: the key is read from config, sent as a header, and
 * never logged or returned.
 */
class SarvamApiClient
{
    public function __construct(private readonly array $config)
    {
    }

    public static function make(): self
    {
        return new self(config('sarvam'));
    }

    public function isConfigured(): bool
    {
        foreach (['api_key', 'org_id', 'workspace_id'] as $key) {
            if (blank($this->config[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public function orgId(): string
    {
        return (string) ($this->config['org_id'] ?? '');
    }

    public function workspaceId(): string
    {
        return (string) ($this->config['workspace_id'] ?? '');
    }

    /** @return array<string,mixed> */
    public function get(string $path, string $operation): array
    {
        return $this->request('get', $path, null, $operation);
    }

    /** @return array<string,mixed> */
    public function post(string $path, array $payload, string $operation): array
    {
        return $this->request('post', $path, $payload, $operation);
    }

    /** @return array<string,mixed> */
    public function patch(string $path, array $payload, string $operation): array
    {
        return $this->request('patch', $path, $payload, $operation);
    }

    private function client(): PendingRequest
    {
        $http = $this->config['http'] ?? [];

        return Http::baseUrl((string) ($this->config['base_url'] ?? ''))
            ->withHeaders(['X-API-Key' => (string) ($this->config['api_key'] ?? '')])
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) ($http['connect_timeout'] ?? 10))
            ->timeout((int) ($http['timeout'] ?? 30))
            // Only idempotent failures are retried, and never a 4xx: repeating a
            // rejected request cannot start succeeding.
            ->retry(
                (int) ($http['retry_times'] ?? 3),
                (int) ($http['retry_sleep_ms'] ?? 500),
                fn ($exception, $request) => $this->shouldRetry($exception),
                throw: false,
            );
    }

    private function shouldRetry(mixed $exception): bool
    {
        $status = method_exists($exception, 'response') ? $exception->response?->status() : null;

        if ($status === null) {
            return true; // connection/timeout
        }

        return $status === 429 || $status >= 500;
    }

    /** @return array<string,mixed> */
    private function request(string $method, string $path, ?array $payload, string $operation): array
    {
        if (! $this->isConfigured()) {
            throw new SarvamException('The voice platform is not configured.');
        }

        try {
            /** @var Response $response */
            $response = $payload === null
                ? $this->client()->{$method}($path)
                : $this->client()->{$method}($path, $payload);
        } catch (\Throwable $e) {
            // The message may mention the host but never the key: the key is a
            // header, and headers are not part of the exception message.
            Log::error('voice.provider.transport_error', [
                'provider'  => 'sarvam',
                'action'    => $operation,
                'message'   => $e->getMessage(),
            ]);

            // The third argument is a context array, not the previous exception;
            // passing the throwable there crashed with a TypeError instead of
            // surfacing a clean provider error.
            throw new SarvamException(
                'Voice service is temporarily unavailable.',
                null,
                ['action' => $operation],
                $e,
            );
        }

        if ($response->failed()) {
            Log::error('voice.provider.error', [
                'provider'            => 'sarvam',
                'action'              => $operation,
                'status_code'         => $response->status(),
                'provider_request_id' => $response->header('x-request-id') ?: null,
            ]);

            throw new SarvamException("Voice platform rejected {$operation} ({$response->status()}).");
        }

        return is_array($response->json()) ? $response->json() : [];
    }
}
