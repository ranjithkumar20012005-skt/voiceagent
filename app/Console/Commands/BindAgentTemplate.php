<?php

namespace App\Console\Commands;

use App\Models\AgentTemplate;
use Illuminate\Console\Command;

/**
 * Records the published master agent behind a template.
 *
 * This is the one provider step that cannot be automated: the voice platform has
 * no agent-authoring API, so an administrator builds and publishes each master
 * agent in the platform dashboard and then runs this command once per template.
 * Customers never see any of these values.
 */
class BindAgentTemplate extends Command
{
    protected $signature = 'voice:template-bind
        {slug? : The agent template slug, e.g. lead-qualification (omit with --show)}
        {--agent-id= : The published agent id from the voice platform}
        {--agent-version= : The published agent version number}
        {--test-suite= : Optional published test-suite id, enabling "Run checks"}
        {--show : Print the current bindings and exit}';

    protected $description = 'Bind an agent template to a published master agent on the voice platform';

    public function handle(): int
    {
        if ($this->option('show')) {
            return $this->showBindings();
        }

        if (blank($this->argument('slug'))) {
            $this->error('Pass a template slug, or --show to list the current bindings.');

            return self::FAILURE;
        }

        $template = AgentTemplate::where('slug', $this->argument('slug'))->first();

        if (! $template) {
            $this->error("No agent template with slug [{$this->argument('slug')}].");
            $this->line('Known slugs: ' . AgentTemplate::pluck('slug')->implode(', '));

            return self::FAILURE;
        }

        $agentId = $this->option('agent-id');
        $version = $this->option('agent-version');

        if (blank($agentId) || blank($version)) {
            $this->error('Both --agent-id and --agent-version are required.');

            return self::FAILURE;
        }

        if (! ctype_digit((string) $version) || (int) $version < 1) {
            $this->error('--agent-version must be a positive whole number.');

            return self::FAILURE;
        }

        $metadata = $template->provider_metadata ?? [];

        if ($suite = $this->option('test-suite')) {
            $metadata['test_suite_id'] = (string) $suite;
        }

        $template->forceFill([
            'provider_agent_id'      => (string) $agentId,
            'provider_agent_version' => (int) $version,
            'provider_metadata'      => $metadata,
        ])->save();

        $this->info("Bound [{$template->slug}] to the published master agent (version {$version}).");

        if (! isset($metadata['test_suite_id'])) {
            $this->line('No test suite bound, so "Run checks" stays unavailable for this type.');
        }

        return self::SUCCESS;
    }

    private function showBindings(): int
    {
        $rows = AgentTemplate::orderBy('sort_order')->get()->map(fn (AgentTemplate $t) => [
            $t->slug,
            $t->name,
            $t->isProvisioned() ? 'bound' : 'NOT BOUND',
            $t->isProvisioned() ? 'v' . $t->provider_agent_version : '-',
            isset($t->provider_metadata['test_suite_id']) ? 'yes' : 'no',
        ]);

        $this->table(['Slug', 'Name', 'Status', 'Version', 'Checks'], $rows);

        return self::SUCCESS;
    }
}
