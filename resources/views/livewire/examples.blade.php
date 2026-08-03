@section('title', 'Examples — real sites built with SiteForge')
@section('meta_description', 'See what SiteForge produces. Real example sites for a plumber, a coffee roastery and a photographer — each generated from a short brief, then published with hosting and HTTPS.')

@section('head')
<style>
    /* ---------------- hero ---------------- */
    .ex-head { max-width: 48rem; margin: 0 auto; padding: clamp(3rem,7vh,5rem) clamp(1.25rem,4vw,3rem) 1rem; text-align: center; }
    .ex-head h1 { font-size: clamp(2.1rem,4.6vw,3.2rem); font-weight: 800; letter-spacing: -.03em; margin: 0 0 1.1rem; }
    .ex-head h1 .grad {
        background: linear-gradient(100deg,#7dedff 5%,#22d3ee 40%,#0e9fc4 70%,#7dedff 95%);
        background-size: 200% auto; -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: transparent;
        animation: shimmer 6s linear infinite;
    }
    @keyframes shimmer { to { background-position: 200% center; } }
    .ex-head > p { color: var(--muted); font-size: clamp(1rem,1.5vw,1.14rem); margin: 0 auto; max-width: 38rem; }

    /* typed brief — shows the input that produced a site */
    .ex-brief {
        margin: 1.9rem auto 0; max-width: 40rem;
        border: 1px solid var(--line); border-radius: .8rem;
        background: color-mix(in srgb, var(--bg-raise) 62%, transparent);
        padding: .85rem 1.1rem; text-align: left;
        display: flex; align-items: flex-start; gap: .7rem;
        font: 500 .88rem/1.6 var(--font-mono); color: var(--ink);
        min-height: 4.4rem;
    }
    .ex-brief .prompt { color: var(--brand); flex: 0 0 auto; }
    .ex-brief .typed::after {
        content: ""; display: inline-block; width: .55ch; height: 1.05em;
        background: var(--brand); margin-left: .12em; vertical-align: -.18em;
        animation: caret 1s steps(1) infinite;
    }
    @keyframes caret { 50% { opacity: 0; } }

    /* ---------------- filters ---------------- */
    .ex-filters {
        display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem;
        max-width: 76rem; margin: clamp(2rem,5vh,3rem) auto 0;
        padding: 0 clamp(1.25rem,4vw,3rem);
    }
    .ex-filter {
        font: 500 .82rem var(--font-sans); color: var(--muted); cursor: pointer;
        border: 1px solid var(--line); border-radius: 999px; padding: .42rem 1rem;
        background: transparent; transition: color .16s, border-color .16s, background .16s;
    }
    .ex-filter:hover { color: var(--ink); border-color: color-mix(in srgb, var(--brand) 40%, transparent); }
    .ex-filter[aria-pressed="true"] {
        color: #04070d; background: var(--brand); border-color: var(--brand); font-weight: 650;
    }
    .ex-filter b { font-weight: 500; opacity: .65; margin-left: .3rem; }

    /* ---------------- grid ---------------- */
    .ex-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(21rem, 1fr));
        gap: 1.6rem; max-width: 76rem; margin: 1.6rem auto 0;
        padding: 0 clamp(1.25rem,4vw,3rem);
    }
    .ex-card {
        border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden;
        background: color-mix(in srgb, var(--bg-raise) 70%, transparent);
        display: flex; flex-direction: column;
        transition: border-color .22s, transform .22s, box-shadow .22s, opacity .3s;
    }
    .ex-card:hover {
        border-color: color-mix(in srgb, var(--brand) 48%, transparent);
        transform: translateY(-4px);
        box-shadow: 0 22px 54px rgba(2,8,18,.55), 0 0 0 1px rgba(34,211,238,.06);
    }
    /* staggered reveal */
    .ex-card.reveal { opacity: 0; transform: translateY(18px); }
    .ex-card.reveal.in { opacity: 1; transform: none; transition: opacity .5s ease, transform .5s cubic-bezier(.22,.8,.3,1); }
    .ex-card.hidden { display: none; }

    /* chrome bar */
    .ex-chrome {
        display: flex; align-items: center; gap: .34rem; padding: .5rem .7rem;
        border-bottom: 1px solid var(--line); background: rgba(255,255,255,.02);
    }
    .ex-chrome i { width: .44rem; height: .44rem; border-radius: 50%; background: rgba(139,160,182,.35); }
    .ex-chrome i:first-child { background: rgba(34,211,238,.75); }
    .ex-chrome em {
        font: 500 .6rem var(--font-mono); font-style: normal; color: var(--muted);
        margin-left: .45rem; letter-spacing: .03em; overflow: hidden;
        text-overflow: ellipsis; white-space: nowrap; flex: 1;
    }
    .ex-live { display: inline-flex; align-items: center; gap: .3rem; font: 500 .58rem var(--font-mono);
               color: #4ade80; letter-spacing: .1em; text-transform: uppercase; }
    .ex-live .pip { width: .38rem; height: .38rem; border-radius: 50%; background: #4ade80;
                    animation: pip 2s ease-in-out infinite; }
    @keyframes pip { 0%,100% { opacity: 1; box-shadow: 0 0 0 0 rgba(74,222,128,.5); }
                     50% { opacity: .55; box-shadow: 0 0 0 .22rem rgba(74,222,128,0); } }

    /* device toggle */
    .ex-devices { display: flex; gap: .18rem; margin-left: .4rem; }
    .ex-dev {
        background: transparent; border: 1px solid transparent; border-radius: .35rem;
        padding: .16rem .3rem; cursor: pointer; color: var(--muted); line-height: 0;
        transition: color .15s, background .15s;
    }
    .ex-dev:hover { color: var(--ink); }
    .ex-dev[aria-pressed="true"] { color: var(--brand); background: var(--brand-soft); }

    /* preview */
    .ex-frame { position: relative; aspect-ratio: 16/10; overflow: hidden; background: #fff; }
    .ex-frame iframe {
        position: absolute; top: 0; left: 0; border: 0;
        width: 1280px; height: 900px; transform-origin: 0 0;
        pointer-events: none;
        transition: transform .55s cubic-bezier(.4,0,.2,1);
    }
    /* on hover, ease the preview down the page so more than the hero is visible */
    .ex-card:hover .ex-frame iframe { transform: var(--scroll-tf, var(--tf)); }
    .ex-frame.is-mobile { background: #0a1220; }
    .ex-frame.is-mobile iframe { width: 390px; height: 780px; box-shadow: 0 0 0 1px rgba(255,255,255,.1); }

    .ex-frame a.cover { position: absolute; inset: 0; z-index: 3; }
    .ex-frame .open-hint {
        position: absolute; inset: 0; z-index: 2; display: grid; place-items: center;
        background: color-mix(in srgb, var(--bg) 50%, transparent); opacity: 0;
        transition: opacity .2s ease; pointer-events: none;
    }
    .ex-card:hover .open-hint { opacity: 1; }
    .open-hint span {
        background: var(--brand); color: #04070d; font-weight: 650; font-size: .84rem;
        padding: .6rem 1.15rem; border-radius: 999px;
        transform: translateY(6px); transition: transform .25s cubic-bezier(.22,.8,.3,1);
    }
    .ex-card:hover .open-hint span { transform: none; }

    /* body */
    .ex-body { padding: 1.15rem 1.3rem 1.4rem; display: flex; flex-direction: column; flex: 1; }
    .ex-meta { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; }
    .ex-type {
        font: 500 .66rem var(--font-mono); text-transform: uppercase; letter-spacing: .12em;
        color: var(--brand); border: 1px solid color-mix(in srgb, var(--brand) 30%, transparent);
        border-radius: 999px; padding: .22rem .62rem;
    }
    .ex-swatch { width: .78rem; height: .78rem; border-radius: 50%; border: 1px solid rgba(255,255,255,.18); }
    .ex-style { font-size: .74rem; color: var(--muted); text-transform: capitalize; }
    .ex-body h3 { margin: 0 0 .3rem; font-size: 1.16rem; letter-spacing: -.015em; }
    .ex-tag { color: var(--muted); font-size: .92rem; margin: 0 0 .9rem; }
    .ex-sections { display: flex; flex-wrap: wrap; gap: .32rem; margin-top: auto; padding-top: .5rem; }
    .ex-sections span {
        font: 500 .66rem var(--font-mono); color: var(--muted);
        border: 1px solid var(--line); border-radius: 999px; padding: .2rem .55rem;
    }
    .ex-open {
        margin-top: 1rem; display: inline-flex; align-items: center; gap: .4rem;
        color: var(--brand); text-decoration: none; font-size: .9rem; font-weight: 600;
    }
    .ex-open .arw { transition: transform .2s cubic-bezier(.22,.8,.3,1); }
    .ex-card:hover .ex-open .arw { transform: translateX(4px); }

    .ex-note {
        max-width: 46rem; margin: clamp(2.6rem,6vh,4rem) auto 0;
        padding: 1.4rem clamp(1.25rem,4vw,3rem); text-align: center;
        color: var(--muted); font-size: .95rem;
    }
    .ex-cta { text-align: center; padding: clamp(2.5rem,6vh,4rem) 1.25rem clamp(3.5rem,8vh,5.5rem); }
    .ex-cta h2 { font-size: clamp(1.5rem,3vw,2.1rem); margin: 0 0 .7rem; letter-spacing: -.02em; }
    .ex-cta p { color: var(--muted); margin: 0 0 1.6rem; }
    .ex-empty { text-align: center; color: var(--muted); padding: 3rem 1.25rem; }

    @media (prefers-reduced-motion: reduce) {
        .ex-card.reveal { opacity: 1; transform: none; }
        .ex-card:hover { transform: none; }
        .ex-card:hover .ex-frame iframe { transform: var(--tf); }
        .ex-head h1 .grad { animation: none; }
        .ex-live .pip { animation: none; }
        .ex-brief .typed::after { animation: none; }
    }
</style>
@endsection

<div>
    <header class="ex-head">
        <h1>Built from a paragraph.<br><span class="grad">Live in minutes.</span></h1>
        <p>These are real sites produced by SiteForge — each one from a short description of the
           business and a few prices. Nothing else.</p>

        @if ($demos->isNotEmpty())
            <div class="ex-brief" aria-hidden="true">
                <span class="prompt">&gt;</span>
                <span class="typed" id="ex-typed"></span>
            </div>
        @endif
    </header>

    @if ($demos->isEmpty())
        <p class="ex-empty">Examples are being prepared. Check back shortly.</p>
    @else
        @php
            $types = $demos->groupBy('type')->map->count();
        @endphp

        <div class="ex-filters" role="group" aria-label="Filter examples by type">
            <button class="ex-filter" data-filter="all" aria-pressed="true">
                All<b>{{ $demos->count() }}</b>
            </button>
            @foreach ($types as $type => $count)
                <button class="ex-filter" data-filter="{{ $type }}" aria-pressed="false">
                    {{ $demos->firstWhere('type', $type)['type_label'] }}<b>{{ $count }}</b>
                </button>
            @endforeach
        </div>

        <div class="ex-grid" id="ex-grid">
            @foreach ($demos as $demo)
                <article class="ex-card reveal" data-type="{{ $demo['type'] }}" style="--d: {{ $loop->index * 90 }}ms">
                    <div class="ex-chrome">
                        <i></i><i></i><i></i>
                        <em>{{ $demo['slug'] }}.{{ config('sites.domain') }}</em>
                        <span class="ex-live"><span class="pip"></span>Live</span>
                        <span class="ex-devices">
                            <button class="ex-dev" data-device="desktop" aria-pressed="true" title="Desktop preview" aria-label="Desktop preview">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>
                                </svg>
                            </button>
                            <button class="ex-dev" data-device="mobile" aria-pressed="false" title="Mobile preview" aria-label="Mobile preview">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>
                                </svg>
                            </button>
                        </span>
                    </div>

                    <div class="ex-frame">
                        <iframe src="{{ $demo['url'] }}" title="Preview of {{ $demo['name'] }}"
                                loading="lazy" scrolling="no" tabindex="-1" aria-hidden="true"></iframe>
                        <div class="open-hint"><span>View the full site →</span></div>
                        <a class="cover" href="{{ $demo['url'] }}" target="_blank" rel="noopener"
                           aria-label="Open the {{ $demo['name'] }} example site in a new tab"></a>
                    </div>

                    <div class="ex-body">
                        <div class="ex-meta">
                            <span class="ex-type">{{ $demo['type_label'] }}</span>
                            <span class="ex-swatch" style="background: {{ $demo['accent'] }}"></span>
                            <span class="ex-style">{{ $demo['style'] }} · {{ $demo['scheme'] }}</span>
                        </div>
                        <h3>{{ $demo['name'] }}</h3>
                        @if ($demo['tagline'])
                            <p class="ex-tag">{{ $demo['tagline'] }}</p>
                        @endif
                        <div class="ex-sections">
                            @foreach ($demo['sections'] as $section)
                                <span>{{ str_replace('_', ' ', $section) }}</span>
                            @endforeach
                            @if ($demo['offerings'])
                                <span>{{ $demo['offerings'] }} listed</span>
                            @endif
                        </div>
                        <a class="ex-open" href="{{ $demo['url'] }}" target="_blank" rel="noopener">
                            Open {{ $demo['name'] }} <span class="arw">→</span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <p class="ex-note">
        Every example is a self-contained static site — no page builder, no plugins, nothing to keep
        updated. That's what gets published to your domain, with hosting and HTTPS included.
    </p>

    <section class="ex-cta">
        <h2>Your turn</h2>
        <p>Describe your business, upload a few photos, and see what comes back.</p>
        <a class="mk-btn" href="{{ route('register') }}">Build my site</a>
    </section>

    @if ($demos->isNotEmpty())
    <script>
    (() => {
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /* --- scale each preview to its container, and set the hover scroll --- */
        const fit = (card) => {
            const frame = card.querySelector('.ex-frame');
            const iframe = frame.querySelector('iframe');
            const mobile = frame.classList.contains('is-mobile');
            const w = frame.clientWidth, h = frame.clientHeight;
            if (!w || !h) return;

            if (mobile) {
                // Fit the phone by height and centre it horizontally.
                const s = h / 780;
                const left = (w - 390 * s) / 2;
                iframe.style.setProperty('--tf', `translateX(${left}px) scale(${s})`);
                iframe.style.setProperty('--scroll-tf', `translateX(${left}px) scale(${s})`);
            } else {
                const s = w / 1280;
                iframe.style.setProperty('--tf', `scale(${s})`);
                // Drift down the page on hover to reveal more than the hero.
                const drift = Math.min(260, (900 - h / s) * 0.55);
                iframe.style.setProperty('--scroll-tf', `translateY(${-drift * s}px) scale(${s})`);
            }
            iframe.style.transform = 'var(--tf)';
        };

        const cards = [...document.querySelectorAll('.ex-card')];
        const fitAll = () => cards.forEach(fit);
        fitAll();
        window.addEventListener('resize', fitAll, { passive: true });
        cards.forEach(c => c.querySelector('iframe').addEventListener('load', () => fit(c)));

        /* --- desktop / mobile toggle --- */
        cards.forEach(card => {
            card.querySelectorAll('.ex-dev').forEach(btn => {
                btn.addEventListener('click', () => {
                    const frame = card.querySelector('.ex-frame');
                    frame.classList.toggle('is-mobile', btn.dataset.device === 'mobile');
                    card.querySelectorAll('.ex-dev').forEach(b =>
                        b.setAttribute('aria-pressed', String(b === btn)));
                    fit(card);
                });
            });
        });

        /* --- staggered reveal --- */
        if (!reduced && 'IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (!e.isIntersecting) return;
                    const el = e.target;
                    setTimeout(() => el.classList.add('in'), parseInt(el.style.getPropertyValue('--d')) || 0);
                    io.unobserve(el);
                });
            }, { rootMargin: '0px 0px -8% 0px' });
            cards.forEach(c => io.observe(c));
        } else {
            cards.forEach(c => c.classList.add('in'));
        }

        /* --- filter by site type --- */
        const grid = document.getElementById('ex-grid');
        document.querySelectorAll('.ex-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                const want = btn.dataset.filter;
                document.querySelectorAll('.ex-filter').forEach(b =>
                    b.setAttribute('aria-pressed', String(b === btn)));
                cards.forEach(c => {
                    c.classList.toggle('hidden', want !== 'all' && c.dataset.type !== want);
                });
                requestAnimationFrame(fitAll);
                grid.setAttribute('aria-live', 'polite');
            });
        });

        /* --- typed brief in the hero --- */
        const briefs = @json($demos->map(fn ($d) => $d['description'])->values());
        const out = document.getElementById('ex-typed');
        if (out && briefs.length) {
            if (reduced) {
                out.textContent = briefs[0];
            } else {
                let i = 0, c = 0, deleting = false;
                const tick = () => {
                    const full = briefs[i];
                    c += deleting ? -1 : 1;
                    out.textContent = full.slice(0, c);
                    let wait = deleting ? 12 : 26;
                    if (!deleting && c === full.length) { wait = 2600; deleting = true; }
                    else if (deleting && c === 0) { deleting = false; i = (i + 1) % briefs.length; wait = 420; }
                    setTimeout(tick, wait);
                };
                tick();
            }
        }
    })();
    </script>
    @endif
</div>
