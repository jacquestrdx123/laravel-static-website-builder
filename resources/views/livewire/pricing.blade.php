<div>
    @php
        $items = collect($pricing['items'])->keyBy('key');
        $generation = $items->get('website_generation');
        $hosting = $items->get('website_hosting');
        $editing = $items->get('editing_without_ai');
        $newsletter = $items->get('newsletter');
        $poster = $items->get('marketing_poster');
    @endphp

    <section class="hero" style="padding-bottom:2rem;">
        <p class="eyebrow reveal reveal-d1">Transparent rates</p>
        <h1 class="reveal reveal-d2">Locked-in pricing</h1>
        <p class="lede reveal reveal-d3">
            1 credit = {{ $pricing['currency_symbol'] }}{{ number_format($pricing['credit_value_zar'], 0) }}.
            Rates stay fixed — no surprise mark-ups on generation, hosting, or marketing tools.
        </p>
        <div class="hero-cta reveal reveal-d4">
            @auth
                <a class="btn" href="{{ route('billing.index') }}">Buy credits</a>
                <a class="btn secondary" href="{{ route('dashboard') }}">Back to dashboard</a>
            @else
                <a class="btn" href="{{ route('register') }}">Get started free</a>
                <a class="btn secondary" href="{{ route('login') }}">Log in</a>
            @endauth
        </div>
    </section>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card reveal-on-scroll" style="margin-bottom:0">
            <h2 style="margin-top:0">{{ $generation['label'] }}</h2>
            <p class="muted">{{ $generation['description'] }}</p>
            <p style="font-size:1.35rem; margin:.5rem 0 0"><strong>{{ $generation['credits_label'] }}</strong></p>
            <p class="hint" style="margin:.2rem 0 0">{{ $generation['zar_label'] }}</p>
        </div>
        <div class="card reveal-on-scroll" style="margin-bottom:0">
            <h2 style="margin-top:0">{{ $hosting['label'] }}</h2>
            <p class="muted">{{ $hosting['description'] }}</p>
            <p style="font-size:1.35rem; margin:.5rem 0 0"><strong>{{ $hosting['credits_label'] }}</strong></p>
            <p class="hint" style="margin:.2rem 0 0">{{ $hosting['zar_label'] }}</p>
        </div>
        <div class="card reveal-on-scroll" style="margin-bottom:0">
            <h2 style="margin-top:0">{{ $editing['label'] }}</h2>
            <p class="muted">{{ $editing['description'] }}</p>
            <p style="font-size:1.15rem; margin:.5rem 0 0"><strong>{{ $editing['credits_label'] }}</strong></p>
            <p class="hint" style="margin:.2rem 0 0">{{ $editing['zar_label'] }}</p>
        </div>
        <div class="card reveal-on-scroll" style="margin-bottom:0">
            <h2 style="margin-top:0">{{ $poster['label'] }}</h2>
            <p class="muted">{{ $poster['description'] }}</p>
            <p style="font-size:1.35rem; margin:.5rem 0 0"><strong>{{ $poster['credits_label'] }}</strong></p>
            <p class="hint" style="margin:.2rem 0 0">{{ $poster['zar_label'] }}</p>
        </div>
    </div>

    <div class="card reveal-on-scroll" style="border-color: color-mix(in srgb, var(--brand) 45%, var(--line));">
        <p class="eyebrow" style="margin-bottom:.4rem;">Featured</p>
        <h2 style="margin-top:0">{{ $newsletter['label'] }}</h2>
        <p>{{ $newsletter['description'] }}</p>
        <p style="font-size:1.2rem; margin:1rem 0 .3rem"><strong>{{ $newsletter['credits_label'] }}</strong></p>
        <p class="hint" style="margin:0">{{ $newsletter['zar_label'] }} · Extra emails: {{ $newsletter['extra_emails_label'] }} ({{ $newsletter['extra_emails_zar_label'] }})</p>

        <ul style="margin:1.2rem 0 0; padding-left:1.2rem">
            @foreach ($newsletter['features'] as $feature)
                <li style="margin:.35rem 0">{{ $feature }}</li>
            @endforeach
        </ul>
    </div>
</div>
