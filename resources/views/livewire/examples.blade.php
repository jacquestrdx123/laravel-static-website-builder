@section('title', 'Examples — real sites built with SiteForge')
@section('meta_description', 'See what SiteForge produces. Real example sites for a plumber, a coffee roastery and a photographer — each generated from a short brief, then published with hosting and HTTPS.')

@section('head')
<style>
    .ex-head { max-width: 46rem; margin: 0 auto; padding: clamp(3rem,7vh,5rem) clamp(1.25rem,4vw,3rem) 1rem; text-align: center; }
    .ex-head h1 { font-size: clamp(2.1rem,4.6vw,3.2rem); font-weight: 800; letter-spacing: -.03em; margin: 0 0 1rem; }
    .ex-head h1 .grad {
        background: linear-gradient(100deg,#7dedff 5%,#22d3ee 40%,#0e9fc4 70%,#7dedff 95%);
        background-size: 200% auto; -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: transparent;
    }
    .ex-head p { color: var(--muted); font-size: clamp(1rem,1.5vw,1.14rem); margin: 0 auto; max-width: 38rem; }

    .ex-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(21rem, 1fr));
        gap: 1.6rem; max-width: 76rem;
        margin: clamp(2rem,5vh,3.4rem) auto 0;
        padding: 0 clamp(1.25rem,4vw,3rem);
    }
    .ex-card {
        border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden;
        background: color-mix(in srgb, var(--bg-raise) 70%, transparent);
        display: flex; flex-direction: column;
        transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
    }
    .ex-card:hover { border-color: color-mix(in srgb, var(--brand) 45%, transparent); transform: translateY(-3px);
                     box-shadow: 0 18px 46px rgba(2,8,18,.5); }

    /* browser chrome + live preview */
    .ex-chrome { display: flex; align-items: center; gap: .34rem; padding: .5rem .7rem;
                 border-bottom: 1px solid var(--line); background: rgba(255,255,255,.02); }
    .ex-chrome i { width: .44rem; height: .44rem; border-radius: 50%; background: rgba(139,160,182,.35); }
    .ex-chrome i:first-child { background: rgba(34,211,238,.75); }
    .ex-chrome em {
        font: 500 .6rem var(--font-mono); font-style: normal; color: var(--muted);
        margin-left: .45rem; letter-spacing: .03em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .ex-frame { position: relative; aspect-ratio: 16/10; overflow: hidden; background: #fff; }
    .ex-frame iframe {
        position: absolute; top: 0; left: 0; border: 0;
        width: 1280px; height: 800px; transform: scale(.42); transform-origin: 0 0;
        pointer-events: none;
    }
    .ex-frame a.cover { position: absolute; inset: 0; z-index: 2; }
    .ex-frame .open-hint {
        position: absolute; inset: 0; z-index: 3; display: grid; place-items: center;
        background: color-mix(in srgb, var(--bg) 55%, transparent); opacity: 0;
        transition: opacity .18s ease; pointer-events: none;
    }
    .ex-card:hover .open-hint { opacity: 1; }
    .open-hint span {
        background: var(--brand); color: #04070d; font-weight: 650; font-size: .84rem;
        padding: .6rem 1.15rem; border-radius: 999px;
    }

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
    .ex-open:hover { text-decoration: underline; }

    .ex-note {
        max-width: 46rem; margin: clamp(2.6rem,6vh,4rem) auto 0;
        padding: 1.4rem clamp(1.25rem,4vw,3rem); text-align: center;
        color: var(--muted); font-size: .95rem;
    }
    .ex-cta { text-align: center; padding: clamp(2.5rem,6vh,4rem) 1.25rem clamp(3.5rem,8vh,5.5rem); }
    .ex-cta h2 { font-size: clamp(1.5rem,3vw,2.1rem); margin: 0 0 .7rem; letter-spacing: -.02em; }
    .ex-cta p { color: var(--muted); margin: 0 0 1.6rem; }
    .ex-empty { text-align: center; color: var(--muted); padding: 3rem 1.25rem; }
</style>
@endsection

<div>
    <header class="ex-head">
        <h1>Built from a paragraph.<br><span class="grad">Live in minutes.</span></h1>
        <p>These are real sites produced by SiteForge — each one from a short description of the
           business, a few prices, and nothing else. No templates picked, no pages laid out by hand.</p>
    </header>

    @if ($demos->isEmpty())
        <p class="ex-empty">Examples are being prepared. Check back shortly.</p>
    @else
        <div class="ex-grid">
            @foreach ($demos as $demo)
                <article class="ex-card">
                    <div class="ex-chrome">
                        <i></i><i></i><i></i>
                        <em>{{ $demo['slug'] }}.{{ config('sites.domain') }}</em>
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
                            Open {{ $demo['name'] }} →
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
</div>
