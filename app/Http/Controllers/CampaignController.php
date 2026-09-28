<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Jobs\DispatchCampaignJob;
use App\Models\CallAttempt;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Services\SarvamException;
use App\Services\SarvamVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(private readonly SarvamVoiceService $sarvam)
    {
    }

    public function index(): View
    {
        $campaigns = Campaign::latest('id')->paginate(20);

        $stats = CallAttempt::whereIn('campaign_id', $campaigns->pluck('sarvam_campaign_id')->filter())
            ->selectRaw(<<<'SQL'
                campaign_id,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN connectivity_status IN ('failed', 'no_answer', 'busy') OR status = 'failed' THEN 1 ELSE 0 END) AS failed
            SQL)
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        return view('campaigns.index', [
            'campaigns'  => $campaigns,
            'stats'      => $stats,
            'batches'    => ImportBatch::where('status', ImportBatch::COMPLETED)->latest('id')->limit(25)->get(),
            'configured' => $this->sarvam->isConfigured(),
        ]);
    }

    public function store(StoreCampaignRequest $request)
    {
        $data = $request->validated();

        if (! $this->sarvam->isConfigured()) {
            return back()->withErrors(['name' => 'The voice service is not configured yet.']);
        }

        $customerIds = $this->resolveCustomerIds($data);

        if ($customerIds === []) {
            return back()->withErrors(['name' => 'No callable customers matched that selection.'])->withInput();
        }

        $campaign = Campaign::create([
            'name'                => $data['name'],
            'description'         => $data['description'] ?? null,
            'status'              => 'draft',
            'import_batch_id'     => $data['import_batch_id'] ?? null,
            'user_id'             => $request->user()->id,
            'total_contacts'      => count($customerIds),
            'attempts_per_second' => $data['attempts_per_second'] ?? config('sarvam.campaign.attempts_per_second'),
            'starts_at'           => $data['starts_at'] ?? now(),
            'ends_at'             => $data['ends_at'] ?? now()->addDays(7),
        ]);

        DispatchCampaignJob::dispatch($campaign->id, $customerIds);

        return redirect()->route('campaigns.show', $campaign)
            ->with('status', 'Campaign queued with ' . count($customerIds) . ' contacts.');
    }

    public function show(Campaign $campaign): View
    {
        $stats = null;

        if ($campaign->sarvam_campaign_id) {
            $stats = CallAttempt::where('campaign_id', $campaign->sarvam_campaign_id)
                ->selectRaw(<<<'SQL'
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
                    SUM(CASE WHEN connectivity_status = 'no_answer' THEN 1 ELSE 0 END) AS no_answer,
                    SUM(CASE WHEN connectivity_status = 'busy' THEN 1 ELSE 0 END) AS busy,
                    SUM(CASE WHEN connectivity_status = 'failed' THEN 1 ELSE 0 END) AS failed
                SQL)
                ->first();
        }

        return view('campaigns.show', [
            'campaign' => $campaign,
            'stats'    => $stats,
            'attempts' => $campaign->sarvam_campaign_id
                ? CallAttempt::with('customer')
                    ->where('campaign_id', $campaign->sarvam_campaign_id)
                    ->latest('id')->paginate(25)
                : null,
        ]);
    }

    /** pause | resume | cancel, forwarded to the voice platform. */
    public function updateStatus(Request $request, Campaign $campaign): JsonResponse
    {
        $action = $request->validate([
            'action' => ['required', 'in:pause,resume,cancel'],
        ])['action'];

        if (! $campaign->isRemote()) {
            return response()->json([
                'ok'      => false,
                'message' => 'This campaign has not been dispatched yet.',
            ], 422);
        }

        try {
            $result = $this->sarvam->updateCampaignStatus($campaign->sarvam_campaign_id, $action);
        } catch (SarvamException $e) {
            return response()->json(['ok' => false, 'message' => $e->userMessage()], 502);
        }

        $campaign->update(['status' => $result['status'] ?? $campaign->status]);

        return response()->json([
            'ok'      => true,
            'status'  => $campaign->status,
            'message' => 'Campaign ' . $action . 'd.',
        ]);
    }

    /** Pull the live campaign record so the page reflects remote state. */
    public function refresh(Campaign $campaign): JsonResponse
    {
        if (! $campaign->isRemote()) {
            return response()->json(['ok' => false, 'message' => 'Not dispatched yet.'], 422);
        }

        try {
            $remote = $this->sarvam->getCampaign($campaign->sarvam_campaign_id);
        } catch (SarvamException $e) {
            return response()->json(['ok' => false, 'message' => $e->userMessage()], 502);
        }

        if (! empty($remote['status'])) {
            $campaign->update(['status' => $remote['status']]);
        }

        return response()->json(['ok' => true, 'status' => $campaign->status]);
    }

    /** @return list<int> */
    private function resolveCustomerIds(array $data): array
    {
        $query = Customer::query()->callable();

        switch ($data['source']) {
            case 'selected':
                $query->whereIn('id', $data['customer_ids'] ?? []);
                break;

            case 'import_batch':
                if (empty($data['import_batch_id'])) {
                    return [];
                }
                $query->where('import_batch_id', $data['import_batch_id']);
                break;

            case 'filtered':
                $days = (int) ($data['expiry_within_days'] ?? 30);
                $query->whereNotNull('policy_expiry_date')
                      ->whereDate('policy_expiry_date', '>=', now()->toDateString())
                      ->whereDate('policy_expiry_date', '<=', now()->addDays($days)->toDateString())
                      ->where('customer_status', 'pending');
                break;
        }

        return $query->limit((int) config('sarvam.campaign.max_calls_per_run', 200) * 25)
            ->pluck('id')->all();
    }
}
