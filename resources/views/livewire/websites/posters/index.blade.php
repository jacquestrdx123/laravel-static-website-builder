<div>
    <h1>Marketing posters</h1>
    <p class="muted">AI-generated posters with PNG export, stored in your website vault.</p>

    <div class="actions" style="margin-bottom: 1rem;">
        <a class="btn" href="{{ route('websites.posters.create', $website) }}">Generate poster</a>
    </div>

    <div class="card">
        @if (empty($posters))
            <p class="muted">No posters yet.</p>
        @else
            <table>
                <thead><tr><th>Format</th><th>Status</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @foreach ($posters as $poster)
                    <tr wire:key="poster-{{ $poster['uuid'] }}">
                        <td>{{ $poster['format'] }}</td>
                        <td>{{ $poster['status'] }}</td>
                        <td class="muted">{{ \Illuminate\Support\Str::before($poster['created_at'], 'T') }}</td>
                        <td><a href="{{ route('websites.posters.show', [$website, $poster['uuid']]) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p><a href="{{ route('websites.show', $website) }}">← Back to website</a></p>
</div>
