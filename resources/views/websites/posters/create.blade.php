@extends('layouts.app')

@section('title', 'Generate poster — '.$website->name)

@section('content')
    <h1>Generate poster</h1>
    <p class="muted">Costs {{ $creditCost }} credits. HTML and PNG are saved to your website vault.</p>

    <div class="card">
        <form method="POST" action="{{ route('websites.posters.store', $website) }}">
            @csrf
            <label>
                Brief
                <textarea name="brief" rows="4" required maxlength="1000">{{ old('brief') }}</textarea>
            </label>
            <label>
                Format
                <select name="format" required>
                    @foreach ($formats as $key => $format)
                        <option value="{{ $key }}" @selected(old('format') === $key)>{{ $format['label'] }} ({{ $format['width'] }}×{{ $format['height'] }})</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Generate ({{ $creditCost }} credits)</button>
        </form>
    </div>

    <p><a href="{{ route('websites.posters.index', $website) }}">← Back</a></p>
@endsection
