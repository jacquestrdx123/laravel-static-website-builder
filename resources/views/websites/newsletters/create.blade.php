@extends('layouts.app')

@section('title', 'Generate newsletter — '.$website->name)

@section('content')
    <h1>Generate newsletter</h1>
    <p class="muted">
        Professional email service with advanced reporting — opens, link clicks, and delivery insights.
        AI drafts are included with newsletter hosting.
    </p>

    <div class="card">
        @if ($hasNewsletterHosting)
            <p class="hint" style="margin-top:0">Newsletter hosting is active this month. Generating a draft costs no extra credits.</p>
        @else
            <p style="margin-top:0">
                First draft this period activates newsletter hosting:
                <strong>{{ $hostingCredits }} credits</strong> ({{ $hostingZar }}/month),
                including {{ number_format($includedEmails) }} free emails.
            </p>
        @endif

        <form method="POST" action="{{ route('websites.newsletters.store', $website) }}">
            @csrf
            <label>
                Topic
                <input type="text" name="topic" value="{{ old('topic') }}" required maxlength="255">
            </label>
            <label>
                Angle (optional)
                <input type="text" name="angle" value="{{ old('angle') }}" maxlength="500" placeholder="e.g. summer sale, new product launch">
            </label>
            <button type="submit">
                @if ($hasNewsletterHosting)
                    Generate draft (included)
                @else
                    Activate hosting &amp; generate ({{ $hostingCredits }} credits)
                @endif
            </button>
        </form>
    </div>

    <p><a href="{{ route('websites.newsletters.index', $website) }}">← Back</a></p>
@endsection
