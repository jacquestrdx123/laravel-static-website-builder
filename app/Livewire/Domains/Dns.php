<?php

namespace App\Livewire\Domains;

use App\Models\Domain;
use App\Services\HostAfricaClient;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Dns extends Component
{
    #[Locked]
    public int $domainId;

    /** @var list<array{hostname: string, type: string, address: string, priority: int|string|null, recid: string|null}> */
    public array $records = [];

    public function mount(Domain $domain, HostAfricaClient $client): void
    {
        abort_unless($domain->user_id === auth()->id(), 403);
        $this->domainId = $domain->id;

        $records = [];

        try {
            $response = $client->getDns($domain->domain);
            $records = $response['dnsrecords'] ?? $response['records'] ?? $response;
            if (! is_array($records)) {
                $records = [];
            }
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->records = array_values($records ?: [[
            'hostname' => '@',
            'type' => 'A',
            'address' => '',
            'priority' => 0,
            'recid' => '',
        ]]);
    }

    public function addRow(): void
    {
        $this->records[] = [
            'hostname' => '@',
            'type' => 'A',
            'address' => '',
            'priority' => 0,
            'recid' => '',
        ];
    }

    public function removeRow(int $index): void
    {
        if (count($this->records) <= 1) {
            return;
        }

        unset($this->records[$index]);
        $this->records = array_values($this->records);
    }

    public function save(HostAfricaClient $client): void
    {
        $domain = Domain::query()->findOrFail($this->domainId);
        abort_unless($domain->user_id === auth()->id(), 403);

        $data = $this->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.hostname' => ['required', 'string', 'max:255'],
            'records.*.type' => ['required', 'string', 'max:10'],
            'records.*.address' => ['required', 'string', 'max:255'],
            'records.*.priority' => ['nullable', 'integer', 'min:0'],
            'records.*.recid' => ['nullable', 'string', 'max:50'],
        ]);

        $records = collect($data['records'])
            ->map(fn (array $record) => array_filter([
                'hostname' => $record['hostname'],
                'type' => strtoupper($record['type']),
                'address' => $record['address'],
                'priority' => $record['priority'] ?? 0,
                'recid' => $record['recid'] ?? null,
            ]))
            ->values()
            ->all();

        try {
            $client->saveDns($domain->domain, $records);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('status', 'DNS records updated.');
    }

    public function render()
    {
        $domain = Domain::query()->findOrFail($this->domainId);

        return view('livewire.domains.dns', [
            'domain' => $domain,
        ])->extends('layouts.app')->title($domain->domain.' — DNS');
    }
}
