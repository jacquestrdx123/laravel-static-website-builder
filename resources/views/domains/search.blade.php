@extends('layouts.app')

@section('title', 'Search domains')

@section('content')
    <h1>Search domains</h1>
    <p class="muted">Check availability and register a custom domain for your website.</p>

    <div class="card">
        <form method="POST" action="{{ route('domains.search.perform') }}">
            @csrf
            <label for="searchTerm">Domain name</label>
            <input type="text" id="searchTerm" name="searchTerm" value="{{ old('searchTerm', $searchTerm) }}" placeholder="mybusiness" required>
            @error('searchTerm')<div class="error">{{ $message }}</div>@enderror
            <p class="hint">Enter a name without the TLD, or a full domain like mybusiness.co.za</p>
            <div class="actions" style="margin-top:1rem">
                <button type="submit">Search availability</button>
                <a class="btn secondary" href="{{ route('domains.transfer') }}">Transfer a domain</a>
                <a class="btn secondary" href="{{ route('domains.index') }}">My domains</a>
            </div>
        </form>
    </div>

    @if (! empty($results))
        <div class="card">
            <h2 style="margin-top:0">Results for “{{ $searchTerm }}”</h2>
            <table>
                <thead>
                <tr>
                    <th>Domain</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($results as $result)
                    <tr>
                        <td>{{ $result['domain'] }}</td>
                        <td>
                            @if ($result['available'])
                                <span class="status-badge status-ready">Available</span>
                            @else
                                <span class="status-badge status-failed">Taken</span>
                            @endif
                        </td>
                        <td>{{ $result['price'] ?? '—' }}</td>
                        <td>
                            @if ($result['available'])
                                <a class="btn" style="padding:.35rem .9rem" href="{{ route('domains.register', ['domain' => $result['domain']]) }}">Register</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
