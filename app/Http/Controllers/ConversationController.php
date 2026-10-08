<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Services\Presenters\CallResultPresenter;
use App\Services\Presenters\TranscriptPresenter;
use App\Support\CallStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Conversations: the calls that actually produced a transcript.
 *
 * Reads the same call_attempts rows as Call Logs rather than storing transcripts
 * a second time -- the call record already owns the transcript, and a second copy
 * would only be a second thing to keep in step. The difference is the filter: a
 * call nobody answered has nothing to read, so it does not belong here.
 *
 * Tenant scoping comes from the model, so everything below is already limited to
 * the signed-in client's workspace.
 */
class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $conversations = $this->query($filters)
            ->with(['customer', 'agent'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('conversations.index', [
            'conversations' => $conversations,
            'filters'       => $filters,
            'agents'        => Agent::orderBy('name')->get(['id', 'name']),
            'outcomes'      => CallStatus::outcomeLabels(),
            // Only offered as a filter when we genuinely hold language data.
            'languages'     => CallAttempt::query()
                ->whereNotNull('language')
                ->distinct()
                ->orderBy('language')
                ->pluck('language'),
        ]);
    }

    public function show(CallAttempt $conversation): View
    {
        $conversation->load(['customer', 'agent', 'callback']);

        return view('conversations.show', [
            'call'       => $conversation,
            'result'     => CallResultPresenter::for($conversation),
            'transcript' => TranscriptPresenter::for($conversation),
            'callback'   => $conversation->callback,
        ]);
    }

    /** @return array<string,mixed> */
    private function filters(Request $request): array
    {
        return [
            'search'   => trim((string) $request->query('search', '')),
            'agent'    => $request->query('agent'),
            'outcome'  => $request->query('outcome'),
            'language' => $request->query('language'),
            'from'     => $request->query('from'),
            'to'       => $request->query('to'),
        ];
    }

    /** @param array<string,mixed> $filters */
    private function query(array $filters)
    {
        // A conversation is a call that was answered and left something to read.
        $query = CallAttempt::query()
            ->where('connectivity_status', CallStatus::CONNECTED)
            ->whereNotNull('transcript');

        if ($filters['search'] !== '') {
            $term = $filters['search'];

            $query->where(function ($w) use ($term) {
                $w->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")
                    ->orWhere('phone_number', 'like', "%{$term}%"))
                    ->orWhere('customer_phone_number', 'like', "%{$term}%");
            });
        }

        if (filled($filters['agent'])) {
            $query->where('agent_id', $filters['agent']);
        }

        if (filled($filters['outcome'])) {
            $query->where('call_disposition', $filters['outcome']);
        }

        if (filled($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        if (filled($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (filled($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }
}
