<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallbackController extends Controller
{
    public function index(Request $request): View
    {
        $range = $request->query('range', 'upcoming');

        $query = Customer::query()->with('latestCallAttempt')->whereNotNull('next_callback_at');

        match ($range) {
            'today'   => $query->whereDate('next_callback_at', today()),
            'overdue' => $query->where('next_callback_at', '<', now()),
            'week'    => $query->whereBetween('next_callback_at', [now(), now()->addWeek()]),
            default   => $query->where('next_callback_at', '>=', now()),
        };

        return view('callbacks.index', [
            'customers' => $query->orderBy('next_callback_at')->paginate(25)->withQueryString(),
            'range'     => $range,
            'counts'    => [
                'overdue'  => Customer::whereNotNull('next_callback_at')->where('next_callback_at', '<', now())->count(),
                'today'    => Customer::whereDate('next_callback_at', today())->count(),
                'week'     => Customer::whereBetween('next_callback_at', [now(), now()->addWeek()])->count(),
                'upcoming' => Customer::where('next_callback_at', '>=', now())->count(),
            ],
        ]);
    }

    /** Clear a callback once it has been handled. */
    public function clear(Customer $customer)
    {
        $customer->update(['next_callback_at' => null]);

        return back()->with('status', 'Callback cleared for ' . ($customer->name ?: $customer->display_phone) . '.');
    }
}
