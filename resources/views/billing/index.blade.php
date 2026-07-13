@extends('layouts.app')

@section('title', 'Billing')

@section('content')
    <h1>Credits</h1>
    <p class="muted">You have <strong>{{ $user->ai_credits }}</strong> credit{{ $user->ai_credits === 1 ? '' : 's' }}.
        All services are prepaid from your credit balance.</p>

    <div class="card">
        <h2 style="margin-top:0">Service costs</h2>
        <table>
            <thead><tr><th>Service</th><th>Cost</th></tr></thead>
            <tbody>
            <tr>
                <td>AI website generation</td>
                <td>{{ config('sites.generation_cost') }} credit{{ config('sites.generation_cost') === 1 ? '' : 's' }}</td>
            </tr>
            <tr>
                <td>Domain registration / transfer / renewal</td>
                <td>Based on registrar price (shown before checkout)</td>
            </tr>
            <tr>
                <td>Manual content editing</td>
                <td>{{ config('sites.editing_subscription_price') }} (yearly per website, stub checkout)</td>
            </tr>
            <tr>
                <td>AI newsletter generation</td>
                <td>{{ config('sites.newsletter_generation_cost') }} credit{{ config('sites.newsletter_generation_cost') === 1 ? '' : 's' }}</td>
            </tr>
            <tr>
                <td>AI marketing poster generation</td>
                <td>{{ config('sites.poster_generation_cost') }} credit{{ config('sites.poster_generation_cost') === 1 ? '' : 's' }}</td>
            </tr>
            </tbody>
        </table>
        <p class="hint">Domain prices are converted to credits using a {{ number_format(config('sites.credit_unit_cents') / 100, 2) }} currency unit per credit.</p>
    </div>

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
