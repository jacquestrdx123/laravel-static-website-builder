@extends('layouts.app')

@section('title', 'Newsletter — '.$website->name)

@section('content')
    <h1>{{ $newsletter['subject'] }}</h1>
    <p class="muted">Topic: {{ $newsletter['topic'] }} · Status: {{ $newsletter['status'] }}
        @if ($newsletter['sent_at'])
            · Sent to {{ $newsletter['recipient_count'] }} recipient(s)
        @endif
    </p>

    @if ($newsletter['status'] !== 'sent')
        <form method="POST" action="{{ route('websites.newsletters.send', [$website, $newsletter['uuid']]) }}" style="margin-bottom: 1rem;">
            @csrf
            <button type="submit">Send to all subscribers</button>
        </form>
    @endif

    <div class="card">
        <iframe class="preview" style="min-height: 480px;" srcdoc="{{ e($html) }}" title="Newsletter preview"></iframe>
    </div>

    <p><a href="{{ route('websites.newsletters.index', $website) }}">← Back</a></p>
@endsection
