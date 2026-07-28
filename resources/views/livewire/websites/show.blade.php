<div @if (in_array($status, [\App\Models\Website::STATUS_QUEUED, \App\Models\Website::STATUS_GENERATING], true)) wire:poll.4s="refreshStatus" @endif>
    <div class="actions" style="justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="margin:0">{{ $website->name }}</h1>
        <span class="status-badge status-{{ $status }}">{{ $status }}</span>
    </div>

    @if (in_array($status, [\App\Models\Website::STATUS_QUEUED, \App\Models\Website::STATUS_GENERATING], true))
        <div class="card" style="text-align:center; padding: 3rem;">
            <p style="font-size:1.15rem"><span class="spinner"></span> The AI is building your website…</p>
            <p class="muted">This usually takes a few minutes. This page refreshes automatically.</p>
        </div>
    @endif

    @if ($status === \App\Models\Website::STATUS_FAILED)
        <div class="flash err">
            <strong>Generation failed.</strong> {{ $error }}<br>
            <span class="hint">Your credit has been refunded — you can try again below.</span>
        </div>
    @endif

    @if ($website->isGenerated())
        <div class="card">
            <div class="actions" style="justify-content: space-between; margin-bottom: .8rem;">
                <strong>Preview</strong>
                <span class="actions">
                    <a class="btn secondary" href="{{ route('websites.content.edit', $website) }}">✏ Edit content</a>
                    <a class="btn secondary" target="_blank" href="{{ $website->previewUrl() }}">Open full screen ↗</a>
                </span>
            </div>
            <iframe class="preview" src="{{ $website->previewUrl() }}" title="Website preview"></iframe>
        </div>

        <div class="card">
            @if ($status === \App\Models\Website::STATUS_PUBLISHED)
                <p>
                    <strong>Live at:</strong>
                    <a href="https://{{ $website->hostname() }}" target="_blank">https://{{ $website->hostname() }}</a>
                    @if ($website->published_at)
                        <span class="hint">(published {{ $website->published_at->diffForHumans() }})</span>
                    @endif
                </p>
                <div class="actions">
                    <button type="button" class="btn secondary" wire:click="unpublish" wire:confirm="Unpublish this site?">Unpublish</button>
                </div>
            @else
                <p><strong>Happy with it?</strong> Publish it to <code>{{ $website->hostname() }}</code>.</p>
                <button type="button" class="btn" wire:click="publish">Publish website</button>
            @endif
        </div>

        <div class="card">
            <strong>Marketing services</strong>
            <p class="hint">Newsletters and posters use prepaid credits. Content history is stored in your private website vault.</p>
            <div class="actions">
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

    @unless (in_array($status, [\App\Models\Website::STATUS_QUEUED, \App\Models\Website::STATUS_GENERATING], true))
        <div class="card">
            <strong>Not quite right?</strong>
            <p class="hint">Regenerating uses the same brief and photos but produces a fresh design.
                Costs {{ config('sites.generation_cost') }} credit.</p>
            <div class="actions">
                <button type="button" class="btn secondary" wire:click="regenerate">
                    Regenerate ({{ config('sites.generation_cost') }} credit)
                </button>
                <button type="button" class="btn danger" wire:click="destroyWebsite"
                        wire:confirm="Delete this website permanently?">Delete</button>
            </div>
        </div>
    @endunless
</div>
