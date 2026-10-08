<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Services\DashboardMetrics;
use App\Support\CallStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Leads & Results -- every call the agent made, with what came of it.
 *
 * The tabs narrow by lead status and read the same query the dashboard rail uses,
 * so "Hot lead" means one thing across the product. The filters below it are the
 * ones a client actually reaches for: date range, agent, call status, outcome and
 * a search over name or number.
 *
 * Everything is workspace-scoped by the model, so one client's results are never
 * visible to another.
 */
class LeadController extends Controller
{
    public function __construct(private readonly DashboardMetrics $metrics)
    {
    }

    public function index(Request $request): View
    {
        $tab     = $request->query('filter', 'hot');
        $filters = $this->filters($request);

        $query = match ($tab) {
            'follow_up' => CallAttempt::query()->where('call_disposition', CallStatus::CALLBACK),
            'qualified' => CallAttempt::query()->where('lead_generated', true),
            'all'       => CallAttempt::query(),
            default     => $this->metrics->hotLeadQuery(),
        };

        $query->with(['customer', 'agent', 'callback'])->latest('id');

        $this->applyFilters($query, $filters);

        return view('leads.index', [
            'leads'    => $query->paginate(20)->withQueryString(),
            'filter'   => $tab,
            'filters'  => $filters,
            // Kept for the existing search box in the view.
            'search'   => $filters['q'],
            'agents'   => Agent::orderBy('name')->get(['id', 'name']),
            'statuses' => CallStatus::connectivityLabels(),
            'outcomes' => CallStatus::outcomeLabels(),
            'counts'   => [
                'hot'       => $this->metrics->hotLeadQuery()->count(),
                'qualified' => CallAttempt::where('lead_generated', true)->count(),
                'follow_up' => CallAttempt::where('call_disposition', CallStatus::CALLBACK)->count(),
                'all'       => CallAttempt::count(),
            ],
        ]);
    }

    /** @return array<string,mixed> */
    private function filters(Request $request): array
    {
        return [
            'q'       => $request->query('q'),
            'agent'   => $request->query('agent'),
            'status'  => $request->query('status'),
            'outcome' => $request->query('outcome'),
            'from'    => $request->query('from'),
            'to'      => $request->query('to'),
        ];
    }

    /** @param array<string,mixed> $filters */
    private function applyFilters($query, array $filters): void
    {
        if ($search = $filters['q']) {
            // Wildcards in the term are escaped so a customer searching for "%"
            // does not match everything.
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

            $query->where(function ($w) use ($like) {
                $w->where('customer_phone_number', 'like', $like)
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)
                      ->orWhere('phone_number', 'like', $like)
                      ->orWhere('policy_number', 'like', $like));
            });
        }

        if (filled($filters['agent'])) {
            $query->where('agent_id', (int) $filters['agent']);
        }

        if (filled($filters['status']) && array_key_exists($filters['status'], CallStatus::connectivityLabels())) {
            $query->where('connectivity_status', $filters['status']);
        }

        if (filled($filters['outcome']) && array_key_exists($filters['outcome'], CallStatus::outcomeLabels())) {
            $query->where('call_disposition', $filters['outcome']);
        }

        if (filled($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (filled($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
    }
}
