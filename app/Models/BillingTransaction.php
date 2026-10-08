<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of the workspace ledger. Append-only: corrections are a new
 * adjustment row, never an edit, so the balance history stays auditable.
 */
class BillingTransaction extends Model
{
    use BelongsToWorkspace;

    public const DEBIT      = 'debit';
    public const CREDIT     = 'credit';
    public const ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'workspace_id', 'type', 'description', 'reference_type', 'reference_id',
        'minutes', 'amount', 'balance_after', 'currency', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'minutes'       => 'decimal:2',
            'amount'        => 'decimal:4',
            'balance_after' => 'decimal:4',
            'metadata'      => 'array',
        ];
    }

    public function isDebit(): bool
    {
        return $this->type === self::DEBIT;
    }
}
