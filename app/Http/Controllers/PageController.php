<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * The public, unauthenticated marketing site.
 *
 * Deliberately knows nothing about the voice platform: it renders copy and a
 * pricing table from config, and its only link into the product is the sign-in
 * page. No vendor name, credential or identifier is reachable from here.
 */
class PageController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'plans'        => config('pricing.plans', []),
            'contactEmail' => config('pricing.contact_email'),
            'pricingNote'  => config('pricing.currency_note'),
        ]);
    }
}
