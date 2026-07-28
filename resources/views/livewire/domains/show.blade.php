<div>
    <h1>{{ $domain->domain }}</h1>
    @include('domains.partials.nav')

    <div class="card">
        <h2 style="margin-top:0">Overview</h2>
        <table>
            <tr><th>Status</th><td><span class="status-badge status-{{ $domain->status === 'active' ? 'ready' : ($domain->status === 'pending' ? 'queued' : 'failed') }}">{{ ucfirst($domain->status) }}</span></td></tr>
            <tr><th>Registered</th><td>{{ $domain->registered_at?->format('Y-m-d') ?? '—' }}</td></tr>
            <tr><th>Expires</th><td>{{ $domain->expires_at?->format('Y-m-d') ?? '—' }}</td></tr>
            <tr><th>Period</th><td>{{ $domain->regperiod }} year(s)</td></tr>
            <tr><th>ID protection</th><td>{{ $domain->id_protection ? 'Enabled' : 'Disabled' }}</td></tr>
            <tr><th>Registrar lock</th><td>{{ $domain->registrar_locked ? 'Locked' : 'Unlocked' }}</td></tr>
            <tr><th>Linked website</th><td>{{ $domain->website?->name ?? 'Not linked' }}</td></tr>
        </table>

        @if ($information)
            <h3>Registrar information</h3>
            <pre style="white-space:pre-wrap;background:#faf9f6;padding:1rem;border-radius:6px;border:1px solid var(--line)">{{ json_encode($information, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>

    <div class="card">
        <h2 style="margin-top:0">Link to website</h2>
        @if ($domain->website_id)
            <p class="muted">Currently linked to <strong>{{ $domain->website->name }}</strong>.</p>
            <form method="POST" action="{{ route('domains.unlink', $domain) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn secondary">Unlink</button>
            </form>
        @else
            <form method="POST" action="{{ route('domains.link', $domain) }}" class="actions">
                @csrf
                <select name="website_id" required>
                    <option value="">Choose website…</option>
                    @foreach ($websites as $website)
                        <option value="{{ $website->id }}">{{ $website->name }}</option>
                    @endforeach
                </select>
                <button type="submit">Link domain</button>
            </form>
        @endif
        <p class="hint">Linking sets the website's custom domain so Caddy can serve it with on-demand TLS.</p>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Renew domain</h2>
        @if ($renewCredits)
            <p class="muted">Renewal costs from <strong>{{ $renewCredits }} credit{{ $renewCredits === 1 ? '' : 's' }}</strong> per year.</p>
        @endif
        <form method="POST" action="{{ route('domains.renew', $domain) }}" class="actions">
            @csrf
            <select name="regperiod">
                @for ($year = 1; $year <= 10; $year++)
                    <option value="{{ $year }}">{{ $year }} year(s)</option>
                @endfor
            </select>
            <button type="submit">Renew with credits</button>
        </form>
    </div>

    <div class="actions">
        <form method="POST" action="{{ route('domains.sync', $domain) }}">
            @csrf
            <button type="submit" class="btn secondary">Sync with registrar</button>
        </form>
        @if ($domain->status === 'pending')
            <form method="POST" action="{{ route('domains.settings.transfer-sync', $domain) }}">
                @csrf
                <button type="submit" class="btn secondary">Transfer sync</button>
            </form>
        @endif
    </div>
</div>
