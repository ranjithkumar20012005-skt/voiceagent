<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A master agent we maintain on the voice platform.
 *
 * Admin-managed and shared by every workspace: customers choose one by name and
 * never see the provider identifiers it carries.
 */
class AgentTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'description', 'provider',
        'provider_agent_id', 'provider_agent_version', 'provider_metadata', 'supported_languages',
        'default_variables', 'supported_calling_modes', 'status', 'sort_order',
    ];

    protected $hidden = ['provider_agent_id', 'provider_agent_version'];

    protected function casts(): array
    {
        return [
            'supported_languages'     => 'array',
            'default_variables'       => 'array',
            'supported_calling_modes' => 'array',
            'provider_agent_version'  => 'integer',
            'provider_metadata'       => 'array',
        ];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /** True once an admin has pointed this template at a real provider agent. */
    public function isProvisioned(): bool
    {
        return filled($this->provider_agent_id) && $this->provider_agent_version !== null;
    }

    public function supports(string $callingMode): bool
    {
        $modes = $this->supported_calling_modes;

        return empty($modes) || in_array($callingMode, $modes, true);
    }
}
