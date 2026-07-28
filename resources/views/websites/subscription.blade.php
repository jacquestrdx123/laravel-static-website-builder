@extends('layouts.app')

@section('title', 'Subscription — '.$website->name)

@section('content')
    <h1>Editing without AI</h1>
    <p class="muted">
        Optional yearly plan for <strong>{{ $website->name }}</strong>.
        Manual content editing is available to all owners; this subscription tracks your prepaid editing entitlement
        ({{ $creditsPerMonth }} credits/month or {{ $creditsPerYear }} credits/year).
    </p>

    <div class="card">
        @if ($website->hasActiveEditingSubscription())
            <p><strong>Status:</strong> Active until {{ $subscription?->expires_at?->format('Y-m-d') ?? '—' }}</p>
            <p class="hint">You can edit content from the website preview page.</p>
            <a class="btn" href="{{ route('websites.content.edit', $website) }}">Edit content</a>
            <form method="POST" action="{{ route('websites.subscription.purchase', $website) }}" style="margin-top:1rem">
                @csrf
                <button type="submit" class="btn secondary">Extend 1 year ({{ $creditsPerYear }} credits · {{ $priceZar }})</button>
            </form>
        @else
            <p><strong>Price:</strong> {{ $creditsPerYear }} credits / year ({{ $priceZar }}, or {{ $priceMonthlyZar }})</p>
            <form method="POST" action="{{ route('websites.subscription.purchase', $website) }}">
                @csrf
                <button type="submit">Pay {{ $creditsPerYear }} credits &amp; activate</button>
            </form>
        @endif
    </div>

    <p><a href="{{ route('websites.show', $website) }}">← Back to website</a></p>
@endsection
