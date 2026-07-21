<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SiteForge — AI Website Builder')</title>
    <meta name="description" content="@yield('meta_description', 'Describe your business, upload your photos, and get a fast, beautiful website built by AI — with domains, hosting and HTTPS included.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: dark;
            --bg: #04070d;
            --bg-raise: #0a1220;
            --ink: #e8eef6;
            --muted: #8ba0b6;
            --line: rgba(148, 184, 220, 0.14);
            --brand: #22d3ee;
            --brand-deep: #0891b2;
            --brand-soft: rgba(34, 211, 238, 0.12);
            --glow: rgba(34, 211, 238, 0.45);
            --radius: 1.1rem;
            --font-sans: "Geist", ui-sans-serif, system-ui, sans-serif;
            --font-mono: "Geist Mono", ui-monospace, monospace;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font: 16px/1.65 var(--font-sans);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: var(--brand); }
        ::selection { background: rgba(34, 211, 238, .28); }

        /* ---- atmosphere ------------------------------------------------- */
        .mk-atmosphere {
            position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden;
        }
        .mk-atmosphere::before {
            content: ""; position: absolute; inset: -20%;
            background:
                radial-gradient(42rem 30rem at 78% -6%, rgba(8, 145, 178, .16), transparent 60%),
                radial-gradient(36rem 28rem at 6% 22%, rgba(34, 211, 238, .07), transparent 55%),
                radial-gradient(50rem 36rem at 50% 115%, rgba(8, 145, 178, .10), transparent 60%);
        }
        .mk-grid-lines {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(139, 160, 182, .05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(139, 160, 182, .05) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(70rem 40rem at 50% 0%, #000 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(70rem 40rem at 50% 0%, #000 30%, transparent 75%);
        }

        .mk-shell { position: relative; z-index: 1; min-height: 100vh; display: flex; flex-direction: column; }

        /* ---- nav -------------------------------------------------------- */
        nav.mk-nav {
            position: sticky; top: 0; z-index: 50;
            display: flex; align-items: center; gap: 1.4rem;
            padding: .9rem clamp(1.25rem, 4vw, 3rem);
            background: color-mix(in srgb, var(--bg) 72%, transparent);
            border-bottom: 1px solid transparent;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: border-color .25s ease, background .25s ease;
        }
        nav.mk-nav.scrolled { border-color: var(--line); background: color-mix(in srgb, var(--bg) 86%, transparent); }
        .mk-brand {
            font-weight: 700; font-size: 1.15rem; letter-spacing: -.02em;
            text-decoration: none; color: var(--ink); display: flex; align-items: center; gap: .55rem;
        }
        .mk-brand .dot {
            width: .95rem; height: .95rem; border-radius: 50%;
            background: radial-gradient(circle at 32% 30%, #67e8f9, var(--brand-deep) 70%);
            box-shadow: 0 0 14px var(--glow);
        }
        .mk-brand span { color: var(--brand); }
        .mk-nav .links { display: flex; align-items: center; gap: 1.4rem; margin-left: auto; }
        .mk-nav a.link {
            color: var(--muted); text-decoration: none; font-size: .92rem; font-weight: 500;
            transition: color .15s ease;
        }
        .mk-nav a.link:hover { color: var(--ink); }
        @media (max-width: 720px) { .mk-nav a.link.optional { display: none; } }

        /* ---- buttons ---------------------------------------------------- */
        .mk-btn {
            display: inline-flex; align-items: center; gap: .5rem;
            background: linear-gradient(135deg, #38e1f8, var(--brand-deep));
            color: #04121a; font-weight: 600; font-size: .98rem;
            border: 0; border-radius: 999px; padding: .72rem 1.5rem;
            text-decoration: none; cursor: pointer;
            box-shadow: 0 4px 24px rgba(8, 145, 178, .35), inset 0 1px 0 rgba(255,255,255,.25);
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .mk-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(34, 211, 238, .45), inset 0 1px 0 rgba(255,255,255,.25); }
        .mk-btn.ghost {
            background: rgba(255,255,255,.04); color: var(--ink);
            border: 1px solid var(--line); box-shadow: none;
        }
        .mk-btn.ghost:hover { border-color: rgba(34, 211, 238, .4); box-shadow: 0 0 0 4px var(--brand-soft); }
        .mk-btn.small { padding: .45rem 1.1rem; font-size: .9rem; }

        /* ---- shared section furniture ----------------------------------- */
        .mk-container { width: 100%; max-width: 76rem; margin: 0 auto; padding: 0 clamp(1.25rem, 4vw, 3rem); }
        .mk-eyebrow {
            font-family: var(--font-mono); font-size: .72rem; font-weight: 500;
            letter-spacing: .22em; text-transform: uppercase; color: var(--brand);
            margin: 0 0 .9rem;
        }
        h1, h2, h3 { letter-spacing: -.03em; line-height: 1.12; font-weight: 700; margin: 0; }

        /* ---- reveal animations ------------------------------------------ */
        .rv { opacity: 0; transform: translateY(22px); transition: opacity .7s ease, transform .7s ease; }
        .rv.in { opacity: 1; transform: none; }
        .rv-d1 { transition-delay: .05s; } .rv-d2 { transition-delay: .15s; }
        .rv-d3 { transition-delay: .25s; } .rv-d4 { transition-delay: .35s; }
        .rv-d5 { transition-delay: .45s; } .rv-d6 { transition-delay: .55s; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .rv { opacity: 1; transform: none; transition: none; }
        }

        /* ---- footer ------------------------------------------------------ */
        footer.mk-footer {
            margin-top: auto; border-top: 1px solid var(--line);
            padding: 2.2rem clamp(1.25rem, 4vw, 3rem);
            display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;
            color: var(--muted); font-size: .9rem;
            background: color-mix(in srgb, var(--bg) 80%, transparent);
        }
        footer.mk-footer .links { display: flex; gap: 1.4rem; flex-wrap: wrap; }
        footer.mk-footer a { color: var(--muted); text-decoration: none; }
        footer.mk-footer a:hover { color: var(--ink); }
    </style>
    <noscript><style>.rv { opacity: 1; transform: none; }</style></noscript>
    @yield('head')
</head>
<body>
<div class="mk-atmosphere"><div class="mk-grid-lines"></div></div>
<div class="mk-shell">
    <nav class="mk-nav" id="mk-nav">
        <a class="mk-brand" href="{{ url('/') }}"><span class="dot"></span>Site<span>Forge</span></a>
        <div class="links">
            <a class="link optional" href="{{ url('/#features') }}">Features</a>
            <a class="link optional" href="{{ url('/#how-it-works') }}">How it works</a>
            <a class="link" href="{{ route('pricing') }}">Pricing</a>
            @auth
                <a class="link" href="{{ route('dashboard') }}">Dashboard</a>
            @else
                <a class="link" href="{{ route('login') }}">Log in</a>
                <a class="mk-btn small" href="{{ route('register') }}">Get started</a>
            @endauth
        </div>
    </nav>

    @yield('content')

    <footer class="mk-footer">
        <a class="mk-brand" style="font-size:1rem" href="{{ url('/') }}"><span class="dot" style="width:.7rem;height:.7rem"></span>Site<span>Forge</span></a>
        <div class="links">
            <a href="{{ route('pricing') }}">Pricing</a>
            <a href="{{ route('register') }}">Sign up</a>
            <a href="{{ route('login') }}">Log in</a>
        </div>
        <span>© {{ date('Y') }} SiteForge. Websites, domains &amp; hosting — built by AI.</span>
    </footer>
</div>

<script>
    (function () {
        const nav = document.getElementById('mk-nav');
        addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 12), { passive: true });

        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        document.querySelectorAll('.rv').forEach(el => io.observe(el));
    })();
</script>
@yield('scripts')
</body>
</html>
