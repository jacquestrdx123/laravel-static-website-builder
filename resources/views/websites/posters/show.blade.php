@extends('layouts.app')

@section('title', 'Poster — '.$website->name)

@section('content')
    <h1>Poster</h1>
    <p class="muted">Format: {{ $poster['format'] }} · Status: {{ $poster['status'] }}</p>

    <div class="actions" style="margin-bottom: 1rem;">
        @if ($poster['png_path'])
            <a class="btn" href="{{ route('websites.posters.download', [$website, $poster['uuid']]) }}">Download PNG</a>
        @endif
    </div>

    <div class="card">
        @if ($poster['png_path'])
            <img src="{{ route('websites.posters.download', [$website, $poster['uuid']]) }}" alt="Poster" style="max-width:100%; height:auto;">
        @else
            <iframe class="preview" style="min-height: 480px;" srcdoc="{{ e($html) }}" title="Poster preview"></iframe>
        @endif
    </div>

    <p><a href="{{ route('websites.posters.index', $website) }}">← Back</a></p>
@endsection
