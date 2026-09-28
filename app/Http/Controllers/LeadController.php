<?php

namespace App\Http\Controllers;

use App\Models\CallAttempt;
use App\Services\DashboardMetrics;
use App\Support\CallStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Leads -- the calls the agent's own output marked as worth following up.
 *
 * Reads the same query the Overview rail uses, so "Hot Lead" means exactly one
 * thing across the product.
 */
class LeadController extends Controller
{
    public function __construct(private readonly DashboardMetrics $metrics)
    {
    }

    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'hot');

        $query = match ($filter) {
            'follow_up' => CallAttempt::with('customer')->where('call_disposition', CallStatus::CALLBACK)->latest('id'),
            'qualified' => CallAttempt::with('customer')->where('lead_generated', true)->latest('id'),
            default     => $this->metrics->hotLeadQuery(),
        };

        if ($search = $request->query('q')) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('customer_phone_number', 'like', $like)
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)
                      ->orWhere('policy_number', 'like', $like));
            });
        }

        return view('leads.index', [
            'leads'  => $query->paginate(20)->withQueryString(),
            'filter' => $filter,
            'search' => $search,
            'counts' => [
                'hot'       => $this->metrics->hotLeadQuery()->count(),
                'qualified' => CallAttempt::where('lead_generated', true)->count(),
                'follow_up' => CallAttempt::where('call_disposition', CallStatus::CALLBACK)->count(),
            ],
        ]);
    }
}
