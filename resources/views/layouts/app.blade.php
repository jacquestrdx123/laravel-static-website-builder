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
            --brand-2: #6366f1;
            --brand-3: #d946ef;
            --brand-gradient: linear-gradient(135deg, var(--brand), var(--brand-2) 58%, var(--brand-3));
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

        /* ---- Floating glass nav ------------------------------------------- */
        nav.top {
            position: sticky;
            top: 0;
            z-index: 40;
            padding: .8rem 1rem;
            background: linear-gradient(to bottom,
                color-mix(in srgb, var(--background) 90%, transparent) 40%, transparent);
        }
        nav.top .nav-inner {
            position: relative;
            display: flex;
            align-items: center;
            gap: .75rem;
            max-width: 78rem;
            margin: 0 auto;
            padding: .45rem .55rem .45rem .9rem;
            border-radius: var(--radius-pill);
            background: color-mix(in srgb, var(--surface) 72%, transparent);
            backdrop-filter: blur(16px) saturate(150%);
            -webkit-backdrop-filter: blur(16px) saturate(150%);
            box-shadow:
                0 1px 2px rgba(15, 23, 42, .04),
                0 14px 34px -14px color-mix(in srgb, var(--brand) 42%, transparent);
        }
        /* gradient hairline border */
        nav.top .nav-inner::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(115deg,
                color-mix(in srgb, var(--brand) 60%, transparent),
                color-mix(in srgb, var(--line) 90%, transparent) 38%,
                color-mix(in srgb, var(--line) 90%, transparent) 62%,
                color-mix(in srgb, var(--brand-3) 55%, transparent));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
            mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            mask-composite: exclude;
            pointer-events: none;
        }

        nav.top .brand {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            font-weight: 650;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
            text-decoration: none;
            color: var(--foreground);
            flex: none;
        }
        nav.top .brand .mark {
            width: 1.9rem;
            height: 1.9rem;
            flex: none;
            display: grid;
            place-items: center;
            border-radius: .62rem;
            color: #fff;
            background: var(--brand-gradient);
            box-shadow: 0 5px 14px -5px color-mix(in srgb, var(--brand) 75%, transparent);
            transition: transform .4s cubic-bezier(.22, 1, .36, 1);
        }
        nav.top .brand .mark svg { width: 1.05rem; height: 1.05rem; }
        nav.top .brand:hover .mark { transform: rotate(-10deg) scale(1.07); }
        nav.top .brand span {
            background: linear-gradient(115deg, var(--brand), var(--brand-3));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        nav.top .nav-links {
            position: relative;
            display: flex;
            align-items: center;
            gap: .1rem;
        }
        nav.top a.link {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            text-decoration: none;
            color: var(--muted);
            white-space: nowrap;
            font-size: .9rem;
            font-weight: 500;
            padding: .5rem .78rem;
            border-radius: var(--radius-pill);
            transition: color .18s ease;
        }
        nav.top a.link svg {
            width: 1rem;
            height: 1rem;
            opacity: .55;
            transition: opacity .18s ease, transform .35s cubic-bezier(.22, 1, .36, 1), color .18s ease;
        }
        nav.top a.link:hover,
        nav.top a.link:focus-visible,
        nav.top a.link[aria-current="page"] { color: var(--foreground); }
        nav.top a.link:hover svg,
        nav.top a.link:focus-visible svg,
        nav.top a.link[aria-current="page"] svg {
            opacity: 1;
            color: var(--brand);
            transform: translateY(-1px);
        }

        /* the pill that slides between items */
        nav.top .nav-spot {
            position: absolute;
            z-index: 0;
            top: 50%;
            left: 0;
            width: 0;
            height: 2.2rem;
            transform: translateY(-50%);
            border-radius: var(--radius-pill);
            background: linear-gradient(135deg,
                color-mix(in srgb, var(--brand) 17%, transparent),
                color-mix(in srgb, var(--brand-3) 15%, transparent));
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--brand) 24%, transparent);
            opacity: 0;
            pointer-events: none;
            transition: left .34s cubic-bezier(.22, 1, .36, 1),
                        width .34s cubic-bezier(.22, 1, .36, 1),
                        opacity .2s ease;
        }
        nav.top .nav-spot.is-ready { opacity: 1; }

        nav.top .spacer { flex: 1; }

        nav.top .nav-toggle {
            display: none;
            margin-left: auto;
            width: 2.4rem;
            height: 2.4rem;
            align-items: center;
            justify-content: center;
            padding: 0;
            background: transparent;
            border: 1px solid var(--line);
            border-radius: .78rem;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease;
        }
        nav.top .nav-toggle:hover { border-color: color-mix(in srgb, var(--brand) 40%, var(--line)); }
        nav.top .nav-toggle .bars { position: relative; width: 1.05rem; height: .72rem; }
        nav.top .nav-toggle .bars i {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            border-radius: 2px;
            background: var(--foreground);
            transition: transform .32s cubic-bezier(.22, 1, .36, 1), opacity .2s ease;
        }
        nav.top .nav-toggle .bars i:nth-child(1) { top: 0; }
        nav.top .nav-toggle .bars i:nth-child(2) { top: 50%; margin-top: -1px; }
        nav.top .nav-toggle .bars i:nth-child(3) { bottom: 0; }
        nav.top.nav-open .nav-toggle .bars i:nth-child(1) { transform: translateY(.35rem) rotate(45deg); }
        nav.top.nav-open .nav-toggle .bars i:nth-child(2) { opacity: 0; transform: scaleX(.4); }
        nav.top.nav-open .nav-toggle .bars i:nth-child(3) { transform: translateY(-.35rem) rotate(-45deg); }

        .credits-pill {
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            background: var(--brand-gradient);
            color: #fff;
            border-radius: var(--radius-pill);
            padding: .45rem .9rem;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 7px 18px -8px color-mix(in srgb, var(--brand) 90%, transparent);
            transition: transform .25s cubic-bezier(.22, 1, .36, 1), box-shadow .25s ease;
        }
        .credits-pill svg { width: .95rem; height: .95rem; flex: none; }
        .credits-pill:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 11px 24px -8px color-mix(in srgb, var(--brand) 100%, transparent);
        }
        .credits-pill::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: -60%;
            width: 38%;
            background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .45), transparent);
            transform: skewX(-18deg);
        }
        .credits-pill:hover::after { animation: credits-shine .8s ease; }
        @keyframes credits-shine { to { left: 130%; } }

        nav.top .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--line);
            border-radius: var(--radius-pill);
            padding: .45rem .9rem;
            font: inherit;
            font-size: .85rem;
            font-weight: 500;
            cursor: pointer;
            transition: color .18s ease, border-color .18s ease, background .18s ease;
        }
        nav.top .logout-btn svg { width: .95rem; height: .95rem; }
        nav.top .logout-btn:hover {
            color: var(--danger);
            border-color: color-mix(in srgb, var(--danger) 38%, var(--line));
            background: color-mix(in srgb, var(--danger) 7%, transparent);
        }

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

        .auth-divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.25rem 0;
            color: var(--muted);
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--line);
        }
        .auth-google {
            display: flex;
            width: 100%;
            justify-content: center;
            text-decoration: none;
            box-sizing: border-box;
        }

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
            nav.top .nav-spot,
            nav.top .brand .mark,
            nav.top a.link svg,
            .credits-pill { transition: none !important; }
            nav.top.nav-open .nav-links > * { animation: none !important; }
            .credits-pill:hover::after { animation: none !important; }
            nav.top .brand:hover .mark,
            .credits-pill:hover { transform: none; }
        }

        @media (max-width: 900px) {
            nav.top .nav-inner {
                flex-wrap: wrap;
                border-radius: 1.4rem;
                padding: .5rem .55rem;
            }
            nav.top .nav-toggle { display: inline-flex; }
            nav.top .nav-spot { display: none; }
            /* Only the authenticated menu collapses — guests have no toggle to reopen it. */
            nav.top .nav-links.is-collapsible {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: .18rem;
                padding: .55rem .1rem .15rem;
            }
            nav.top.nav-open .nav-links.is-collapsible { display: flex; }
            nav.top .nav-links.is-collapsible a.link {
                width: 100%;
                padding: .72rem .8rem;
                font-size: .95rem;
            }
            nav.top .nav-links a.link svg { opacity: 1; color: var(--brand); }
            nav.top .nav-links.is-collapsible a.link[aria-current="page"] {
                background: linear-gradient(135deg,
                    color-mix(in srgb, var(--brand) 14%, transparent),
                    color-mix(in srgb, var(--brand-3) 12%, transparent));
            }
            nav.top .nav-links.is-collapsible .credits-pill,
            nav.top .nav-links.is-collapsible form,
            nav.top .nav-links.is-collapsible .logout-btn { width: 100%; justify-content: center; }
            nav.top .nav-inner > .spacer { display: none; }

            nav.top.nav-open .nav-links.is-collapsible > * {
                animation: nav-item-in .34s cubic-bezier(.22, 1, .36, 1) backwards;
            }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(1) { animation-delay: .02s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(2) { animation-delay: .05s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(3) { animation-delay: .08s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(4) { animation-delay: .11s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(5) { animation-delay: .14s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(6) { animation-delay: .17s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(7) { animation-delay: .20s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(8) { animation-delay: .23s; }
            nav.top.nav-open .nav-links.is-collapsible > *:nth-child(9) { animation-delay: .26s; }
        }
        @keyframes nav-item-in {
            from { opacity: 0; transform: translateY(-7px); }
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
        [
            'route' => 'dashboard', 'path' => null,
            'href' => route('dashboard'), 'label' => 'Dashboard',
            'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        ],
        [
            'route' => 'pricing', 'path' => null,
            'href' => route('pricing'), 'label' => 'Pricing',
            'icon' => '<path d="M20.6 13.4 11 3.8A2 2 0 0 0 9.6 3H4a1 1 0 0 0-1 1v5.6A2 2 0 0 0 3.6 11l9.6 9.6a2 2 0 0 0 2.8 0l4.6-4.6a2 2 0 0 0 0-2.6Z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        ],
        [
            'route' => 'websites.index', 'path' => null,
            'href' => route('websites.index'), 'label' => 'My Websites',
            'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18"/>',
        ],
        [
            'route' => 'domains.index', 'path' => null,
            'href' => route('domains.index'), 'label' => 'My Domains',
            'icon' => '<path d="M10 13a5 5 0 0 0 7.1 0l2.9-2.9a5 5 0 0 0-7.1-7.1L11.5 4.4"/><path d="M14 11a5 5 0 0 0-7.1 0L4 13.9a5 5 0 0 0 7.1 7.1l1.4-1.4"/>',
        ],
        [
            'route' => null, 'path' => 'posters*',
            'href' => url('/posters'), 'label' => 'My Posters',
            'icon' => '<rect x="3" y="3" width="18" height="18" rx="2.5"/><circle cx="8.5" cy="8.5" r="1.4"/><path d="m21 15-4.5-4.5L5 21"/>',
        ],
        [
            'route' => null, 'path' => 'newsletters*',
            'href' => url('/newsletters'), 'label' => 'My Newsletters',
            'icon' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7.5 8.5 5.5 8.5-5.5"/>',
        ],
    ];
@endphp
<nav class="top" id="top-nav">
    <div class="nav-inner">
        <a class="brand" href="{{ url('/') }}">
            <span class="mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2 4 14h7l-1 8 10-12h-7l1-8Z"/>
                </svg>
            </span>
            <span class="wordmark">Site<span>Forge</span></span>
        </a>
        @auth
            <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="main-nav-links" aria-label="Toggle menu">
                <span class="bars" aria-hidden="true"><i></i><i></i><i></i></span>
            </button>
            <div class="nav-links is-collapsible" id="main-nav-links">
                @foreach ($mainNavItems as $item)
                    @php
                        $isActive = ($item['route'] && request()->routeIs($item['route'], $item['route'].'.*'))
                            || ($item['path'] && request()->is($item['path']));
                    @endphp
                    <a class="link"
                       href="{{ $item['href'] }}"
                       @if ($isActive) aria-current="page" @endif
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
                <span class="spacer"></span>
                <a class="credits-pill" href="{{ route('billing.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M13 2 4 14h7l-1 8 10-12h-7l1-8Z"/>
                    </svg>
                    <span>{{ auth()->user()->ai_credits }} credits</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>
                        </svg>
                        <span>Log out</span>
                    </button>
                </form>
                <span class="nav-spot" aria-hidden="true"></span>
            </div>
        @else
            <span class="spacer"></span>
            <div class="nav-links">
                <a class="link" href="{{ route('pricing') }}">Pricing</a>
                <a class="link" href="{{ route('login') }}">Log in</a>
                <span class="nav-spot" aria-hidden="true"></span>
            </div>
            <a class="btn" style="padding:.45rem 1.15rem" href="{{ route('register') }}">Sign up</a>
        @endauth
    </div>
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

<script>
    (function () {
        const nav = document.getElementById('top-nav');
        if (!nav) return;

        const toggle = document.getElementById('nav-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                const open = nav.classList.toggle('nav-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && nav.classList.contains('nav-open')) {
                    nav.classList.remove('nav-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.focus();
                }
            });
        }

        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        nav.querySelectorAll('.nav-links').forEach(function (group) {
            const spot = group.querySelector('.nav-spot');
            const items = group.querySelectorAll('a.link');
            if (!spot || !items.length) return;

            const anchor = group.querySelector('a.link[aria-current="page"]');

            function place(el, animate) {
                // The sliding pill is a desktop affordance; mobile uses a stacked panel.
                if (!el || window.innerWidth <= 900) {
                    spot.classList.remove('is-ready');
                    return;
                }
                if (!animate) spot.style.transition = 'none';
                spot.style.left = el.offsetLeft + 'px';
                spot.style.width = el.offsetWidth + 'px';
                spot.classList.add('is-ready');
                if (!animate) {
                    void spot.offsetWidth;
                    spot.style.transition = '';
                }
            }

            items.forEach(function (el) {
                el.addEventListener('mouseenter', function () { place(el, !reduce); });
                el.addEventListener('focus', function () { place(el, !reduce); });
            });
            group.addEventListener('mouseleave', function () { place(anchor, !reduce); });

            place(anchor, false);
            window.addEventListener('resize', function () { place(anchor, false); });
            // Geist loads async and changes link widths — re-measure once it lands.
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(function () { place(anchor, false); });
            }
        });
    })();
</script>
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
