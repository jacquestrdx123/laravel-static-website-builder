@extends('layouts.app')

@section('title', 'AI Website Builder')

@section('content')
    <div style="text-align:center; padding: 4rem 1rem;">
        <h1 style="font-size: 2.6rem; margin-bottom: .5rem;">Your website, built by AI.</h1>
        <p class="muted" style="font-size: 1.2rem; max-width: 560px; margin: 0 auto 2rem;">
            Upload your photos, tell us about your business, flip a few toggles —
            and get a fast, hand-crafted-quality static website in minutes.
        </p>
        @auth
            <a class="btn" style="font-size:1.1rem" href="{{ route('websites.create') }}">Build a website</a>
        @else
            <a class="btn" style="font-size:1.1rem" href="{{ route('register') }}">Get started — first site free</a>
        @endauth
    </div>

    <div class="grid-2" style="grid-template-columns: repeat(3, 1fr);">
        <div class="card">
            <h3 style="margin-top:0">1. Describe &amp; upload</h3>
            <p class="muted">Tell us what you do and add up to {{ config('sites.max_images') }} photos.
                The AI actually looks at them and designs around your images.</p>
        </div>
        <div class="card">
            <h3 style="margin-top:0">2. Generate &amp; preview</h3>
            <p class="muted">A complete static website — pure HTML, CSS and vanilla JavaScript.
                No frameworks, no bloat, loads instantly.</p>
        </div>
        <div class="card">
            <h3 style="margin-top:0">3. Publish &amp; host</h3>
            <p class="muted">One click puts your site live on your own subdomain,
                served with automatic HTTPS.</p>
        </div>
    </div>
@endsection
