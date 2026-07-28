<div>
    <h1>Search domains</h1>
    <p class="muted">Check availability and register a custom domain using your prepaid credits.</p>

    <div class="card">
        <form wire:submit="search">
            <label for="searchTerm">Domain name</label>
            <input type="text" id="searchTerm" wire:model="searchTerm" placeholder="mybusiness" required>
            @error('searchTerm')<div class="error">{{ $message }}</div>@enderror
            <p class="hint">Enter a name without the TLD, or a full domain like mybusiness.co.za</p>
            <div class="actions" style="margin-top:1rem">
                <button type="submit" wire:loading.attr="disabled">Search availability</button>
                <a class="btn secondary" href="{{ route('domains.transfer') }}">Transfer a domain</a>
                <a class="btn secondary" href="{{ route('domains.index') }}">My domains</a>
            </div>
        </form>
    </div>

    @if (! empty($results))
        <div class="card">
            <h2 style="margin-top:0">Results for “{{ $searchTerm }}”</h2>
            <table>
                <thead>
                <tr>
                    <th>Domain</th>
                    <th>Status</th>
                    <th>Credits</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($results as $result)
                    <tr wire:key="result-{{ $result['domain'] }}">
                        <td>{{ $result['domain'] }}</td>
                        <td>
                            @if ($result['available'])
                                <span class="status-badge status-ready">Available</span>
                            @else
                                <span class="status-badge status-failed">Taken</span>
                            @endif
                        </td>
                        <td>{{ isset($result['credits']) ? $result['credits'].' credit'.($result['credits'] === 1 ? '' : 's') : '—' }}</td>
                        <td>
                            @if ($result['available'])
                                <a class="btn" style="padding:.35rem .9rem" href="{{ route('domains.register', ['domain' => $result['domain']]) }}">Register</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
