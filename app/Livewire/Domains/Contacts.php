<?php

namespace App\Livewire\Domains;

use App\Models\Domain;
use App\Services\HostAfricaClient;
use App\Support\DomainContactBuilder;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Contacts extends Component
{
    #[Locked]
    public int $domainId;

    /** @var array<string, mixed> */
    public array $contact = [];

    public function mount(Domain $domain, HostAfricaClient $client): void
    {
        abort_unless($domain->user_id === auth()->id(), 403);
        $this->domainId = $domain->id;

        $contact = $domain->contacts ?? DomainContactBuilder::fromUser(auth()->user());

        try {
            $response = $client->getContacts($domain->domain);
            $contact = $response['Registrant']
                ?? $response['registrant']
                ?? $response['contactdetails']['Registrant']
                ?? $contact;
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->contact = is_array($contact) ? $contact : [];
    }

    public function render()
    {
        $domain = Domain::query()->findOrFail($this->domainId);

        return view('livewire.domains.contacts', [
            'domain' => $domain,
            'contact' => $this->contact,
        ])->extends('layouts.app')->title($domain->domain.' — Contacts');
    }
}
