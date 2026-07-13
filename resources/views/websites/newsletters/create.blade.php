@extends('layouts.app')

@section('title', 'Generate newsletter — '.$website->name)

@section('content')
    <h1>Generate newsletter</h1>
    <p class="muted">Costs {{ $creditCost }} credits. Content is saved to your website vault.</p>

    <div class="card">
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
            <button type="submit">Generate ({{ $creditCost }} credits)</button>
        </form>
    </div>

    <p><a href="{{ route('websites.newsletters.index', $website) }}">← Back</a></p>
@endsection
