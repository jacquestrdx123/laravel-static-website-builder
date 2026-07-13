@extends('layouts.app')

@section('title', 'My domains')

@section('content')
    <h1>My domains</h1>
    <div class="actions" style="margin-bottom:1rem">
        <a class="btn" href="{{ route('domains.search') }}">Search & register</a>
        <a class="btn secondary" href="{{ route('domains.transfer') }}">Transfer domain</a>
    </div>

    @if ($domains->isEmpty())
        <div class="card">
            <p class="muted">You do not own any domains yet.</p>
        </div>
    @else
        <div class="card">
            <table>
                <thead>
                <tr>
                    <th>Domain</th>
                    <th>Status</th>
                    <th>Expires</th>
                    <th>Website</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($domains as $domain)
                    <tr>
                        <td>{{ $domain->domain }}</td>
                        <td><span class="status-badge status-{{ $domain->status === 'active' ? 'ready' : ($domain->status === 'pending' ? 'queued' : 'failed') }}">{{ ucfirst($domain->status) }}</span></td>
                        <td class="muted">{{ $domain->expires_at?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $domain->website?->name ?? '—' }}</td>
                        <td><a href="{{ route('domains.show', $domain) }}">Manage</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
