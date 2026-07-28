<?php

namespace App\Livewire;

use App\Http\Controllers\BillingController;
use App\Support\CreditsPricing;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Billing')]
class Billing extends Component
{
    public function purchase(int $credits): void
    {
        $packs = BillingController::PACKS;

        if (! array_key_exists($credits, $packs)) {
            $this->addError('credits', 'Invalid credit pack.');

            return;
        }

        auth()->user()->addCredits(
            $credits,
            'Credit pack purchase ('.$packs[$credits].') [stub - no payment taken]'
        );

        session()->flash('status', $credits.' credits added to your account.');
    }

    public function render(CreditsPricing $pricing)
    {
        return view('livewire.billing', [
            'user' => auth()->user(),
            'packs' => BillingController::PACKS,
            'transactions' => auth()->user()->creditTransactions()->limit(25)->get(),
            'catalog' => $pricing->catalog(),
        ])->extends('layouts.app');
    }
}
