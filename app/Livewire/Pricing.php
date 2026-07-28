<?php

namespace App\Livewire;

use App\Support\CreditsPricing;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pricing')]
class Pricing extends Component
{
    public function render(CreditsPricing $pricing)
    {
        $catalog = $pricing->catalog();

        return view('livewire.pricing', [
            'pricing' => $catalog,
        ])->extends('layouts.app')->layoutData(['pricing' => $catalog]);
    }
}
