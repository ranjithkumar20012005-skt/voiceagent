<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The only place in the application that talks to Sarvam.
 *
 * Nothing here is ever reachable from the browser: controllers call this
 * server-side, and the API key never leaves this class.
 *
 * Endpoints follow the official Sarvam Voice Agents API:
 *   POST /api/outbounds/v1/orgs/{org}/workspaces/{ws}/outbounds
 *   POST /api/scheduling/v1/orgs/{org}/workspaces/{ws}/campaigns
 *   POST /api/scheduling/v1/orgs/{org}/workspaces/{ws}/campaigns/{id}/cohorts/stream
 *   GET  /api/scheduling/v1/orgs/{org}/workspaces/{ws}/campaigns/{id}
 *   PUT  /api/scheduling/v1/orgs/{org}/workspaces/{ws}/campaigns/{id}/status
 */
class SarvamVoiceService
{
    /** Hard API limit for a single cohort stream request. */
    public const COHORT_CHUNK_LIMIT = 1000;

    public function __construct(private readonly array $config)
    {
    }

    public static function make(): self
    {
        return new self(config('sarvam'));
    }

    // =================================================================
    // Configuration
    // =================================================================

    /**
     * True when every value needed to place a call is present. The UI uses
     * this to show "Not configured" instead of pretending the agent is live.
     */
    public function isConfigured(): bool
    {
        foreach (['api_key', 'org_id', 'workspace_id', 'app_id', 'app_version', 'connection_id', 'agent_phone_number'] as $k) {
            if (blank($this->config[$k] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> Names of missing settings -- never their values. */
    public function missingConfigKeys(): array
    {
        $missing = [];

        foreach (['api_key', 'org_id', 'workspace_id', 'app_id', 'app_version', 'connection_id', 'agent_phone_number', 'webhook_token'] as $k) {
            if (blank($this->config[$k] ?? null)) {
                $missing[] = 'SARVAM_' . strtoupper($k);
            }
        }

        return $missing;
    }

    /** Public, non-secret summary for the Settings screen. */
    public function publicStatus(): array
    {
        return [
            'configured'         => $this->isConfigured(),
            'agent_name'         => $this->config['app_id'] ?: null,
            'agent_version'      => $this->config['app_version'] ?: null,
            'caller_number'      => $this->config['agent_phone_number'] ?: null,
            'timezone'           => config('app.timezone'),
            'default_language'   => $this->config['default_language'] ?? null,
            'webhook_configured' => filled($this->config['webhook_token'] ?? null),
        ];
    }

    /** The URL Sarvam should post call results back to. */
    public function webhookUrl(): ?string
    {
        $token = $this->config['webhook_token'] ?? null;

        if (blank($token)) {
            return null;
        }

        $base = $this->config['webhook_url'] ?: config('app.url');

        return rtrim((string) $base, '/') . '/api/webhooks/sarvam/' . $token;
    }

    // =================================================================
    // Instant outbound -- the "Call Now" path
    // =================================================================

    /**
     * Place a single immediate outbound call.
     *
     * @param  array<string,string>  $agentVariables
     * @param  array{app_id:string,app_version:int}|null  $app  Another agent in the same
     *         workspace; null keeps the agent configured in the environment.
     * @return array{attempt_id:string, raw:array}
     *
     * @throws SarvamException
     */
    public function createInstantCall(
        string $phoneNumber,
        array $agentVariables = [],
        ?string $language = null,
        array $webhookMetadata = [],
        ?array $app = null,
    ): array {
        $this->assertConfigured();

        $payload = [
            'app_config' => array_filter([
                'app_id'      => (string) ($app['app_id'] ?? $this->config['app_id']),
                'app_version' => (int) ($app['app_version'] ?? $this->config['app_version']),
                'app_type'    => $this->config['app_type'] ?? 'agent',
                'connection_config' => [
                    'connection_id'      => (string) $this->config['connection_id'],
                    'agent_phone_number' => (string) $this->config['agent_phone_number'],
                ],
                'agent_variables' => $agentVariables ?: null,
                'app_overrides'   => $language ? ['initial_language_name' => $language] : null,
            ], fn ($v) => $v !== null),

            'user_config' => [
                'user_phone_number' => $phoneNumber,
            ],
        ];

        if ($url = $this->webhookUrl()) {
            $payload['webhook_config'] = array_filter([
                'url'      => $url,
                'metadata' => $webhookMetadata ?: null,
            ], fn ($v) => $v !== null);
        }

        $data = $this->request(
            'POST',
            "/api/outbounds/v1/orgs/{$this->config['org_id']}/workspaces/{$this->config['workspace_id']}/outbounds",
            $payload,
            'instant_outbound.create',
        );

        $attemptId = $data['attempt_id'] ?? null;

        if (! is_string($attemptId) || $attemptId === '') {
            throw new SarvamException('Voice service did not return an attempt_id.', null, ['keys' => array_keys($data)]);
        }

        return ['attempt_id' => $attemptId, 'raw' => $data];
    }

    // =================================================================
    // Campaigns
    // =================================================================

    /**
     * @param  array{max_retries?:int,retry_interval_minutes?:int,retry_on_busy?:bool,retry_on_no_answer?:bool,retry_on_failed?:bool}  $retry
     * @return array{campaign_id:string, status:string, raw:array}
     *
     * @throws SarvamException
     */
    public function createCampaign(
        string $name,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        ?float $attemptsPerSecond = null,
        ?array $window = null,
        ?array $retry = null,
        ?string $description = null,
        array $webhookMetadata = [],
    ): array {
        $this->assertConfigured();

        $window ??= $this->config['campaign']['window'];
        $retry  = array_merge($this->config['campaign']['retry'], $retry ?? []);
        $tz     = config('app.timezone', 'Asia/Kolkata');

        $payload = array_filter([
            'name' => Str::limit($name, 50, ''),
            'app_config' => [
                'app_id'              => (string) $this->config['app_id'],
                'app_type'            => $this->config['app_type'] ?? 'agent',
                'app_version'         => (int) $this->config['app_version'],
                'attempts_per_second' => $this->clampRate($attemptsPerSecond),
                'connection_configs'  => [[
                    'connection_id'      => (string) $this->config['connection_id'],
                    'agent_phone_number' => (string) $this->config['agent_phone_number'],
                ]],
                'retry_config' => [
                    'max_retries'            => max(0, min(20, (int) $retry['max_retries'])),
                    'retry_interval_minutes' => max(5, (int) $retry['retry_interval_minutes']),
                    'retry_on' => [
                        'busy'      => ['enabled' => (bool) $retry['retry_on_busy']],
                        'no_answer' => ['enabled' => (bool) $retry['retry_on_no_answer']],
                        'failed'    => ['enabled' => (bool) $retry['retry_on_failed']],
                    ],
                ],
            ],
            'start_timestamp' => $startsAt->format(\DateTimeInterface::ATOM),
            'end_timestamp'   => $endsAt->format(\DateTimeInterface::ATOM),
            'allowed_schedule' => [
                'allowed_start_time' => $window['start'],
                'allowed_end_time'   => $window['end'],
                'allowed_days'       => array_values($window['days']),
                'timezone'           => $tz,
            ],
            'description' => $description ? Str::limit($description, 150, '') : null,
        ], fn ($v) => $v !== null);

        if ($url = $this->webhookUrl()) {
            $payload['webhook_config'] = array_filter([
                'url'      => $url,
                'metadata' => $webhookMetadata ?: null,
            ], fn ($v) => $v !== null);
        }

        $data = $this->request('POST', $this->campaignPath(), $payload, 'campaign.create');

        $id = $data['campaign_id'] ?? null;

        if (! is_string($id) || $id === '') {
            throw new SarvamException('Voice service did not return a campaign_id.', null, ['keys' => array_keys($data)]);
        }

        return ['campaign_id' => $id, 'status' => (string) ($data['status'] ?? 'scheduled'), 'raw' => $data];
    }

    /**
     * Push up to 1000 contacts into a campaign as one cohort.
     *
     * @param  list<array{user_phone_number:string,user_identifier?:string,app_variables?:array,app_overrides?:array}>  $users
     *
     * @throws SarvamException
     */
    public function streamCohort(string $campaignId, string $cohortName, array $users): array
    {
        $this->assertConfigured();

        $count = count($users);

        if ($count < 1) {
            throw new SarvamException('Cannot create an empty cohort.');
        }

        if ($count > self::COHORT_CHUNK_LIMIT) {
            throw new SarvamException(
                sprintf('Cohort of %d exceeds the %d-contact limit; chunk before calling.', $count, self::COHORT_CHUNK_LIMIT)
            );
        }

        $data = $this->request(
            'POST',
            $this->campaignPath($campaignId) . '/cohorts/stream',
            [
                'name'  => $this->sanitiseCohortName($cohortName),
                'users' => array_values($users),
            ],
            'cohort.stream',
        );

        return [
            'cohort_id' => $data['cohort_id'] ?? null,
            'status'    => $data['status'] ?? null,
            'result'    => $data['result'] ?? [],
            'raw'       => $data,
        ];
    }

    /** @throws SarvamException */
    public function getCampaign(string $campaignId): array
    {
        $this->assertConfigured();

        return $this->request('GET', $this->campaignPath($campaignId), null, 'campaign.get');
    }

    /** @throws SarvamException */
    public function getCohort(string $campaignId, string $cohortId): array
    {
        $this->assertConfigured();

        return $this->request('GET', $this->campaignPath($campaignId) . "/cohorts/{$cohortId}", null, 'cohort.get');
    }

    /**
     * @param  'pause'|'resume'|'cancel'  $action
     *
     * @throws SarvamException
     */
    public function updateCampaignStatus(string $campaignId, string $action): array
    {
        $this->assertConfigured();

        if (! in_array($action, ['pause', 'resume', 'cancel'], true)) {
            throw new SarvamException("Unsupported campaign action [{$action}].");
        }

        return $this->request('PUT', $this->campaignPath($campaignId) . '/status', ['action' => $action], 'campaign.status');
    }

    // =================================================================
    // Internals
    // =================================================================

    private function campaignPath(?string $campaignId = null): string
    {
        $base = "/api/scheduling/v1/orgs/{$this->config['org_id']}/workspaces/{$this->config['workspace_id']}/campaigns";

        return $campaignId ? "{$base}/{$campaignId}" : $base;
    }

    private function clampRate(?float $rate): float
    {
        $rate ??= (float) ($this->config['campaign']['attempts_per_second'] ?? 1.0);

        return round(max(0.1, min(500.0, $rate)), 2);
    }

    /** Sarvam allows 1-50 chars: letters, numbers, spaces, underscores, hyphens. */
    private function sanitiseCohortName(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 _-]/', '', $name) ?: 'cohort';

        return Str::limit(trim($clean), 50, '') ?: 'cohort';
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new SarvamException(
                'Voice service is not configured. Missing: ' . implode(', ', $this->missingConfigKeys())
            );
        }
    }

    private function client(): PendingRequest
    {
        $http = $this->config['http'];

        return Http::baseUrl($this->config['base_url'])
            ->withHeaders([
                // The Voice Agents API on apps.sarvam.ai authenticates with
                // X-API-Key. (`api-subscription-key` is the *model* API on
                // api.sarvam.ai and is rejected here with
                // "header.X-API-Key: Field required".)
                // Never logged, never sent to the browser.
                'X-API-Key'    => (string) $this->config['api_key'],
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->connectTimeout((int) $http['connect_timeout'])
            ->timeout((int) $http['timeout'])
            // Only retry genuinely transient failures -- never a 4xx, which
            // would just re-submit a rejected call.
            ->retry(
                max(1, (int) $http['retry_times']),
                max(0, (int) $http['retry_sleep_ms']),
                function (\Throwable $e) {
                    if ($e instanceof ConnectionException) {
                        return true;
                    }

                    $status = method_exists($e, 'response') && $e->response ? $e->response->status() : null;

                    return in_array($status, [429, 500, 502, 503, 504], true);
                },
                throw: false,
            );
    }

    /**
     * Perform a request and normalise the response into an array.
     *
     * @throws SarvamException
     */
    private function request(string $method, string $path, ?array $payload, string $operation): array
    {
        $started = microtime(true);

        try {
            $response = $this->client()->send($method, $path, $payload !== null ? ['json' => $payload] : []);
        } catch (ConnectionException $e) {
            $this->log($operation, $method, $path, null, $started, 'connection_error');

            throw new SarvamException("Could not reach the voice service [{$operation}].", null, [], $e);
        }

        $this->log(
            $operation,
            $method,
            $path,
            $response->status(),
            $started,
            // On failure, keep the upstream body so the cause is diagnosable
            // from the log alone. It contains no secret -- the key is only ever
            // sent in a request header, never echoed back.
            error: null,
            body: $response->failed() ? $response->body() : null,
        );

        if ($response->failed()) {
            throw new SarvamException(
                $this->extractError($response),
                $response->status(),
                ['operation' => $operation],
            );
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /** Pull a human-readable message out of Sarvam's two error shapes. */
    private function extractError(Response $response): string
    {
        $body = $response->json();

        if (is_array($body)) {
            if (isset($body['error']['message']) && is_string($body['error']['message'])) {
                $message = $body['error']['message'];

                // The platform puts the actionable part in error.data.details
                // (e.g. "header.X-API-Key: Field required"), while `message`
                // is only a generic "(422) Invalid Parameter". Keep both.
                $details = $body['error']['data']['details'] ?? null;

                if (is_string($details) && $details !== '') {
                    $message .= ' -- ' . $details;
                } elseif (is_array($details)) {
                    $message .= ' -- ' . json_encode($details);
                }

                return $message;
            }

            // FastAPI-style 422: {"detail":[{"loc":[...],"msg":"...","type":"..."}]}
            if (isset($body['detail'])) {
                if (is_string($body['detail'])) {
                    return $body['detail'];
                }

                if (is_array($body['detail'])) {
                    $msgs = [];

                    foreach ($body['detail'] as $d) {
                        if (is_array($d) && isset($d['msg'])) {
                            $field  = isset($d['loc']) && is_array($d['loc']) ? implode('.', array_map('strval', $d['loc'])) : '';
                            $msgs[] = trim($field . ' ' . $d['msg']);
                        }
                    }

                    if ($msgs) {
                        return implode('; ', array_slice($msgs, 0, 5));
                    }
                }
            }

            if (isset($body['message']) && is_string($body['message'])) {
                return $body['message'];
            }
        }

        return 'Voice service returned HTTP ' . $response->status() . '.';
    }

    /**
     * Structured diagnostics. Logs the operation, path shape, status and
     * duration -- deliberately never the payload, the phone number or the key.
     */
    private function log(
        string $operation,
        string $method,
        string $path,
        ?int $status,
        float $started,
        ?string $error = null,
        ?string $body = null,
    ): void {
        $context = array_filter([
            'operation' => $operation,
            'method'    => $method,
            // Strip org/workspace/campaign IDs so logs stay free of tenant identifiers.
            'path'      => preg_replace('#/(orgs|workspaces|campaigns|cohorts)/[^/]+#', '/$1/{id}', $path),
            'status'    => $status,
            'ms'        => (int) round((microtime(true) - $started) * 1000),
            'error'     => $error,
            // Upstream error body, truncated. Never contains the API key.
            'response'  => $body !== null ? Str::limit($body, 1000) : null,
        ], fn ($v) => $v !== null);

        $channel = Log::channel(config('logging.default'));

        $body !== null || $error !== null
            ? $channel->warning('sarvam.api', $context)
            : $channel->info('sarvam.api', $context);
    }
}
