@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
    <div class="auth-split">
        <div class="reveal reveal-d1">
            <p class="eyebrow">Get started</p>
            <h1 style="font-size: clamp(1.85rem, 4vw, 2.4rem); margin-bottom:.75rem;">
                Get your business online today.
            </h1>
            <p class="muted" style="font-size:1.05rem; margin-bottom:1.75rem;">
                No designers, no developers, no monthly page builders.
                Describe your business, add your photos, and our AI builds you a real website.
            </p>

            <div style="display:flex; gap:.7rem; margin:1.1rem 0;">
                <span class="step-num" style="margin:0; flex:0 0 auto;">✓</span>
                <span><strong>Your first site is on us.</strong> Sign up and get 1 free AI credit — enough to generate a complete website.</span>
            </div>
            <div style="display:flex; gap:.7rem; margin:1.1rem 0;">
                <span class="step-num" style="margin:0; flex:0 0 auto;">✓</span>
                <span><strong>Designed around your photos.</strong> The AI looks at your images and builds the design to fit them.</span>
            </div>
            <div style="display:flex; gap:.7rem; margin:1.1rem 0;">
                <span class="step-num" style="margin:0; flex:0 0 auto;">✓</span>
                <span><strong>Fast, clean, and yours.</strong> Pure HTML, CSS and JavaScript — no bloat, great for SEO.</span>
            </div>
            <div style="display:flex; gap:.7rem; margin:1.1rem 0;">
                <span class="step-num" style="margin:0; flex:0 0 auto;">✓</span>
                <span><strong>Live in one click.</strong> Publish to your subdomain with automatic HTTPS.</span>
            </div>
        </div>

        <div class="card reveal reveal-d3">
            <div class="flash ok" style="margin-bottom:1rem;">Includes 1 free website generation</div>
            <h2 style="margin-top:0">Create your account</h2>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <label for="name">Your name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                       required autofocus autocomplete="name">
                @error('name')<div class="error">{{ $message }}</div>@enderror

                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="email">
                @error('email')<div class="error">{{ $message }}</div>@enderror

                <label for="password">Password <span class="hint">(min. 8 characters)</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror

                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       required autocomplete="new-password">

                <div style="margin-top: 1.5rem;">
                    <button type="submit" style="width:100%">Create account &amp; get my free credit</button>
                </div>

                <p class="hint" style="text-align:center; margin-top: 1rem;">
                    Already have an account? <a href="{{ route('login') }}">Log in</a>
                </p>
            </form>
        </div>
    </div>
@endsection
