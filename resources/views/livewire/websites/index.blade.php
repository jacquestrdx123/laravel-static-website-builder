<div>
    <div class="actions" style="justify-content: space-between; margin-bottom: 1.25rem;">
        <div>
            <p class="eyebrow" style="margin-bottom:.35rem;">Dashboard</p>
            <h1 style="margin:0">My websites</h1>
        </div>
        <a class="btn" href="{{ route('websites.create') }}">+ New website</a>
    </div>

    @if ($websites->isEmpty())
        <div class="card" style="text-align:center; padding: 3rem 1.5rem;">
            <p class="eyebrow">Get started</p>
            <p style="font-size:1.25rem; font-weight:600; margin:.25rem 0 .5rem;">You haven't built a website yet.</p>
            <p class="muted" style="max-width:28rem; margin:0 auto 1.5rem;">Pick a site template, upload a few photos, and let the AI do the rest.</p>
            <a class="btn" href="{{ route('websites.create') }}">Build my first website</a>
        </div>
    @else
        <div class="card">
            <table>
                <thead>
                <tr><th>Name</th><th>Status</th><th>Created</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($websites as $website)
                    <tr wire:key="website-{{ $website->id }}">
                        <td>
                            <a href="{{ route('websites.show', $website) }}">{{ $website->name }}</a>
                            @if ($website->status === \App\Models\Website::STATUS_PUBLISHED)
                                <div class="hint">{{ $website->hostname() }}</div>
                            @endif
                        </td>
                        <td><span class="status-badge status-{{ $website->status }}">{{ $website->status }}</span></td>
                        <td class="muted">{{ $website->created_at->diffForHumans() }}</td>
                        <td style="text-align:right">
                            <a class="btn secondary" href="{{ route('websites.show', $website) }}">Open</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
