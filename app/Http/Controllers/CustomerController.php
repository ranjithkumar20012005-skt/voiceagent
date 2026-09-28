<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerImporter;
use App\Support\CallStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->with('latestCallAttempt');

        $query->search($request->query('q'));

        if ($status = $request->query('status')) {
            $query->where('customer_status', $status);
        }

        if ($outcome = $request->query('outcome')) {
            $query->where('last_outcome', $outcome);
        }

        if ($request->filled('callback_from')) {
            $query->whereDate('next_callback_at', '>=', $request->date('callback_from'));
        }

        if ($request->filled('callback_to')) {
            $query->whereDate('next_callback_at', '<=', $request->date('callback_to'));
        }

        if ($request->boolean('dnc_only')) {
            $query->where('do_not_call', true);
        }

        $sort = in_array($request->query('sort'), ['name', 'policy_expiry_date', 'last_call_at', 'created_at'], true)
            ? $request->query('sort')
            : 'created_at';

        $query->orderBy($sort, $request->query('dir') === 'asc' ? 'asc' : 'desc');

        return view('customers.index', [
            'customers' => $query->paginate(25)->withQueryString(),
            'statuses'  => ['pending', 'queued', 'in_progress', 'contacted', 'closed'],
            'outcomes'  => CallStatus::outcomeLabels(),
            'filters'   => $request->only(['q', 'status', 'outcome', 'callback_from', 'callback_to', 'dnc_only', 'sort', 'dir']),
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['callAttempts' => fn ($q) => $q->with('agent')->limit(50), 'importBatch']);

        $campaignIds = $customer->callAttempts->pluck('campaign_id')->filter()->unique();

        return view('customers.show', [
            'customer'  => $customer,
            'campaigns' => $campaignIds->isEmpty()
                ? collect()
                : \App\Models\Campaign::whereIn('sarvam_campaign_id', $campaignIds)->latest('id')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'               => ['nullable', 'string', 'max:120'],
            'policy_number'      => ['nullable', 'string', 'max:64'],
            'preferred_language' => ['nullable', 'string', 'in:' . implode(',', config('sarvam.languages', []))],
            'customer_status'    => ['nullable', 'in:pending,queued,in_progress,contacted,closed'],
            'next_callback_at'   => ['nullable', 'date'],
            'do_not_call'        => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:5000'],
        ]);

        $data['do_not_call'] = $request->boolean('do_not_call');

        $customer->update($data);

        return back()->with('status', 'Customer updated.');
    }

    /**
     * A blank template with our canonical headings, so a client has something
     * correct to start from.
     */
    public function template(): StreamedResponse
    {
        $headers = [
            'customer_id', 'user_name', 'phone_number', 'policy_number',
            'registered_mobile', 'policy_expiry_date', 'renewal_premium',
            'preferred_language',
        ];

        $example = [
            'CUST-001', 'Anita Sharma', '+919876543210', 'POL-2291',
            '+919876543210', '2026-10-15', '14500', 'Hindi',
        ];

        return $this->streamCsv('customer-import-template.csv', function ($out) use ($headers, $example) {
            fputcsv($out, $headers);
            fputcsv($out, $example);
        });
    }

    /** Export the current filtered view. */
    public function export(Request $request): StreamedResponse
    {
        $query = Customer::query()->search($request->query('q'));

        if ($status = $request->query('status')) {
            $query->where('customer_status', $status);
        }

        if ($outcome = $request->query('outcome')) {
            $query->where('last_outcome', $outcome);
        }

        return $this->streamCsv('customers-' . now()->format('Y-m-d') . '.csv', function ($out) use ($query) {
            fputcsv($out, [
                'customer_id', 'name', 'phone_number', 'policy_number', 'registered_mobile',
                'policy_expiry_date', 'renewal_premium', 'preferred_language', 'status',
                'last_outcome', 'last_call_at', 'next_callback_at', 'do_not_call', 'notes',
            ]);

            $query->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $c) {
                    fputcsv($out, array_map([$this, 'csvSafe'], [
                        $c->customer_identifier,
                        $c->name,
                        $c->phone_number,
                        $c->policy_number,
                        $c->registered_mobile,
                        optional($c->policy_expiry_date)->toDateString(),
                        $c->renewal_premium,
                        $c->preferred_language,
                        $c->customer_status,
                        $c->last_outcome,
                        optional($c->last_call_at)->toDateTimeString(),
                        optional($c->next_callback_at)->toDateTimeString(),
                        $c->do_not_call ? 'yes' : 'no',
                        $c->notes,
                    ]));
                }
            });
        });
    }

    private function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 correctly
            $writer($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralise CSV formula injection: a cell beginning =, +, -, @, tab or CR
     * is executed by Excel and Sheets when the export is opened.
     */
    private function csvSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
