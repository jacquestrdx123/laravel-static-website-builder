<?php

namespace App\Livewire\Websites;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My websites')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.websites.index', [
            'websites' => auth()->user()->websites()->latest()->get(),
        ])->extends('layouts.app');
    }
}
