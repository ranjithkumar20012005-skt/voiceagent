<?php

namespace App\Http\Controllers;

use App\Models\Callback;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Callbacks the agent agreed to during a call.
 *
 * Reads the `callbacks` table, which the webhook now populates through
 * CallbackRecorder. It used to read `customers.next_callback_at`, which could
 * only ever hold one pending callback per customer and carried no reason or link
 * back to the call that asked for it.
 *
 * `customers.next_callback_at` is still maintained by the result processor for
 * the CRM views, so nothing that depended on it broke.
 */
class CallbackController extends Controller
{
    /** Tab => how to narrow the list. */
    private const RANGES = ['upcoming', 'due', 'completed', 'cancelled', 'all'];

    public function index(Request $request): View
    {
        $range = in_array($request->query('range'), self::RANGES, true) ? $request->query('range') : 'upcoming';

        $query = Callback::query()->with(['customer', 'agent', 'call']);

        match ($range) {
            'due'       => $query->dueNow(),
            'completed' => $query->where('status', Callback::COMPLETED),
            'cancelled' => $query->whereIn('status', [Callback::CANCELLED, Callback::FAILED]),
            'all'       => $query,
            default     => $query->pending()->where('scheduled_at', '>', now()),
        };

        return view('callbacks.index', [
            'callbacks' => $query->orderBy('scheduled_at')->paginate(25)->withQueryString(),
            'range'     => $range,
            'counts'    => [
                'upcoming'  => Callback::pending()->where('scheduled_at', '>', now())->count(),
                'due'       => Callback::dueNow()->count(),
                'completed' => Callback::where('status', Callback::COMPLETED)->count(),
                'cancelled' => Callback::whereIn('status', [Callback::CANCELLED, Callback::FAILED])->count(),
                'all'       => Callback::count(),
            ],
        ]);
    }

    /** Mark a callback as dealt with. */
    public function complete(Callback $callback)
    {
        $callback->forceFill([
            'status'       => Callback::COMPLETED,
            'completed_at' => now(),
        ])->save();

        // Keep the customer's CRM field in step so the two views agree.
        if ($callback->customer && ! Callback::pending()->where('customer_id', $callback->customer_id)->exists()) {
            $callback->customer->forceFill(['next_callback_at' => null])->save();
        }

        return back()->with('status', 'Callback marked as done.');
    }

    public function cancel(Callback $callback)
    {
        $callback->forceFill(['status' => Callback::CANCELLED])->save();

        return back()->with('status', 'Callback cancelled.');
    }

    /**
     * Clear the CRM reminder on a customer.
     *
     * Kept because the old Customers screens link to it; it now also cancels any
     * pending callback rows for that customer so the two cannot disagree.
     */
    public function clear(Customer $customer)
    {
        $customer->update(['next_callback_at' => null]);

        Callback::pending()->where('customer_id', $customer->id)->update(['status' => Callback::CANCELLED]);

        return back()->with('status', 'Callback cleared for ' . ($customer->name ?: $customer->display_phone) . '.');
    }
}
