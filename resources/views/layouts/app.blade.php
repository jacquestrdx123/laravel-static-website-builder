<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@isset($title){{ $title }}@else@yield('title', 'SiteForge')@endisset — AI Website Builder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --background: #fafafa;
            --foreground: #0f172a;
            --brand: #0891b2;
            --brand-soft: rgba(8, 145, 178, 0.12);
            --surface: #ffffff;
            --muted: #64748b;
            --line: rgba(15, 23, 42, 0.1);
            --danger: #b3372f;
            --ok: #0f766e;
            --warn: #a06a00;
            --radius: 1rem;
            --radius-pill: 999px;
            --font-sans: "Geist", ui-sans-serif, system-ui, sans-serif;
            --font-mono: "Geist Mono", ui-monospace, monospace;

            /* Backward-compatible aliases used across existing views */
            --ink: var(--foreground);
            --ink-soft: var(--muted);
            --paper: var(--background);
            --card: var(--surface);
            --accent: var(--brand);
            --accent-ink: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--background);
            color: var(--foreground);
            font: 16px/1.6 var(--font-sans);
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--brand); }

        .atmosphere {
            pointer-events: none;
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
        }
        .atmosphere .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(72px);
        }
        .atmosphere .orb-a {
            left: -20%;
            top: 0;
            width: 28rem;
            height: 28rem;
            background: rgba(8, 145, 178, 0.1);
        }
        .atmosphere .orb-b {
            right: -18%;
            top: 28%;
            width: 24rem;
            height: 24rem;
            background: rgba(8, 145, 178, 0.06);
        }

        .shell { position: relative; z-index: 1; min-height: 100vh; display: flex; flex-direction: column; }

        nav.top {
            position: sticky;
            top: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .85rem 1.5rem;
            background: color-mix(in srgb, var(--surface) 82%, transparent);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            flex-wrap: wrap;
        }
        nav.top .brand {
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: -0.02em;
            text-decoration: none;
            color: var(--foreground);
        }
        nav.top .brand span { color: var(--brand); }
        nav.top a.link {
            text-decoration: none;
            color: var(--muted);
            white-space: nowrap;
            font-size: .925rem;
            font-weight: 500;
            transition: color .15s ease;
        }
        nav.top a.link:hover,
        nav.top a.link[aria-current="page"] { color: var(--foreground); }
        nav.top .spacer { flex: 1; }
        nav.top .nav-links { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
        nav.top .nav-toggle {
            display: none;
            background: transparent;
            color: var(--foreground);
            border: 1px solid var(--line);
            border-radius: var(--radius-pill);
            padding: .35rem .9rem;
            font: inherit;
            cursor: pointer;
        }
        .credits-pill {
            background: var(--brand);
            color: #fff;
            border-radius: var(--radius-pill);
            padding: .2rem .85rem;
            font-size: .85rem;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
        }
        .credits-pill:hover { opacity: .92; color: #fff; }

        main {
            width: 100%;
            max-width: 72rem;
            margin: 0 auto;
            padding: 2rem 1.25rem 3.5rem;
            flex: 1;
        }

        h1, h2, h3 {
            letter-spacing: -0.025em;
            font-weight: 600;
            line-height: 1.2;
        }
        h1 { font-size: 1.85rem; margin: 0 0 1rem; }
        h2 { font-size: 1.35rem; }
        h3 { font-size: 1.1rem; }

        .eyebrow {
            font-family: var(--font-mono);
            font-size: .7rem;
            font-weight: 500;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--brand);
            margin: 0 0 .75rem;
        }

        .card {
            background: color-mix(in srgb, var(--surface) 88%, transparent);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            backdrop-filter: blur(2px);
        }
        .card:hover { border-color: color-mix(in srgb, var(--brand) 28%, var(--line)); }

        .muted { color: var(--muted); }
        .flash {
            border-radius: var(--radius);
            padding: .8rem 1.1rem;
            margin-bottom: 1.25rem;
        }
        .flash.ok {
            background: color-mix(in srgb, var(--ok) 12%, var(--surface));
            border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);
            color: var(--ok);
        }
        .flash.err {
            background: color-mix(in srgb, var(--danger) 10%, var(--surface));
            border: 1px solid color-mix(in srgb, var(--danger) 28%, transparent);
            color: var(--danger);
        }

        label { display: block; font-weight: 600; margin: 1rem 0 .3rem; font-size: .925rem; }
        input[type=text], input[type=email], input[type=password], textarea, select {
            width: 100%;
            padding: .7rem .85rem;
            border: 1px solid var(--line);
            border-radius: .75rem;
            font: inherit;
            background: var(--surface);
            color: var(--foreground);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: color-mix(in srgb, var(--brand) 55%, var(--line));
            box-shadow: 0 0 0 3px var(--brand-soft);
        }
        textarea { min-height: 6rem; resize: vertical; }
        .hint { font-size: .85rem; color: var(--muted); font-weight: normal; }
        .error { color: var(--danger); font-size: .88rem; margin-top: .25rem; }

        .choices { display: flex; flex-wrap: wrap; gap: .5rem; }
        .choices label {
            font-weight: normal;
            margin: 0;
            border: 1px solid var(--line);
            border-radius: var(--radius-pill);
            padding: .35rem .9rem;
            cursor: pointer;
            background: var(--surface);
            user-select: none;
        }
        .choices input { margin-right: .4rem; }
        .choices label:has(input:checked) {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
        }

        button, .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            background: var(--foreground);
            color: var(--background);
            border: 0;
            border-radius: var(--radius-pill);
            padding: .7rem 1.4rem;
            font: inherit;
            font-size: .925rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s ease, border-color .15s ease, color .15s ease, background .15s ease;
        }
        button:hover, .btn:hover { opacity: .92; color: var(--background); }
        .btn.secondary, button.secondary {
            background: transparent;
            color: var(--foreground);
            border: 1px solid var(--line);
        }
        .btn.secondary:hover, button.secondary:hover {
            border-color: var(--brand);
            color: var(--brand);
            opacity: 1;
        }
        .btn.danger { background: var(--danger); color: #fff; }
        .btn.brand { background: var(--brand); color: #fff; }
        .btn.brand:hover { color: #fff; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .7rem .5rem; border-bottom: 1px solid var(--line); }
        th { color: var(--muted); font-weight: 500; font-size: .85rem; }

        .status-badge {
            border-radius: var(--radius-pill);
            padding: .15rem .7rem;
            font-size: .78rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .status-draft, .status-queued { background: #fef3c7; color: var(--warn); }
        .status-generating { background: #e0f2fe; color: #0369a1; }
        .status-ready { background: #ccfbf1; color: var(--ok); }
        .status-published { background: var(--brand); color: #fff; }
        .status-failed { background: #fee2e2; color: var(--danger); }

        iframe.preview {
            width: 100%;
            height: 70vh;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: #fff;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
        .actions { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }

        .spinner {
            display: inline-block;
            width: 1em;
            height: 1em;
            border: 2px solid var(--line);
            border-top-color: var(--brand);
            border-radius: 50%;
            vertical-align: -.15em;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Landing / auth helpers */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 1.25rem;
            padding: 3.5rem 1rem 3rem;
            max-width: 42rem;
            margin: 0 auto;
        }
        .hero .brand-mark {
            font-size: 1.35rem;
            font-weight: 600;
            letter-spacing: -0.03em;
            color: var(--foreground);
            text-decoration: none;
        }
        .hero .brand-mark span { color: var(--brand); }
        .hero h1 {
            font-size: clamp(2.15rem, 5vw, 3.1rem);
            margin: 0;
            text-wrap: balance;
            line-height: 1.1;
        }
        .hero .lede {
            margin: 0;
            max-width: 34rem;
            font-size: 1.125rem;
            line-height: 1.65;
            color: var(--muted);
            text-wrap: pretty;
        }
        .hero-cta { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; padding-top: .35rem; }
        .hero-cta .btn { min-height: 3rem; padding-inline: 1.75rem; }

        .section-block {
            border-top: 1px solid var(--line);
            margin-top: 1rem;
            padding: 3rem 0 1rem;
        }
        .section-block .section-head {
            text-align: center;
            max-width: 32rem;
            margin: 0 auto 2rem;
        }
        .section-block .section-head h2 { margin: .35rem 0; font-size: 1.75rem; }
        .section-block .section-head p { margin: 0; color: var(--muted); }

        .step-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        @media (max-width: 800px) { .step-grid { grid-template-columns: 1fr; } }
        .step-card {
            background: color-mix(in srgb, var(--surface) 90%, transparent);
            border: 1px solid var(--line);
            border-radius: 1.25rem;
            padding: 1.5rem;
            min-height: 100%;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .step-card:hover {
            border-color: color-mix(in srgb, var(--brand) 40%, var(--line));
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            transform: translateY(-2px);
        }
        .step-card h3 { margin: 0 0 .5rem; }
        .step-card p { margin: 0; color: var(--muted); font-size: .95rem; }
        .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: var(--radius-pill);
            background: var(--brand-soft);
            color: var(--brand);
            font-family: var(--font-mono);
            font-size: .75rem;
            font-weight: 600;
            margin-bottom: .85rem;
        }

        .auth-panel {
            max-width: 28rem;
            margin: 2.5rem auto 1rem;
        }
        .auth-panel .card { margin-bottom: 0; }
        .auth-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
            align-items: start;
            max-width: 56rem;
            margin: 1.5rem auto;
        }
        @media (max-width: 760px) { .auth-split { grid-template-columns: 1fr; } }

        .site-footer {
            border-top: 1px solid var(--line);
            padding: 1.75rem 1.5rem;
            color: var(--muted);
            font-size: .875rem;
        }
        .site-footer .inner {
            max-width: 72rem;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
        }
        .site-footer a { color: var(--muted); text-decoration: none; }
        .site-footer a:hover { color: var(--foreground); }

        /* Motion */
        .reveal {
            opacity: 0;
            transform: translateY(14px);
            animation: reveal-up .7s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .reveal-d1 { animation-delay: .05s; }
        .reveal-d2 { animation-delay: .14s; }
        .reveal-d3 { animation-delay: .23s; }
        .reveal-d4 { animation-delay: .32s; }
        .reveal-d5 { animation-delay: .41s; }
        @keyframes reveal-up {
            to { opacity: 1; transform: none; }
        }
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity .55s cubic-bezier(0.22, 1, 0.36, 1), transform .55s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .reveal-on-scroll.is-visible {
            opacity: 1;
            transform: none;
        }
        @media (prefers-reduced-motion: reduce) {
            .reveal, .reveal-on-scroll {
                animation: none !important;
                transition: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
            .step-card:hover { transform: none; }
        }

        @media (max-width: 900px) {
            nav.top .nav-toggle { display: inline-flex; margin-left: auto; }
            nav.top .nav-links {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: .35rem;
                padding-top: .5rem;
            }
            nav.top.nav-open .nav-links { display: flex; }
            nav.top .nav-links a.link,
            nav.top .nav-links .credits-pill,
            nav.top .nav-links form { width: 100%; }
            nav.top .nav-links form button { width: 100%; }
            nav.top .spacer { display: none; }
        }

        @yield('page_styles')
    </style>
</head>
<body>
<div class="atmosphere" aria-hidden="true">
    <div class="orb orb-a"></div>
    <div class="orb orb-b"></div>
</div>
<div class="shell">
@php
    $mainNavItems = [
        ['route' => 'dashboard', 'href' => route('dashboard'), 'label' => 'Dashboard'],
        ['route' => 'pricing', 'href' => route('pricing'), 'label' => 'Pricing'],
        ['route' => 'websites.index', 'href' => route('websites.index'), 'label' => 'My Websites'],
        ['route' => 'domains.index', 'href' => route('domains.index'), 'label' => 'My Domains'],
        ['route' => null, 'href' => url('/posters'), 'label' => 'My Posters'],
        ['route' => null, 'href' => url('/newsletters'), 'label' => 'My Newsletters'],
    ];
@endphp
<nav class="top" id="top-nav">
    <a class="brand" href="{{ url('/') }}">Site<span>Forge</span></a>
    @auth
        <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="main-nav-links">Menu</button>
        <div class="nav-links" id="main-nav-links">
            @foreach ($mainNavItems as $item)
                <a class="link"
                   href="{{ $item['href'] }}"
                   @if ($item['route'] && request()->routeIs($item['route'], $item['route'].'.*')) aria-current="page" @endif
                >{{ $item['label'] }}</a>
            @endforeach
            <span class="spacer"></span>
            <a class="credits-pill" href="{{ route('billing.index') }}">{{ auth()->user()->ai_credits }} credits</a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn secondary" style="padding:.35rem 1rem">Log out</button>
            </form>
        </div>
    @else
        <span class="spacer"></span>
        <a class="link" href="{{ route('pricing') }}">Pricing</a>
        <a class="link" href="{{ route('login') }}">Log in</a>
        <a class="btn" style="padding:.4rem 1.1rem" href="{{ route('register') }}">Sign up</a>
    @endauth
</nav>

<main>
    @if (session('status'))
        <div class="flash ok">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="flash err">{{ session('error') }}</div>
    @endif
    @yield('content')
</main>

<footer class="site-footer">
    <div class="inner">
        <p style="margin:0"><strong style="color:var(--foreground)">Site<span style="color:var(--brand)">Forge</span></strong> · AI websites, hosted for you</p>
        <div class="actions">
            <a href="{{ route('pricing') }}">Pricing</a>
            @guest
                <a href="{{ route('login') }}">Log in</a>
            @else
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @endguest
        </div>
    </div>
</footer>
</div>

@auth
<script>
    (function () {
        const nav = document.getElementById('top-nav');
        const toggle = document.getElementById('nav-toggle');
        if (!nav || !toggle) return;
        toggle.addEventListener('click', function () {
            const open = nav.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    })();
</script>
@endauth
<script>
    (function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.querySelectorAll('.reveal-on-scroll').forEach(function (el) {
                el.classList.add('is-visible');
            });
            return;
        }
        var nodes = document.querySelectorAll('.reveal-on-scroll');
        if (!nodes.length || !('IntersectionObserver' in window)) {
            nodes.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '-40px 0px', threshold: 0.12 });
        nodes.forEach(function (el) { io.observe(el); });
    })();
</script>
</body>
</html>
