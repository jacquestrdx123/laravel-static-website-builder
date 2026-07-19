<div>
    <h1>Newsletters</h1>
    <p class="muted">AI-generated email campaigns stored in your website vault.</p>

    <div class="actions" style="margin-bottom: 1rem;">
        <a class="btn" href="{{ route('websites.newsletters.create', $website) }}">Generate newsletter</a>
        <a class="btn secondary" href="{{ route('websites.subscribers.index', $website) }}">Subscribers</a>
    </div>

    <div class="card">
        @if (empty($newsletters))
            <p class="muted">No newsletters yet.</p>
        @else
            <table>
                <thead><tr><th>Topic</th><th>Subject</th><th>Status</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @foreach ($newsletters as $newsletter)
                    <tr wire:key="nl-{{ $newsletter['uuid'] }}">
                        <td>{{ $newsletter['topic'] }}</td>
                        <td>{{ $newsletter['subject'] }}</td>
                        <td>{{ $newsletter['status'] }}</td>
                        <td class="muted">{{ \Illuminate\Support\Str::before($newsletter['created_at'], 'T') }}</td>
                        <td><a href="{{ route('websites.newsletters.show', [$website, $newsletter['uuid']]) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p><a href="{{ route('websites.show', $website) }}">← Back to website</a></p>
</div>
