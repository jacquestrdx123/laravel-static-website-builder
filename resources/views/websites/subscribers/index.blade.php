@extends('layouts.app')

@section('title', 'Subscribers — '.$website->name)

@section('content')
    <h1>Newsletter subscribers</h1>
    <p class="muted">People who signed up from your published site or were added manually.</p>

    <div class="card">
        <h2 style="margin-top:0">Add subscriber</h2>
        <form method="POST" action="{{ route('websites.subscribers.store', $website) }}" class="actions">
            @csrf
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="name" placeholder="Name (optional)">
            <button type="submit">Add</button>
        </form>
    </div>

    <div class="card">
        @if ($subscribers->isEmpty())
            <p class="muted">No subscribers yet.</p>
        @else
            <table>
                <thead><tr><th>Email</th><th>Name</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($subscribers as $subscriber)
                    <tr>
                        <td>{{ $subscriber->email }}</td>
                        <td>{{ $subscriber->name ?? '—' }}</td>
                        <td>{{ $subscriber->status }}</td>
                        <td>
                            <form method="POST" action="{{ route('websites.subscribers.destroy', [$website, $subscriber]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p><a href="{{ route('websites.show', $website) }}">← Back to website</a></p>
@endsection
