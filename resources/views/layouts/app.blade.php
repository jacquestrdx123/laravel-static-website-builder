<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SiteForge') — AI Website Builder</title>
    <style>
        :root {
            --ink: #1a1d23; --ink-soft: #5c6370; --paper: #f6f5f1; --card: #ffffff;
            --accent: #0e7a5f; --accent-ink: #ffffff; --line: #e4e2db;
            --danger: #b3372f; --ok: #0e7a5f; --warn: #a06a00; --radius: 10px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--paper); color: var(--ink);
            font: 16px/1.6 Georgia, 'Times New Roman', serif;
        }
        a { color: var(--accent); }
        nav.top {
            display: flex; align-items: center; gap: 1.5rem; padding: .9rem 1.5rem;
            background: var(--card); border-bottom: 1px solid var(--line);
        }
        nav.top .brand { font-weight: bold; font-size: 1.15rem; text-decoration: none; color: var(--ink); }
        nav.top .brand span { color: var(--accent); }
        nav.top a.link { text-decoration: none; color: var(--ink-soft); }
        nav.top a.link:hover { color: var(--ink); }
        nav.top .spacer { flex: 1; }
        .credits-pill {
            background: var(--accent); color: var(--accent-ink); border-radius: 999px;
            padding: .15rem .8rem; font-size: .85rem; text-decoration: none;
        }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1.25rem; }
        h1 { font-size: 1.7rem; margin: 0 0 1rem; }
        .card {
            background: var(--card); border: 1px solid var(--line);
            border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.25rem;
        }
        .muted { color: var(--ink-soft); }
        .flash { border-radius: var(--radius); padding: .8rem 1.1rem; margin-bottom: 1.25rem; }
        .flash.ok { background: #e7f3ee; border: 1px solid #bfe0d2; color: var(--ok); }
        .flash.err { background: #f9e9e7; border: 1px solid #ecc8c4; color: var(--danger); }
        label { display: block; font-weight: bold; margin: 1rem 0 .3rem; }
        input[type=text], input[type=email], input[type=password], textarea, select {
            width: 100%; padding: .6rem .7rem; border: 1px solid var(--line);
            border-radius: 6px; font: inherit; background: #fff;
        }
        textarea { min-height: 6rem; resize: vertical; }
        .hint { font-size: .85rem; color: var(--ink-soft); font-weight: normal; }
        .error { color: var(--danger); font-size: .88rem; margin-top: .25rem; }
        .choices { display: flex; flex-wrap: wrap; gap: .5rem; }
        .choices label {
            font-weight: normal; margin: 0; border: 1px solid var(--line); border-radius: 999px;
            padding: .35rem .9rem; cursor: pointer; background: #fff; user-select: none;
        }
        .choices input { margin-right: .4rem; }
        .choices label:has(input:checked) { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
        button, .btn {
            display: inline-block; background: var(--accent); color: var(--accent-ink);
            border: 0; border-radius: 6px; padding: .6rem 1.3rem; font: inherit;
            cursor: pointer; text-decoration: none;
        }
        button:hover, .btn:hover { filter: brightness(1.08); }
        .btn.secondary { background: transparent; color: var(--ink); border: 1px solid var(--line); }
        .btn.danger { background: var(--danger); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .6rem .5rem; border-bottom: 1px solid var(--line); }
        .status-badge { border-radius: 999px; padding: .1rem .7rem; font-size: .8rem; white-space: nowrap; }
        .status-draft, .status-queued { background: #efece2; color: var(--warn); }
        .status-generating { background: #eaf0f9; color: #2b5b9e; }
        .status-ready { background: #e7f3ee; color: var(--ok); }
        .status-published { background: var(--accent); color: var(--accent-ink); }
        .status-failed { background: #f9e9e7; color: var(--danger); }
        iframe.preview { width: 100%; height: 70vh; border: 1px solid var(--line); border-radius: var(--radius); background: #fff; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
        .actions { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
        .spinner {
            display: inline-block; width: 1em; height: 1em; border: 2px solid var(--line);
            border-top-color: var(--accent); border-radius: 50%; vertical-align: -.15em;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<nav class="top">
    <a class="brand" href="{{ url('/') }}">Site<span>Forge</span></a>
    @auth
        <a class="link" href="{{ route('dashboard') }}">My websites</a>
        <a class="link" href="{{ route('websites.create') }}">New website</a>
        <span class="spacer"></span>
        <a class="credits-pill" href="{{ route('billing.index') }}">{{ auth()->user()->ai_credits }} credits</a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button type="submit" class="btn secondary" style="padding:.3rem .9rem">Log out</button>
        </form>
    @else
        <span class="spacer"></span>
        <a class="link" href="{{ route('login') }}">Log in</a>
        <a class="btn" style="padding:.3rem .9rem" href="{{ route('register') }}">Sign up</a>
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
</body>
</html>
