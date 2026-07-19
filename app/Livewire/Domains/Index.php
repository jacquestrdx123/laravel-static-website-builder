<?php

namespace App\Livewire\Domains;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My domains')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.domains.index', [
            'domains' => auth()->user()->domains()->with('website')->get(),
        ])->extends('layouts.app');
    }
}
