<?php

namespace App\Console\Commands;

use App\Support\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Server-side diagnostic for the Instant Outbound integration.
 *
 *   php artisan sarvam:test-outbound +919182790602
 *   php artisan sarvam:test-outbound +919182790602 --dry-run
 *
 * Deliberately bypasses SarvamVoiceService and talks to the API directly, so
 * a failure here isolates configuration/credentials from application code.
 *
 * The API key is NEVER printed -- only whether it is configured and its last
 * 4 characters.
 */
class SarvamTestOutbound extends Command
{
    protected $signature = 'sarvam:test-outbound
                            {phone : Customer phone number to dial, e.g. +919182790602}
                            {--dry-run : Validate config and print the request without sending it}';

    protected $description = 'Diagnose the Sarvam Voice Agents Instant Outbound integration';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan>Sarvam Instant Outbound diagnostic</>');
        $this->line(str_repeat('-', 64));

        // ---------------------------------------------------------------
        // 1. Load and validate configuration
        // ---------------------------------------------------------------
        $cfg = [
            'api_key'            => config('sarvam.api_key'),
            'base_url'           => config('sarvam.base_url'),
            'org_id'             => config('sarvam.org_id'),
            'workspace_id'       => config('sarvam.workspace_id'),
            'app_id'             => config('sarvam.app_id'),
            'app_version'        => config('sarvam.app_version'),
            'connection_id'      => config('sarvam.connection_id'),
            'agent_phone_number' => config('sarvam.agent_phone_number'),
        ];

        $this->line('<options=bold>1. Configuration</>');

        $key = (string) ($cfg['api_key'] ?? '');
        $this->line(sprintf(
            '   %-22s %s',
            'SARVAM_API_KEY',
            $key !== ''
                ? '<fg=green>configured: yes</> (len ' . strlen($key) . ', ends …' . substr($key, -4) . ')'
                : '<fg=red>configured: no</>'
        ));

        $missing = $key === '' ? ['SARVAM_API_KEY'] : [];

        foreach (['org_id', 'workspace_id', 'app_id', 'app_version', 'connection_id', 'agent_phone_number'] as $k) {
            $value = $cfg[$k];
            $ok    = ! blank($value);

            if (! $ok) {
                $missing[] = 'SARVAM_' . strtoupper($k);
            }

            $this->line(sprintf(
                '   %-22s %s',
                'SARVAM_' . strtoupper($k),
                $ok ? '<fg=green>' . $value . '</>' : '<fg=red>MISSING</>'
            ));
        }

        if ($missing) {
            $this->newLine();
            $this->error('Missing configuration: ' . implode(', ', $missing));

            return self::FAILURE;
        }

        // ---------------------------------------------------------------
        // 2. Normalise the customer number (must differ from the agent number)
        // ---------------------------------------------------------------
        $this->newLine();
        $this->line('<options=bold>2. Phone numbers</>');

        $raw      = (string) $this->argument('phone');
        $customer = PhoneNumber::normalize($raw);

        if ($customer === null) {
            $this->error("   Customer number [{$raw}] could not be normalised to E.164.");

            return self::FAILURE;
        }

        $agent = (string) $cfg['agent_phone_number'];

        $this->line("   customer (dialled)  <fg=green>{$customer}</>  (from \"{$raw}\")");
        $this->line("   agent    (caller)   <fg=green>{$agent}</>");

        if ($customer === $agent) {
            $this->newLine();
            $this->error('   Customer number equals the agent caller number. These must be different.');

            return self::FAILURE;
        }

        // ---------------------------------------------------------------
        // 3. Build URL and payload
        // ---------------------------------------------------------------
        $url = sprintf(
            '%s/api/outbounds/v1/orgs/%s/workspaces/%s/outbounds',
            rtrim((string) $cfg['base_url'], '/'),
            $cfg['org_id'],
            $cfg['workspace_id'],
        );

        $payload = [
            'app_config' => [
                'app_id'      => (string) $cfg['app_id'],
                'app_version' => (int) $cfg['app_version'],   // must be an integer
                'connection_config' => [
                    'connection_id'      => (string) $cfg['connection_id'],
                    'agent_phone_number' => $agent,
                ],
            ],
            'user_config' => [
                'user_phone_number' => $customer,
            ],
        ];

        // Without this the platform has nowhere to post the result, so the call
        // completes but nothing ever comes back. Mirrors SarvamVoiceService.
        $webhookUrl = app(\App\Services\SarvamVoiceService::class)->webhookUrl();

        if ($webhookUrl) {
            $payload['webhook_config'] = [
                'url'      => $webhookUrl,
                'metadata' => ['source' => 'sarvam:test-outbound'],
            ];
        }

        $this->newLine();
        $this->line('<options=bold>3. Request</>');
        $this->line('   POST ' . $url);
        $this->line('   Headers: X-API-Key: <hidden>, Content-Type: application/json');
        $this->line('   Body:');

        // Mask the webhook token before printing -- the payload carries it in
        // full, and this output may land in a terminal scrollback or CI log.
        $printable = $payload;

        if (isset($printable['webhook_config']['url'])) {
            $printable['webhook_config']['url'] = preg_replace(
                '#/[A-Za-z0-9_-]{16,}$#',
                '/<token-hidden>',
                $printable['webhook_config']['url'],
            );
        }

        foreach (explode("\n", json_encode($printable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) as $line) {
            $this->line('     ' . $line);
        }

        $this->line('   app_version type: <fg=green>' . gettype($payload['app_config']['app_version']) . '</>');

        if (isset($payload['webhook_config'])) {
            // Token masked so the secret never reaches the terminal or CI output.
            $this->line('   webhook_config:   <fg=green>' . preg_replace('#/[A-Za-z0-9_-]{16,}$#', '/<token-hidden>', $payload['webhook_config']['url']) . '</>');
        } else {
            $this->warn('   webhook_config:   NOT SET — SARVAM_WEBHOOK_TOKEN is missing, so no result will come back.');
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run — no request sent, no call placed.');

            return self::SUCCESS;
        }

        // ---------------------------------------------------------------
        // 4. Send exactly one request
        // ---------------------------------------------------------------
        $this->newLine();
        $this->line('<options=bold>4. Response</>');

        $started = microtime(true);

        try {
            $response = Http::withHeaders([
                'X-API-Key'    => $key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(30)->connectTimeout(10)->post($url, $payload);
        } catch (ConnectionException $e) {
            $this->error('   Could not reach ' . $cfg['base_url'] . ' — ' . $e->getMessage());

            return self::FAILURE;
        }

        $ms     = (int) round((microtime(true) - $started) * 1000);
        $status = $response->status();
        $colour = $response->successful() ? 'green' : 'red';

        $this->line("   HTTP <fg={$colour};options=bold>{$status}</>  ({$ms}ms)");
        $this->line('   Body:');

        $pretty = json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        foreach (explode("\n", $pretty ?: $response->body()) as $line) {
            $this->line('     ' . $line);
        }

        // ---------------------------------------------------------------
        // 5. Verdict
        // ---------------------------------------------------------------
        $this->newLine();
        $this->line('<options=bold>5. Verdict</>');

        if ($response->successful()) {
            $attemptId = $response->json('attempt_id');

            if ($attemptId) {
                $this->info("   Call initiated. attempt_id = {$attemptId}");
                $this->line('   The phone should ring shortly. The final result arrives by webhook.');

                return self::SUCCESS;
            }

            $this->warn('   HTTP success but no attempt_id in the response.');

            return self::FAILURE;
        }

        $this->line('   ' . match (true) {
            $status === 401 => '<fg=red>401</> Invalid or missing Sarvam API key — the key is not accepted by the Voice Agents API.',
            $status === 402 => '<fg=red>402</> Insufficient credits on the Sarvam account. Top up to resume outbound calls.',
            $status === 403 => '<fg=red>403</> API key does not have access to this organisation/workspace.',
            $status === 404 => '<fg=red>404</> Configured Sarvam resource was not found (check org, workspace, app_id).',
            $status === 422 => '<fg=red>422</> Payload/agent configuration rejected — check app_version, connection_id, caller number, required variables.',
            $status === 429 => '<fg=red>429</> Rate limit or usage limit reached.',
            $status >= 500  => '<fg=red>' . $status . '</> Sarvam service is temporarily unavailable.',
            default         => '<fg=red>' . $status . '</> Unexpected status.',
        });

        if ($detail = $response->json('error.data.details')) {
            $this->line('   Upstream detail: <fg=yellow>' . (is_string($detail) ? $detail : json_encode($detail)) . '</>');
        }

        return self::FAILURE;
    }
}
