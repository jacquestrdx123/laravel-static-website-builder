<?php

namespace App\Livewire\Domains;

use App\Models\Domain;
use App\Services\HostAfricaClient;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Settings extends Component
{
    #[Locked]
    public int $domainId;

    /** @var array<string, mixed>|null */
    public ?array $lock = null;

    /** @var array<string, mixed>|null */
    public ?array $emailForwarding = null;

    public function mount(Domain $domain, HostAfricaClient $client): void
    {
        abort_unless($domain->user_id === auth()->id(), 403);
        $this->domainId = $domain->id;

        try {
            $this->lock = $client->getLock($domain->domain);
            $this->emailForwarding = $client->getEmailForwarding($domain->domain);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $domain = Domain::query()->findOrFail($this->domainId);

        return view('livewire.domains.settings', [
            'domain' => $domain,
            'lock' => $this->lock,
            'emailForwarding' => $this->emailForwarding,
        ])->extends('layouts.app')->title($domain->domain.' — Settings');
    }
}
