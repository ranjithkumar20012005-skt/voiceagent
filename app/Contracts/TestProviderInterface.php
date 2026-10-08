<?php

namespace App\Contracts;

use App\Models\Agent;

/** Running the provider's own checks against an agent. */
interface TestProviderInterface
{
    /** @return array{run_id:string,status:string} */
    public function runChecks(Agent $agent): array;

    /** @return array{status:string,results:array<int,array<string,mixed>>} */
    public function getRunResult(Agent $agent, string $runId): array;

    public function isConfigured(): bool;
}
