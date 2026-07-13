@extends('layouts.app')

@section('title', 'Subscription — '.$website->name)

@section('content')
    <h1>Manual editing subscription</h1>
    <p class="muted">Edit offerings, tagline, and contact details on <strong>{{ $website->name }}</strong> without regenerating the site.</p>

    <div class="card">
        @if ($website->hasActiveEditingSubscription())
            <p><strong>Status:</strong> Active until {{ $subscription?->expires_at?->format('Y-m-d') ?? '—' }}</p>
            <p class="hint">You can edit content from the website preview page.</p>
            <a class="btn" href="{{ route('websites.content.edit', $website) }}">Edit content</a>
        @else
            <p>No active subscription. Manual content editing is gated behind a yearly plan.</p>
            <p><strong>Price:</strong> {{ $price }} (stub checkout — no payment taken)</p>
            <form method="POST" action="{{ route('websites.subscription.purchase', $website) }}">
                @csrf
                <button type="submit">Activate subscription</button>
            </form>
        @endif
    </div>

    <p><a href="{{ route('websites.show', $website) }}">← Back to website</a></p>
@endsection
