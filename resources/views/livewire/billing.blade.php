<div>
    <h1>Credits</h1>
    <p class="muted">You have <strong>{{ $user->ai_credits }}</strong> credit{{ $user->ai_credits === 1 ? '' : 's' }}.
        All services are prepaid from your credit balance.
        1 credit = {{ $catalog['currency_symbol'] }}{{ number_format($catalog['credit_value_zar'], 0) }}.</p>

    <div class="card">
        <div class="actions" style="justify-content: space-between;">
            <h2 style="margin:0">Locked service rates</h2>
            <a class="btn secondary" href="{{ route('pricing') }}">Full pricing</a>
        </div>
        <table>
            <thead><tr><th>Service</th><th>Cost</th><th>ZAR</th></tr></thead>
            <tbody>
            @foreach ($catalog['items'] as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td>{{ $item['credits_label'] }}</td>
                    <td>{{ $item['zar_label'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td>Domain registration / transfer / renewal</td>
                <td colspan="2">Based on registrar price (shown before checkout)</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Buy credits</h2>
        <p class="hint">⚠ Payments are not wired up yet — buying a pack credits your account immediately (development stub).</p>
        <div class="actions">
            @foreach ($packs as $credits => $price)
                <button type="button" wire:click="purchase({{ $credits }})" wire:loading.attr="disabled">
                    {{ $credits }} credits — {{ $price }}
                </button>
            @endforeach
        </div>
        @error('credits')<div class="error">{{ $message }}</div>@enderror
    </div>

    <div class="card">
        <h2 style="margin-top:0">History</h2>
        @if ($transactions->isEmpty())
            <p class="muted">No transactions yet.</p>
        @else
            <table>
                <thead><tr><th>When</th><th>Description</th><th style="text-align:right">Credits</th></tr></thead>
                <tbody>
                @foreach ($transactions as $tx)
                    <tr wire:key="tx-{{ $tx->id }}">
                        <td class="muted">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $tx->description }}</td>
                        <td style="text-align:right; color: {{ $tx->amount >= 0 ? 'var(--ok)' : 'var(--danger)' }}">
                            {{ $tx->amount >= 0 ? '+' : '' }}{{ $tx->amount }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
