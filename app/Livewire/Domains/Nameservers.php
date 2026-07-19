<?php

namespace App\Livewire\Domains;

use App\Models\Domain;
use App\Services\HostAfricaClient;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Nameservers extends Component
{
    #[Locked]
    public int $domainId;

    /** @var array<string, string> */
    public array $nameservers = [];

    public function mount(Domain $domain, HostAfricaClient $client): void
    {
        abort_unless($domain->user_id === auth()->id(), 403);
        $this->domainId = $domain->id;

        $nameservers = $domain->nameservers ?? [];

        try {
            $response = $client->getNameservers($domain->domain);
            if (is_array($response)) {
                $nameservers = array_filter([
                    'ns1' => $response['ns1'] ?? null,
                    'ns2' => $response['ns2'] ?? null,
                    'ns3' => $response['ns3'] ?? null,
                    'ns4' => $response['ns4'] ?? null,
                    'ns5' => $response['ns5'] ?? null,
                ]);
            }
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->nameservers = [
            'ns1' => (string) ($nameservers['ns1'] ?? ''),
            'ns2' => (string) ($nameservers['ns2'] ?? ''),
            'ns3' => (string) ($nameservers['ns3'] ?? ''),
            'ns4' => (string) ($nameservers['ns4'] ?? ''),
            'ns5' => (string) ($nameservers['ns5'] ?? ''),
        ];
    }

    public function render()
    {
        $domain = Domain::query()->findOrFail($this->domainId);

        return view('livewire.domains.nameservers', [
            'domain' => $domain,
        ])->extends('layouts.app')->title($domain->domain.' — Nameservers');
    }
}
