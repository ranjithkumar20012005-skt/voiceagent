<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAutomationRequest;
use App\Models\Automation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class AutomationController extends Controller
{
    public function index(): View
    {
        $automations = Automation::orderBy('id')->get();

        return view('automations.index', [
            'automations' => $automations,
            // Live count of who each automation would call right now.
            'eligible'    => $automations->mapWithKeys(
                fn (Automation $a) => [$a->id => $a->eligibleCustomers()->count()]
            ),
        ]);
    }

    public function update(StoreAutomationRequest $request, Automation $automation)
    {
        $automation->update($request->validated());

        return back()->with('status', 'Automation saved.');
    }

    public function store(StoreAutomationRequest $request)
    {
        Automation::create($request->validated());

        return back()->with('status', 'Automation created.');
    }

    /**
     * Run one automation immediately. Deliberately a dry run by default -- an
     * operator should see who would be called before real calls go out.
     */
    public function run(Automation $automation)
    {
        $dry = request()->boolean('dry_run', true);

        Artisan::call('calls:dispatch-due', array_filter([
            '--automation' => (string) $automation->id,
            '--dry-run'    => $dry,
            '--force'      => ! $dry && request()->boolean('force'),
        ]));

        $message = $dry
            ? 'Preview complete: ' . $automation->eligibleCustomers()->count() . ' customers currently match.'
            : (trim(Artisan::output()) ?: 'Automation run dispatched.');

        return back()->with('status', $message);
    }
}
