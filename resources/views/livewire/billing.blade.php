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
        <h2 style="margin-top:0">Redeem promo code</h2>
        <p class="hint">Credit promo codes add credits to your balance immediately.</p>
        <div class="actions" style="align-items: flex-start;">
            <input type="text" wire:model="creditPromoCode" placeholder="Enter code" autocomplete="off" style="max-width: 16rem;">
            <button type="button" wire:click="redeemPromoCode" wire:loading.attr="disabled">Redeem</button>
        </div>
        @error('creditPromoCode')<div class="error">{{ $message }}</div>@enderror
    </div>

    <div class="card">
        <h2 style="margin-top:0">Buy credits</h2>
        <p class="hint">⚠ Payments are not wired up yet — buying a pack credits your account immediately (development stub).</p>

        <div style="margin-bottom: 1rem;">
            <p class="hint" style="margin-top:0">Checkout discount code (optional)</p>
            <div class="actions" style="align-items: flex-start;">
                <input type="text" wire:model="checkoutPromoCode" placeholder="Enter discount code" autocomplete="off" style="max-width: 16rem;" @disabled($appliedCheckoutPromo)>
                @if ($appliedCheckoutPromo)
                    <button type="button" class="secondary" wire:click="clearCheckoutPromo" wire:loading.attr="disabled">Clear</button>
                @else
                    <button type="button" class="secondary" wire:click="applyCheckoutPromo" wire:loading.attr="disabled">Apply</button>
                @endif
            </div>
            @if ($appliedCheckoutPromo)
                <p class="muted" style="margin-bottom:0">Applied: <strong>{{ $appliedCheckoutPromo }}</strong></p>
            @endif
            @error('checkoutPromoCode')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="actions">
            @foreach ($packs as $credits => $pack)
                @php
                    $hasDiscount = $appliedCheckoutPromo && array_key_exists($credits, $discountedPackPrices);
                    $paidZar = $hasDiscount ? $discountedPackPrices[$credits] : $pack['price_zar'];
                @endphp
                <button type="button" wire:click="purchase({{ $credits }})" wire:loading.attr="disabled">
                    {{ $credits }} credits —
                    @if ($hasDiscount && $paidZar < $pack['price_zar'])
                        <span style="text-decoration: line-through; opacity: 0.7;">{{ $pack['label'] }}</span>
                        R{{ $paidZar }}
                    @else
                        {{ $pack['label'] }}
                    @endif
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
