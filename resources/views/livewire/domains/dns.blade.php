<div>
    <h1>{{ $domain->domain }} — DNS</h1>
    @include('domains.partials.nav')

    <div class="card">
        <form wire:submit="save">
            <table id="dns-records">
                <thead>
                <tr>
                    <th>Hostname</th>
                    <th>Type</th>
                    <th>Address</th>
                    <th>Priority</th>
                    <th>Record ID</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($records as $index => $record)
                    <tr wire:key="dns-{{ $index }}">
                        <td><input type="text" wire:model="records.{{ $index }}.hostname" required></td>
                        <td><input type="text" wire:model="records.{{ $index }}.type" required></td>
                        <td><input type="text" wire:model="records.{{ $index }}.address" required></td>
                        <td><input type="number" wire:model="records.{{ $index }}.priority"></td>
                        <td><input type="text" wire:model="records.{{ $index }}.recid"></td>
                        <td>
                            <button type="button" class="btn secondary" style="padding:.25rem .6rem"
                                    wire:click="removeRow({{ $index }})">Remove</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="actions" style="margin-top:1rem">
                <button type="button" class="btn secondary" wire:click="addRow">+ Add record</button>
                <button type="submit" wire:loading.attr="disabled">Save DNS records</button>
            </div>
        </form>
    </div>
</div>
