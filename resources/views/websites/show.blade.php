@extends('layouts.app')

@section('title', $website->name)

@section('content')
    <div class="actions" style="justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="margin:0">{{ $website->name }}</h1>
        <span class="status-badge status-{{ $website->status }}" id="status-badge">{{ $website->status }}</span>
    </div>

    @if ($website->isBusy())
        <div class="card" id="progress-card" style="text-align:center; padding: 3rem;">
            <p style="font-size:1.15rem"><span class="spinner"></span> The AI is building your website…</p>
            <p class="muted">This usually takes a few minutes. This page refreshes automatically.</p>
        </div>
        <script>
            (function poll() {
                setTimeout(function () {
                    fetch('{{ route('websites.status', $website) }}', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(d => {
                            if (d.status !== 'queued' && d.status !== 'generating') {
                                window.location.reload();
                            } else {
                                poll();
                            }
                        })
                        .catch(poll);
                }, 4000);
            })();
        </script>
    @endif

    @if ($website->status === \App\Models\Website::STATUS_FAILED)
        <div class="flash err">
            <strong>Generation failed.</strong> {{ $website->error }}<br>
            <span class="hint">Your credit has been refunded — you can try again below.</span>
        </div>
    @endif

    @if ($website->isGenerated())
        <div class="card">
            <div class="actions" style="justify-content: space-between; margin-bottom: .8rem;">
                <strong>Preview</strong>
                <span class="actions">
                    @if ($hasEditingSubscription)
                        <a class="btn secondary" href="{{ route('websites.content.edit', $website) }}">✏ Edit content</a>
                    @else
                        <a class="btn secondary" href="{{ route('websites.subscription.show', $website) }}">✏ Edit content (subscription)</a>
                    @endif
                    <a class="btn secondary" target="_blank" href="{{ $website->previewUrl() }}">Open full screen ↗</a>
                </span>
            </div>
            <iframe class="preview" src="{{ $website->previewUrl() }}" title="Website preview"></iframe>
        </div>

        <div class="card">
            @if ($website->status === \App\Models\Website::STATUS_PUBLISHED)
                <p>
                    <strong>Live at:</strong>
                    <a href="https://{{ $website->hostname() }}" target="_blank">https://{{ $website->hostname() }}</a>
                    <span class="hint">(published {{ $website->published_at->diffForHumans() }})</span>
                </p>
                <div class="actions">
                    <form method="POST" action="{{ route('websites.unpublish', $website) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn secondary">Unpublish</button>
                    </form>
                </div>
            @else
                <p><strong>Happy with it?</strong> Publish it to <code>{{ $website->hostname() }}</code>.</p>
                <form method="POST" action="{{ route('websites.publish', $website) }}">
                    @csrf
                    <button type="submit">Publish website</button>
                </form>
            @endif
        </div>

        <div class="card">
            <strong>Marketing services</strong>
            <p class="hint">Newsletters and posters use prepaid credits. Content history is stored in your private website vault.</p>
            <div class="actions">
                <a class="btn secondary" href="{{ route('websites.subscription.show', $website) }}">
                    Subscription {{ $hasEditingSubscription ? '(active)' : '' }}
                </a>
                <a class="btn secondary" href="{{ route('websites.newsletters.index', $website) }}">
                    Newsletters ({{ $vaultCounts['newsletters'] }})
                </a>
                <a class="btn secondary" href="{{ route('websites.posters.index', $website) }}">
                    Posters ({{ $vaultCounts['posters'] }})
                </a>
            </div>
            <p class="hint" style="margin-top:.8rem;">Vault history: {{ $vaultCounts['snapshots'] }} product snapshot(s)</p>
        </div>
    @endif

    @unless ($website->isBusy())
        <div class="card">
            <strong>Not quite right?</strong>
            <p class="hint">Regenerating uses the same brief and photos but produces a fresh design.
                Costs {{ config('sites.generation_cost') }} credit.</p>
            <div class="actions">
                <form method="POST" action="{{ route('websites.regenerate', $website) }}">
                    @csrf
                    <button type="submit" class="btn secondary">Regenerate ({{ config('sites.generation_cost') }} credit)</button>
                </form>
                <form method="POST" action="{{ route('websites.destroy', $website) }}"
                      onsubmit="return confirm('Delete this website permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn danger">Delete</button>
                </form>
            </div>
        </div>
    @endunless
@endsection
