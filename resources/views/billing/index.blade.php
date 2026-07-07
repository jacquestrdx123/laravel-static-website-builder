@extends('layouts.app')

@section('title', 'Billing')

@section('content')
    <h1>AI credits</h1>
    <p class="muted">You have <strong>{{ $user->ai_credits }}</strong> credit{{ $user->ai_credits === 1 ? '' : 's' }}.
        Each website generation costs {{ config('sites.generation_cost') }}.</p>

    <div class="card">
        <h2 style="margin-top:0">Buy credits</h2>
        <p class="hint">⚠ Payments are not wired up yet — buying a pack credits your account immediately (development stub).</p>
        <div class="actions">
            @foreach ($packs as $credits => $price)
                <form method="POST" action="{{ route('billing.purchase') }}">
                    @csrf
                    <input type="hidden" name="credits" value="{{ $credits }}">
                    <button type="submit">{{ $credits }} credits — {{ $price }}</button>
                </form>
            @endforeach
        </div>
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
                    <tr>
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
@endsection
