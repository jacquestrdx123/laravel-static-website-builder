@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
    <style>
        .signup-wrap {
            display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem;
            align-items: start; max-width: 900px; margin: 2rem auto;
        }
        @media (max-width: 760px) { .signup-wrap { grid-template-columns: 1fr; } }
        .signup-pitch h1 { font-size: 2.2rem; line-height: 1.2; }
        .signup-pitch .perk { display: flex; gap: .7rem; margin: 1.1rem 0; }
        .signup-pitch .perk .tick {
            flex: 0 0 auto; width: 1.5rem; height: 1.5rem; border-radius: 50%;
            background: var(--accent); color: var(--accent-ink);
            display: grid; place-items: center; font-size: .85rem;
        }
        .free-credit-banner {
            background: #e7f3ee; border: 1px solid #bfe0d2; color: var(--ok);
            border-radius: var(--radius); padding: .7rem 1rem; margin-bottom: 1rem;
            font-size: .95rem;
        }
    </style>

    <div class="signup-wrap">
        <div class="signup-pitch">
            <h1>Get your business online today.</h1>
            <p class="muted">No designers, no developers, no monthly page builders.
                Describe your business, add your photos, and our AI builds you a real website.</p>

            <div class="perk"><span class="tick">✓</span>
                <span><strong>Your first site is on us.</strong> Sign up and get 1 free AI credit — enough to generate a complete website.</span></div>
            <div class="perk"><span class="tick">✓</span>
                <span><strong>Designed around your photos.</strong> The AI actually looks at your images and builds the design to fit them.</span></div>
            <div class="perk"><span class="tick">✓</span>
                <span><strong>Fast, clean, and yours.</strong> Pure HTML, CSS and JavaScript — no bloat, loads instantly, great for SEO.</span></div>
            <div class="perk"><span class="tick">✓</span>
                <span><strong>Live in one click.</strong> Publish to your own subdomain with automatic HTTPS. Custom domains supported.</span></div>
        </div>

        <div class="card">
            <div class="free-credit-banner">🎁 Includes 1 free website generation</div>
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
