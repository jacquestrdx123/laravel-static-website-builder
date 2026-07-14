<?php

namespace App\Http\Controllers;

use App\Support\CreditsPricing;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(CreditsPricing $pricing): View
    {
        return view('pricing.index', [
            'pricing' => $pricing->catalog(),
        ]);
    }
}
