@extends('layouts.app')

@section('title', 'AI Website Builder')

@section('content')
    <section class="hero">
        <a class="brand-mark reveal reveal-d1" href="{{ url('/') }}">Site<span>Forge</span></a>
        <p class="eyebrow reveal reveal-d2">Describe · Generate · Publish</p>
        <h1 class="reveal reveal-d3">Your website, built by AI.</h1>
        <p class="lede reveal reveal-d4">
            Upload your photos, tell us about your business, flip a few toggles —
            and get a fast, hand-crafted-quality static website in minutes.
        </p>
        <div class="hero-cta reveal reveal-d5">
            @auth
                <a class="btn" href="{{ route('websites.create') }}">Build a website</a>
                <a class="btn secondary" href="{{ route('dashboard') }}">Go to dashboard</a>
            @else
                <a class="btn" href="{{ route('register') }}">Get started — first site free</a>
                <a class="btn secondary" href="{{ route('pricing') }}">See pricing</a>
            @endauth
        </div>
    </section>

    <section class="section-block" id="how-it-works">
        <div class="section-head reveal-on-scroll">
            <p class="eyebrow">How it works</p>
            <h2>Three steps from brief to live site</h2>
            <p>No page builders, no agency wait — a clean static site you can publish in one click.</p>
        </div>

        <div class="step-grid">
            <article class="step-card reveal-on-scroll">
                <span class="step-num">01</span>
                <h3>Describe &amp; upload</h3>
                <p>Tell us what you do and add up to {{ config('sites.max_images') }} photos.
                    The AI looks at them and designs around your images.</p>
            </article>
            <article class="step-card reveal-on-scroll">
                <span class="step-num">02</span>
                <h3>Generate &amp; preview</h3>
                <p>A complete static website — pure HTML, CSS and vanilla JavaScript.
                    No frameworks, no bloat, loads instantly.</p>
            </article>
            <article class="step-card reveal-on-scroll">
                <span class="step-num">03</span>
                <h3>Publish &amp; host</h3>
                <p>One click puts your site live on your own subdomain,
                    served with automatic HTTPS.</p>
            </article>
        </div>
    </section>
@endsection
