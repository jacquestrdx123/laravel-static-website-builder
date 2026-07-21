@extends('layouts.marketing')

@section('title', 'SiteForge — AI websites, domains & hosting for African business')

@section('head')
    <style>
        /* ================= HERO ================= */
        .hero {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
            align-items: center;
            gap: 2rem;
            max-width: 76rem;
            margin: 0 auto;
            padding: clamp(3rem, 8vh, 6rem) clamp(1.25rem, 4vw, 3rem) clamp(3rem, 7vh, 5rem);
            min-height: min(78vh, 46rem);
        }
        .hero-copy h1 {
            font-size: clamp(2.4rem, 5.4vw, 4.1rem);
            font-weight: 800;
        }
        .hero-copy h1 .grad {
            background: linear-gradient(100deg, #7dedff 5%, #22d3ee 40%, #0e9fc4 70%, #7dedff 95%);
            background-size: 200% auto;
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; color: transparent;
            animation: shimmer 6s linear infinite;
        }
        @keyframes shimmer { to { background-position: 200% center; } }
        .hero-copy .lede {
            color: var(--muted); font-size: clamp(1.02rem, 1.6vw, 1.2rem);
            max-width: 34rem; margin: 1.3rem 0 2rem;
        }
        .hero-cta { display: flex; flex-wrap: wrap; gap: .9rem; align-items: center; }
        .hero-trust {
            display: flex; flex-wrap: wrap; gap: .55rem; margin-top: 2.2rem;
        }
        .chip {
            font-family: var(--font-mono); font-size: .72rem; color: var(--muted);
            border: 1px solid var(--line); border-radius: 999px; padding: .32rem .8rem;
            background: rgba(255,255,255,.03);
        }
        .chip b { color: var(--brand); font-weight: 500; }

        /* ---- globe + floating cards ---- */
        .hero-visual { position: relative; min-height: 26rem; }
        #globe {
            display: block; width: 100%; height: 100%;
            position: absolute; inset: 0;
        }
        .float-card {
            position: absolute; z-index: 2;
            width: 13.5rem; border-radius: .9rem; overflow: hidden;
            background: color-mix(in srgb, var(--bg-raise) 78%, transparent);
            border: 1px solid var(--line);
            box-shadow: 0 18px 50px rgba(2, 8, 18, .55), 0 0 0 1px rgba(34,211,238,.05);
            backdrop-filter: blur(10px);
            transform-style: preserve-3d;
            animation: floaty 7s ease-in-out infinite;
        }
        .float-card .bar {
            display: flex; gap: .3rem; padding: .45rem .6rem;
            border-bottom: 1px solid var(--line); align-items: center;
        }
        .float-card .bar i { width: .45rem; height: .45rem; border-radius: 50%; background: rgba(139,160,182,.4); }
        .float-card .bar i:first-child { background: rgba(34, 211, 238, .8); }
        .float-card .bar em {
            font: 500 .58rem var(--font-mono); font-style: normal; color: var(--muted);
            margin-left: .3rem; letter-spacing: .04em;
        }
        .float-card .shot { padding: .7rem .75rem .85rem; }
        .float-card .shot .ttl { height: .55rem; width: 62%; border-radius: 4px; background: linear-gradient(90deg, rgba(34,211,238,.75), rgba(34,211,238,.25)); }
        .float-card .shot .ln { height: .34rem; border-radius: 4px; background: rgba(139,160,182,.28); margin-top: .45rem; }
        .float-card .shot .ln.s { width: 78%; } .float-card .shot .ln.xs { width: 52%; }
        .float-card .shot .imgs { display: flex; gap: .4rem; margin-top: .6rem; }
        .float-card .shot .imgs i {
            flex: 1; height: 2.5rem; border-radius: .4rem;
            background: linear-gradient(140deg, rgba(34,211,238,.35), rgba(8,145,178,.12));
            border: 1px solid rgba(34,211,238,.18);
        }
        .float-card .tag {
            position: absolute; top: .4rem; right: .5rem;
            font: 500 .56rem var(--font-mono); letter-spacing: .08em;
            color: #04121a; background: linear-gradient(135deg, #38e1f8, #0e9fc4);
            border-radius: 999px; padding: .14rem .5rem;
        }
        .fc-a { top: 6%; left: -2%; animation-delay: 0s; }
        .fc-b { bottom: 10%; right: 0%; animation-delay: 2.4s; width: 12rem; }
        .fc-c { top: 44%; left: 18%; animation-delay: 1.2s; width: 10.5rem; opacity: .92; }
        @keyframes floaty {
            0%, 100% { margin-top: 0; }
            50% { margin-top: -14px; }
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; min-height: unset; }
            .hero-visual { min-height: 20rem; order: 2; }
            .fc-c { display: none; }
            .fc-a { left: 0; } .fc-b { right: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            .float-card { animation: none; }
            .hero-copy h1 .grad { animation: none; }
        }

        /* ================= STATS BAND ================= */
        .stats {
            border-block: 1px solid var(--line);
            background: color-mix(in srgb, var(--bg-raise) 40%, transparent);
        }
        .stats .mk-container {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 1rem; padding-block: 1.6rem;
        }
        .stat { text-align: center; }
        .stat b {
            display: block; font-size: 1.45rem; font-weight: 700; letter-spacing: -.02em;
            background: linear-gradient(120deg, #a5f3fc, #22d3ee);
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        }
        .stat span { font-size: .82rem; color: var(--muted); }
        @media (max-width: 720px) { .stats .mk-container { grid-template-columns: repeat(2, 1fr); row-gap: 1.4rem; } }

        /* ================= SECTIONS ================= */
        .section { padding: clamp(3.5rem, 9vh, 6.5rem) 0; }
        .section-head { max-width: 42rem; margin-bottom: 2.6rem; }
        .section-head h2 { font-size: clamp(1.7rem, 3.4vw, 2.4rem); }
        .section-head p { color: var(--muted); margin: 1rem 0 0; }

        .feature-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.1rem;
        }
        @media (max-width: 980px) { .feature-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .feature-grid { grid-template-columns: 1fr; } }
        .feature {
            position: relative; border: 1px solid var(--line); border-radius: var(--radius);
            background: color-mix(in srgb, var(--bg-raise) 62%, transparent);
            padding: 1.5rem; overflow: hidden;
            transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease;
        }
        .feature:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 211, 238, .38);
            box-shadow: 0 16px 44px rgba(2, 8, 18, .5), 0 0 24px rgba(34,211,238,.07);
        }
        .feature::after {
            content: ""; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(18rem 10rem at var(--mx, 50%) var(--my, 0%), rgba(34,211,238,.09), transparent 60%);
            opacity: 0; transition: opacity .25s ease;
        }
        .feature:hover::after { opacity: 1; }
        .feature .icon {
            width: 2.6rem; height: 2.6rem; border-radius: .8rem;
            display: grid; place-items: center; margin-bottom: 1rem;
            background: var(--brand-soft); border: 1px solid rgba(34,211,238,.25);
            color: var(--brand); font-size: 1.15rem;
        }
        .feature h3 { font-size: 1.06rem; margin-bottom: .5rem; }
        .feature p { color: var(--muted); font-size: .93rem; margin: 0; }
        .feature .soon {
            font: 500 .6rem var(--font-mono); letter-spacing: .1em; text-transform: uppercase;
            color: var(--brand); border: 1px solid rgba(34,211,238,.3); border-radius: 999px;
            padding: .12rem .5rem; margin-left: .5rem; vertical-align: 2px;
        }

        /* steps */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.1rem; counter-reset: step; }
        @media (max-width: 860px) { .steps { grid-template-columns: 1fr; } }
        .step {
            position: relative; border: 1px solid var(--line); border-radius: var(--radius);
            padding: 1.6rem 1.5rem 1.5rem;
            background: linear-gradient(180deg, color-mix(in srgb, var(--bg-raise) 70%, transparent), transparent);
        }
        .step .num {
            font: 600 .78rem var(--font-mono); color: var(--brand);
            letter-spacing: .12em; display: block; margin-bottom: .8rem;
        }
        .step h3 { font-size: 1.05rem; margin-bottom: .5rem; }
        .step p { color: var(--muted); font-size: .93rem; margin: 0; }

        /* CTA band */
        .cta-band {
            position: relative; border-radius: 1.4rem; overflow: hidden;
            border: 1px solid rgba(34, 211, 238, .25);
            background:
                radial-gradient(34rem 16rem at 18% -30%, rgba(34,211,238,.22), transparent 60%),
                radial-gradient(30rem 18rem at 90% 130%, rgba(8,145,178,.28), transparent 60%),
                color-mix(in srgb, var(--bg-raise) 80%, transparent);
            padding: clamp(2.4rem, 6vw, 4rem);
            text-align: center;
        }
        .cta-band h2 { font-size: clamp(1.7rem, 3.6vw, 2.5rem); }
        .cta-band p { color: var(--muted); max-width: 34rem; margin: 1rem auto 1.8rem; }
    </style>
@endsection

@section('content')
    <!-- ============ HERO ============ -->
    <header class="hero">
        <div class="hero-copy">
            <p class="mk-eyebrow rv rv-d1">AI websites · Domains · Hosting</p>
            <h1 class="rv rv-d2">Your business, <span class="grad">online in minutes.</span></h1>
            <p class="lede rv rv-d3">
                Describe what you do, upload your photos, and our AI designs and builds a
                fast, beautiful website around them — then registers your domain and puts
                it live with HTTPS. No page builders. No agencies. No waiting.
            </p>
            <div class="hero-cta rv rv-d4">
                @auth
                    <a class="mk-btn" href="{{ route('websites.create') }}">Build a website →</a>
                    <a class="mk-btn ghost" href="{{ route('dashboard') }}">Go to dashboard</a>
                @else
                    <a class="mk-btn" href="{{ route('register') }}">Get started — first site free</a>
                    <a class="mk-btn ghost" href="{{ route('pricing') }}">See pricing</a>
                @endauth
            </div>
            <div class="hero-trust rv rv-d5">
                <span class="chip"><b>~3 min</b> from brief to preview</span>
                <span class="chip"><b>100%</b> static &amp; fast</span>
                <span class="chip"><b>Free</b> HTTPS on every site</span>
                <span class="chip"><b>.co.za</b> domains built-in</span>
            </div>
        </div>

        <div class="hero-visual rv rv-d3" id="hero-visual">
            <canvas id="globe" aria-hidden="true"></canvas>

            <div class="float-card fc-a" data-depth="18">
                <span class="tag">LIVE</span>
                <div class="bar"><i></i><i></i><i></i><em>thebakery.sites…</em></div>
                <div class="shot">
                    <div class="ttl"></div>
                    <div class="ln s"></div><div class="ln xs"></div>
                    <div class="imgs"><i></i><i></i><i></i></div>
                </div>
            </div>
            <div class="float-card fc-b" data-depth="30">
                <span class="tag">GENERATING</span>
                <div class="bar"><i></i><i></i><i></i><em>safari-tours…</em></div>
                <div class="shot">
                    <div class="ttl" style="width:48%"></div>
                    <div class="ln s"></div><div class="ln"></div>
                    <div class="imgs"><i></i><i></i></div>
                </div>
            </div>
            <div class="float-card fc-c" data-depth="44">
                <span class="tag">LIVE</span>
                <div class="bar"><i></i><i></i><i></i><em>guesthouse.co.za</em></div>
                <div class="shot">
                    <div class="ttl" style="width:70%"></div>
                    <div class="ln xs"></div>
                    <div class="imgs"><i></i><i></i></div>
                </div>
            </div>
        </div>
    </header>

    <!-- ============ STATS ============ -->
    <section class="stats">
        <div class="mk-container">
            <div class="stat rv"><b>1 credit</b><span>free when you sign up</span></div>
            <div class="stat rv rv-d1"><b>10 photos</b><span>designed around, not stock</span></div>
            <div class="stat rv rv-d2"><b>0 code</b><span>needed, ever</span></div>
            <div class="stat rv rv-d3"><b>24/7</b><span>your site never sleeps</span></div>
        </div>
    </section>

    <!-- ============ FEATURES ============ -->
    <section class="section" id="features">
        <div class="mk-container">
            <div class="section-head rv">
                <p class="mk-eyebrow">Everything included</p>
                <h2>One place for your whole online presence</h2>
                <p>Not just a website — the domain, the hosting, the updates, and the marketing that comes after.</p>
            </div>
            <div class="feature-grid">
                <article class="feature rv">
                    <div class="icon">⚡</div>
                    <h3>AI-designed websites</h3>
                    <p>The AI actually looks at your photos and designs around them — real copy from your
                        business story, no templates, no lorem ipsum.</p>
                </article>
                <article class="feature rv rv-d1">
                    <div class="icon">🌍</div>
                    <h3>Domains built-in</h3>
                    <p>Search, register and manage .co.za and international domains without leaving
                        your dashboard — DNS, nameservers and transfers included.</p>
                </article>
                <article class="feature rv rv-d2">
                    <div class="icon">🔒</div>
                    <h3>Hosting &amp; free HTTPS</h3>
                    <p>Pure static sites served on fast infrastructure with automatic SSL certificates.
                        No plugins to update, nothing to hack.</p>
                </article>
                <article class="feature rv">
                    <div class="icon">✏️</div>
                    <h3>Edit without rebuilding</h3>
                    <p>Prices changed? New menu? Update your products, services and details in a simple
                        form — your live site updates instantly.</p>
                </article>
                <article class="feature rv rv-d1">
                    <div class="icon">📰</div>
                    <h3>AI newsletters</h3>
                    <p>Grow a subscriber list from your site and send beautiful AI-written newsletters
                        that match your brand.</p>
                </article>
                <article class="feature rv rv-d2">
                    <div class="icon">🎨</div>
                    <h3>Social posters</h3>
                    <p>Generate on-brand promo graphics for Instagram, Facebook and stories from the
                        same content that powers your site.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ============ HOW IT WORKS ============ -->
    <section class="section" id="how-it-works" style="padding-top: 0;">
        <div class="mk-container">
            <div class="section-head rv">
                <p class="mk-eyebrow">How it works</p>
                <h2>Three steps from idea to live site</h2>
            </div>
            <div class="steps">
                <article class="step rv">
                    <span class="num">01 — DESCRIBE</span>
                    <h3>Tell us about your business</h3>
                    <p>Your story, your services and prices, up to {{ config('sites.max_images') }} photos.
                        A few toggles set the style, colours and sections.</p>
                </article>
                <article class="step rv rv-d1">
                    <span class="num">02 — GENERATE</span>
                    <h3>The AI designs &amp; builds</h3>
                    <p>A complete hand-crafted-quality static site — designed around your actual photos,
                        with your exact products and prices.</p>
                </article>
                <article class="step rv rv-d2">
                    <span class="num">03 — PUBLISH</span>
                    <h3>Preview, then go live</h3>
                    <p>Happy? One click publishes to your own domain with HTTPS.
                        Not happy? Regenerate for a fresh design.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="section" style="padding-top: 0;">
        <div class="mk-container">
            <div class="cta-band rv">
                <p class="mk-eyebrow">Start free</p>
                <h2>Your first website is on us.</h2>
                <p>Sign up, get a free AI credit, and see your business online before your coffee gets cold.</p>
                @auth
                    <a class="mk-btn" href="{{ route('websites.create') }}">Build a website →</a>
                @else
                    <a class="mk-btn" href="{{ route('register') }}">Create my free website →</a>
                @endauth
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
    (function () {
        /* ==================== 3D particle globe ==================== */
        const canvas = document.getElementById('globe');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

        const N = 620;                       // points on the sphere
        const points = [];
        const golden = Math.PI * (3 - Math.sqrt(5));
        for (let i = 0; i < N; i++) {        // fibonacci sphere
            const y = 1 - (i / (N - 1)) * 2;
            const r = Math.sqrt(1 - y * y);
            const t = golden * i;
            points.push({ x: Math.cos(t) * r, y, z: Math.sin(t) * r });
        }

        let W = 0, H = 0, R = 0, cx = 0, cy = 0, dpr = 1;
        function resize() {
            const box = canvas.parentElement.getBoundingClientRect();
            dpr = Math.min(devicePixelRatio || 1, 2);
            W = box.width; H = box.height;
            canvas.width = W * dpr; canvas.height = H * dpr;
            canvas.style.width = W + 'px'; canvas.style.height = H + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            R = Math.min(W, H) * 0.36;
            cx = W * 0.52; cy = H * 0.52;
        }
        resize();
        addEventListener('resize', resize, { passive: true });

        const TILT = -0.35;
        const sinT = Math.sin(TILT), cosT = Math.cos(TILT);

        // travelling arcs: "a site just went live" pulses hopping the globe
        const arcs = [];
        function spawnArc() {
            const a = points[(Math.random() * N) | 0];
            const b = points[(Math.random() * N) | 0];
            arcs.push({ a, b, t: 0, speed: 0.004 + Math.random() * 0.004 });
            if (arcs.length > 5) arcs.shift();
        }
        for (let i = 0; i < 3; i++) spawnArc();

        function project(p, rot) {
            const cosR = Math.cos(rot), sinR = Math.sin(rot);
            let x = p.x * cosR - p.z * sinR;
            let z = p.x * sinR + p.z * cosR;
            let y = p.y * cosT - z * sinT;
            z = p.y * sinT + z * cosT;
            const s = 1.6 / (1.6 + z * 0.9);     // perspective
            return { X: cx + x * R * s, Y: cy + y * R * s, z, s };
        }

        function slerp(a, b, t) {                // spherical interpolation for arcs
            let dot = a.x * b.x + a.y * b.y + a.z * b.z;
            dot = Math.min(1, Math.max(-1, dot));
            const th = Math.acos(dot) || 0.0001;
            const s1 = Math.sin((1 - t) * th) / Math.sin(th);
            const s2 = Math.sin(t * th) / Math.sin(th);
            const lift = 1 + Math.sin(t * Math.PI) * 0.22;   // arc rises off the surface
            return {
                x: (a.x * s1 + b.x * s2) * lift,
                y: (a.y * s1 + b.y * s2) * lift,
                z: (a.z * s1 + b.z * s2) * lift,
            };
        }

        let rot = 0.6;
        function frame() {
            ctx.clearRect(0, 0, W, H);

            // soft halo behind the globe
            const halo = ctx.createRadialGradient(cx, cy, R * 0.5, cx, cy, R * 1.55);
            halo.addColorStop(0, 'rgba(34, 211, 238, 0.10)');
            halo.addColorStop(1, 'rgba(34, 211, 238, 0)');
            ctx.fillStyle = halo;
            ctx.fillRect(0, 0, W, H);

            // points
            for (const p of points) {
                const q = project(p, rot);
                const front = q.z < 0;
                const alpha = front ? 0.55 + (-q.z) * 0.4 : 0.08;
                const size = (front ? 1.6 : 1.0) * q.s;
                ctx.beginPath();
                ctx.arc(q.X, q.Y, size, 0, 7);
                ctx.fillStyle = front
                    ? `rgba(125, 237, 255, ${alpha})`
                    : `rgba(64, 116, 140, ${alpha})`;
                ctx.fill();
            }

            // arcs + pulse heads
            for (const arc of arcs) {
                arc.t += arc.speed;
                const steps = 28;
                ctx.beginPath();
                let headQ = null;
                for (let i = 0; i <= steps; i++) {
                    const t = i / steps;
                    if (t > arc.t) break;
                    const q = project(slerp(arc.a, arc.b, t), rot);
                    if (i === 0) ctx.moveTo(q.X, q.Y); else ctx.lineTo(q.X, q.Y);
                    headQ = q;
                }
                ctx.strokeStyle = 'rgba(34, 211, 238, 0.5)';
                ctx.lineWidth = 1.1;
                ctx.stroke();
                if (headQ) {
                    ctx.beginPath();
                    ctx.arc(headQ.X, headQ.Y, 2.6 * headQ.s, 0, 7);
                    ctx.fillStyle = 'rgba(165, 243, 252, 0.95)';
                    ctx.shadowColor = 'rgba(34, 211, 238, 0.9)';
                    ctx.shadowBlur = 10;
                    ctx.fill();
                    ctx.shadowBlur = 0;
                }
                if (arc.t >= 1) { arcs.splice(arcs.indexOf(arc), 1); spawnArc(); }
            }

            rot += 0.0016;
        }

        if (reduceMotion) {
            frame();                              // single static render
        } else {
            let running = true;
            new IntersectionObserver(([e]) => { running = e.isIntersecting; })
                .observe(canvas);
            document.addEventListener('visibilitychange', () => { running = !document.hidden; });
            (function loop() {
                if (running) frame();
                requestAnimationFrame(loop);
            })();
        }

        /* ==================== pointer parallax on cards ==================== */
        const visual = document.getElementById('hero-visual');
        const cards = [...document.querySelectorAll('.float-card')];
        if (!reduceMotion && matchMedia('(pointer: fine)').matches) {
            visual.addEventListener('mousemove', (e) => {
                const b = visual.getBoundingClientRect();
                const nx = (e.clientX - b.left) / b.width - 0.5;
                const ny = (e.clientY - b.top) / b.height - 0.5;
                for (const card of cards) {
                    const d = +card.dataset.depth || 20;
                    card.style.transform =
                        `translate(${-nx * d}px, ${-ny * d}px) rotateY(${nx * 8}deg) rotateX(${-ny * 8}deg)`;
                }
            });
            visual.addEventListener('mouseleave', () => {
                cards.forEach(c => c.style.transform = '');
            });
        }

        /* ==================== feature-card cursor glow ==================== */
        document.querySelectorAll('.feature').forEach(el => {
            el.addEventListener('mousemove', (e) => {
                const b = el.getBoundingClientRect();
                el.style.setProperty('--mx', ((e.clientX - b.left) / b.width * 100) + '%');
                el.style.setProperty('--my', ((e.clientY - b.top) / b.height * 100) + '%');
            });
        });
    })();
    </script>
@endsection
