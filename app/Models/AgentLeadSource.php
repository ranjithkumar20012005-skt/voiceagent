<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One place an agent's instant leads arrive from.
 *
 * The token is the credential, so it is hidden from serialization and only ever
 * rendered on the agent's own setup page.
 */
class AgentLeadSource extends Model
{
    use BelongsToWorkspace;

    public const KINDS = [
        'website_form' => 'Website form',
        'meta_lead'    => 'Facebook lead ads',
        'instagram'    => 'Instagram lead ads',
        'whatsapp'     => 'WhatsApp enquiry',
        'automation'   => 'Zapier, Make or similar',
        'api'          => 'Direct API',
        'sheet'        => 'Spreadsheet sync',
    ];

    protected $fillable = [
        'workspace_id', 'agent_id', 'name', 'kind', 'token',
        'enabled', 'field_map', 'metadata',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'enabled'      => 'boolean',
            'field_map'    => 'array',
            'metadata'     => 'array',
            'last_lead_at' => 'datetime',
            'lead_count'   => 'integer',
            'rejected_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $source) {
            // Long enough that guessing it is not worth attempting, and it is
            // the only thing standing between the public internet and a call.
            $source->token ??= Str::random(48);
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopeEnabled(Builder $q): Builder
    {
        return $q->where('enabled', true);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? 'Lead source';
    }

    /** The URL a source posts leads to. Shown once, on the setup page. */
    public function endpoint(): string
    {
        return route('leads.intake', ['token' => $this->token]);
    }
}
