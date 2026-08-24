<div>
    <p class="eyebrow">Create</p>
    <h1>Build a new website</h1>
    <p class="muted" style="max-width:40rem; margin-bottom:1.5rem;">
        Choose a site template, describe your business, add photos and offerings —
        and the AI builds a complete static website. Costs {{ $generationCost }} credits.
    </p>

    <style>
        .wizard-progress {
            display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .wizard-step-label {
            flex: 1; min-width: 120px; text-align: center; padding: .6rem .8rem;
            border-radius: 999px; border: 1px solid var(--line); background: var(--surface);
            font-size: .85rem; color: var(--muted); transition: all .2s; cursor: pointer;
        }
        .wizard-step-label.active { background: var(--foreground); color: var(--background); border-color: var(--foreground); font-weight: 600; }
        .wizard-step-label.done { background: var(--brand-soft); color: var(--brand); border-color: color-mix(in srgb, var(--brand) 35%, var(--line)); }
        .wizard-nav { display: flex; justify-content: space-between; gap: .6rem; margin-top: 1.5rem; flex-wrap: wrap; }
        .photo-slot {
            border: 1px solid var(--line); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;
        }
        .photo-slot h3 { margin: 0 0 .4rem; font-size: 1rem; }
        .photo-preview {
            width: 96px; height: 96px; border: 2px dashed var(--line); border-radius: 6px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            overflow: hidden; background: var(--paper); margin-bottom: .6rem;
        }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
        .photo-preview .placeholder { color: var(--ink-soft); font-size: .75rem; text-align: center; padding: .4rem; }
        .template-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin: 1rem 0 1.75rem;
        }
        .template-card {
            appearance: none;
            -webkit-appearance: none;
            display: flex;
            flex-direction: column;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            text-align: left;
            font: inherit;
            font-weight: 400;
            color: var(--foreground);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 1rem;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }
        .template-card:hover,
        .template-card:focus-visible {
            opacity: 1;
            color: var(--foreground);
            border-color: color-mix(in srgb, var(--brand) 45%, var(--line));
            box-shadow: 0 10px 28px -18px rgba(15, 23, 42, .35);
            transform: translateY(-2px);
        }
        .template-card:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px var(--brand-soft), 0 10px 28px -18px rgba(15, 23, 42, .35);
        }
        .template-card.selected {
            border-color: var(--brand);
            background: var(--surface);
            box-shadow: 0 0 0 1px var(--brand), 0 12px 28px -16px color-mix(in srgb, var(--brand) 55%, transparent);
        }
        .template-card.selected:hover { opacity: 1; color: var(--foreground); }
        .template-thumb {
            position: relative;
            display: block;
            height: 138px;
            overflow: hidden;
            border-bottom: 1px solid var(--line);
            background: #e8eef2;
        }
        .thumb-chrome {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 4px;
            height: 16px;
            padding: 0 10px;
            background: rgba(255, 255, 255, .55);
        }
        .thumb-chrome b {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: rgba(15, 23, 42, .18);
        }
        .thumb-canvas { position: absolute; inset: 16px 0 0; }
        .thumb-canvas .t { position: absolute; border-radius: 4px; }
        .template-check {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
            display: grid;
            place-items: center;
            width: 1.45rem;
            height: 1.45rem;
            border-radius: 50%;
            background: var(--brand);
            color: #fff;
            opacity: 0;
            transform: scale(.85);
            transition: opacity .15s ease, transform .15s ease;
        }
        .template-card.selected .template-check { opacity: 1; transform: none; }
        .template-check svg { width: .85rem; height: .85rem; }
        .template-body { display: flex; flex-direction: column; gap: .45rem; padding: 1rem 1.05rem 1.1rem; flex: 1; }
        .template-card strong {
            display: block;
            margin: 0;
            font-size: 1rem;
            font-weight: 650;
            letter-spacing: -0.02em;
            color: var(--foreground);
        }
        .template-card .summary {
            margin: 0;
            font-size: .875rem;
            line-height: 1.45;
            color: var(--muted);
            text-wrap: pretty;
        }
        .template-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: auto; padding-top: .25rem; }
        .template-tags span {
            font-size: .7rem;
            font-weight: 500;
            letter-spacing: .01em;
            color: var(--muted);
            background: color-mix(in srgb, var(--foreground) 4%, var(--surface));
            border: 1px solid var(--line);
            border-radius: var(--radius-pill);
            padding: .15rem .5rem;
        }
        .template-card.selected .template-tags span {
            color: var(--brand);
            background: var(--brand-soft);
            border-color: color-mix(in srgb, var(--brand) 28%, var(--line));
        }
        .template-card[data-type="business"] { --thumb-bg: #d5e6ec; --thumb-ink: #16323d; --thumb-accent: #0e7490; }
        .template-card[data-type="portfolio"] { --thumb-bg: #1c1917; --thumb-ink: #e7e5e4; --thumb-accent: #d4a017; }
        .template-card[data-type="restaurant"] { --thumb-bg: #3f4636; --thumb-ink: #e8e4d4; --thumb-accent: #c4b48a; }
        .template-card[data-type="landing"] { --thumb-bg: #eef2ff; --thumb-ink: #1e1b4b; --thumb-accent: #6366f1; }
        .template-card[data-type="personal"] { --thumb-bg: #f4ece4; --thumb-ink: #3f2e22; --thumb-accent: #c2410c; }
        .template-card[data-type="event"] { --thumb-bg: #111827; --thumb-ink: #f8fafc; --thumb-accent: #f59e0b; }
        .template-thumb { background: var(--thumb-bg, #e8eef2); }
        .template-card[data-type="business"] .t1 { left: 8%; right: 8%; top: 8%; height: 38%; background: color-mix(in srgb, var(--thumb-ink) 18%, white); }
        .template-card[data-type="business"] .t2,
        .template-card[data-type="business"] .t3,
        .template-card[data-type="business"] .t4 { top: 54%; height: 28%; width: 24%; background: #fff; box-shadow: 0 2px 6px rgba(15,23,42,.08); }
        .template-card[data-type="business"] .t2 { left: 8%; }
        .template-card[data-type="business"] .t3 { left: 38%; }
        .template-card[data-type="business"] .t4 { left: 68%; }
        .template-card[data-type="business"] .t5 { display: none; }
        .template-card[data-type="portfolio"] .t1 { left: 6%; top: 8%; width: 42%; height: 84%; background: color-mix(in srgb, var(--thumb-accent) 35%, #292524); }
        .template-card[data-type="portfolio"] .t2 { right: 6%; top: 8%; width: 42%; height: 38%; background: #44403c; }
        .template-card[data-type="portfolio"] .t3 { right: 6%; bottom: 8%; width: 20%; height: 38%; background: #57534e; }
        .template-card[data-type="portfolio"] .t4 { right: 28%; bottom: 8%; width: 20%; height: 38%; background: color-mix(in srgb, var(--thumb-accent) 50%, #292524); }
        .template-card[data-type="portfolio"] .t5 { display: none; }
        .template-card[data-type="restaurant"] .t1 { left: 0; right: 36%; top: 0; bottom: 0; border-radius: 0; background: color-mix(in srgb, var(--thumb-accent) 22%, #2a3024); }
        .template-card[data-type="restaurant"] .t2,
        .template-card[data-type="restaurant"] .t3,
        .template-card[data-type="restaurant"] .t4 { right: 8%; height: 10%; width: 26%; background: color-mix(in srgb, var(--thumb-ink) 35%, transparent); }
        .template-card[data-type="restaurant"] .t2 { top: 18%; }
        .template-card[data-type="restaurant"] .t3 { top: 38%; }
        .template-card[data-type="restaurant"] .t4 { top: 58%; }
        .template-card[data-type="restaurant"] .t5 { right: 8%; bottom: 12%; width: 18%; height: 14%; border-radius: 999px; background: var(--thumb-accent); }
        .template-card[data-type="landing"] .t1 { left: 18%; right: 18%; top: 16%; height: 12%; background: var(--thumb-ink); opacity: .85; }
        .template-card[data-type="landing"] .t2 { left: 28%; right: 28%; top: 34%; height: 7%; background: color-mix(in srgb, var(--thumb-ink) 25%, white); }
        .template-card[data-type="landing"] .t3 { left: 38%; width: 24%; top: 48%; height: 14%; border-radius: 999px; background: var(--thumb-accent); }
        .template-card[data-type="landing"] .t4,
        .template-card[data-type="landing"] .t5 { bottom: 14%; height: 16%; width: 18%; background: #fff; box-shadow: 0 2px 6px rgba(15,23,42,.08); }
        .template-card[data-type="landing"] .t4 { left: 22%; }
        .template-card[data-type="landing"] .t5 { right: 22%; }
        .template-card[data-type="personal"] .t1 { left: 10%; top: 18%; width: 28px; height: 28px; border-radius: 50%; background: var(--thumb-accent); }
        .template-card[data-type="personal"] .t2 { left: 10%; right: 18%; top: 48%; height: 8%; background: color-mix(in srgb, var(--thumb-ink) 55%, white); }
        .template-card[data-type="personal"] .t3,
        .template-card[data-type="personal"] .t4 { left: 10%; height: 6%; background: color-mix(in srgb, var(--thumb-ink) 18%, white); }
        .template-card[data-type="personal"] .t3 { right: 28%; top: 62%; }
        .template-card[data-type="personal"] .t4 { right: 40%; top: 74%; }
        .template-card[data-type="personal"] .t5 { display: none; }
        .template-card[data-type="event"] .t1 { left: 8%; top: 14%; width: 22%; height: 42%; background: var(--thumb-accent); }
        .template-card[data-type="event"] .t2 { left: 36%; right: 10%; top: 16%; height: 12%; background: #fff; }
        .template-card[data-type="event"] .t3,
        .template-card[data-type="event"] .t4,
        .template-card[data-type="event"] .t5 { left: 36%; right: 10%; height: 10%; background: color-mix(in srgb, var(--thumb-ink) 14%, #1f2937); }
        .template-card[data-type="event"] .t3 { top: 38%; }
        .template-card[data-type="event"] .t4 { top: 54%; }
        .template-card[data-type="event"] .t5 { top: 70%; }
        @media (max-width: 900px) {
            .template-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .template-grid { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            .template-card:hover, .template-card:focus-visible { transform: none; }
        }
        .coming-soon { opacity: .65; }
    </style>

    <form wire:submit="save">
        <div class="wizard-progress" aria-label="Wizard progress">
            @foreach ([1 => '1. Template & about', 2 => '2. Photos', 3 => '3. Offerings', 4 => '4. Design'] as $n => $label)
                <button type="button" class="wizard-step-label {{ $step === $n ? 'active' : '' }} {{ $step > $n ? 'done' : '' }}"
                        wire:click="goToStep({{ $n }})">{{ $label }}</button>
            @endforeach
        </div>

        @if ($step === 1)
            <div class="card">
                <h2 style="margin-top:0">Choose a site template</h2>
                <p class="hint" style="margin-top:0">
                    Each template sets the page structure the AI follows — section order, content markers, and tone.
                    Visual style is directed later from your design choices and our composition library.
                </p>

                <div class="template-grid" role="radiogroup" aria-label="Site template">
                    @foreach ($templates as $key => $template)
                        <button type="button"
                                class="template-card {{ $site_type === $key ? 'selected' : '' }}"
                                data-type="{{ $key }}"
                                wire:click="$set('site_type', '{{ $key }}')"
                                role="radio"
                                aria-checked="{{ $site_type === $key ? 'true' : 'false' }}">
                            <span class="template-thumb" aria-hidden="true">
                                <span class="thumb-chrome"><b></b><b></b><b></b></span>
                                <span class="thumb-canvas">
                                    <span class="t t1"></span>
                                    <span class="t t2"></span>
                                    <span class="t t3"></span>
                                    <span class="t t4"></span>
                                    <span class="t t5"></span>
                                </span>
                                <span class="template-check">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                            </span>
                            <span class="template-body">
                                <strong>{{ $template['label'] }}</strong>
                                <p class="summary">{{ $template['summary'] }}</p>
                                <span class="template-tags">
                                    @foreach (explode(',', $template['best_for']) as $tag)
                                        <span>{{ trim($tag) }}</span>
                                    @endforeach
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
                @error('site_type')<div class="error">{{ $message }}</div>@enderror

                <h2>Tell us about your business</h2>

                <label for="name">Business / site name</label>
                <input id="name" type="text" wire:model="name" required maxlength="100">
                @error('name')<div class="error">{{ $message }}</div>@enderror

                <label for="tagline">Tagline <span class="hint">(optional)</span></label>
                <input id="tagline" type="text" wire:model="tagline" maxlength="200">

                <label for="description">Describe your business and what the site should say</label>
                <p class="hint" style="margin-top: 0.25rem; margin-bottom: 0.5rem;">The more detail you provide, the better your website will be.</p>
                <textarea id="description" wire:model="description" required maxlength="2000"
                    placeholder="e.g. We are a family-run bakery in Stellenbosch specialising in sourdough and wedding cakes."></textarea>
                @error('description')<div class="error">{{ $message }}</div>@enderror

                <label for="contact_email">Contact email shown on the site <span class="hint">(optional)</span></label>
                <input id="contact_email" type="email" wire:model="contact_email">
                @error('contact_email')<div class="error">{{ $message }}</div>@enderror
            </div>
        @endif

        @if ($step === 2)
            <div class="card">
                <h2 style="margin-top:0">Your photos</h2>
                <p class="hint">JPEG, PNG, GIF or WebP. Max 8&nbsp;MB each. Up to {{ $maxPhotos }} photos total.</p>

                <div class="photo-slot">
                    <h3>Logo</h3>
                    <div class="photo-preview">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview">
                        @else
                            <span class="placeholder">No logo yet</span>
                        @endif
                    </div>
                    <input type="file" wire:model="logo" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div wire:loading wire:target="logo" class="hint">Uploading…</div>
                    @error('logo')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="photo-slot">
                    <h3>Favicon <span class="hint">(optional)</span></h3>
                    <div class="photo-preview">
                        @if ($favicon)
                            <img src="{{ $favicon->temporaryUrl() }}" alt="Favicon preview">
                        @else
                            <span class="placeholder">No favicon yet</span>
                        @endif
                    </div>
                    <input type="file" wire:model="favicon" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="coming-soon" style="margin-top: .8rem;">
                        <label>
                            <input type="checkbox" wire:model="generate_favicon_from_logo" disabled>
                            Generate favicon from logo
                        </label>
                        <p class="hint">Coming soon — we'll automatically create a favicon from your logo.</p>
                    </div>
                    @error('favicon')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="photo-slot">
                    <h3>Banner</h3>
                    <div class="photo-preview" style="width: 100%; max-width: 320px; height: 120px;">
                        @if ($banner)
                            <img src="{{ $banner->temporaryUrl() }}" alt="Banner preview">
                        @else
                            <span class="placeholder">No banner yet</span>
                        @endif
                    </div>
                    <input type="file" wire:model="banner" accept="image/jpeg,image/png,image/gif,image/webp">
                    @error('banner')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="photo-slot">
                    <h3>Other photos <span class="hint">(optional)</span></h3>
                    @foreach ($galleryRows as $i => $row)
                        <div wire:key="gallery-{{ $i }}" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                <div class="photo-preview" style="width: 80px; height: 80px;">
                                    @if ($row['file'])
                                        <img src="{{ $row['file']->temporaryUrl() }}" alt="Gallery preview">
                                    @else
                                        <span class="placeholder">No photo</span>
                                    @endif
                                </div>
                                <div style="flex: 1;">
                                    <label style="margin-top: 0;">Choose photo</label>
                                    <input type="file" wire:model="galleryRows.{{ $i }}.file" accept="image/jpeg,image/png,image/gif,image/webp">
                                    <label style="margin-top: 0.5rem;">What does this photo show?</label>
                                    <input type="text" wire:model="galleryRows.{{ $i }}.description" maxlength="200"
                                           placeholder="e.g. Our storefront">
                                </div>
                            </div>
                            <button type="button" class="btn secondary" style="margin-top:.6rem; padding:.25rem .8rem;"
                                    wire:click="removeGalleryRow({{ $i }})">Remove</button>
                            @error('galleryRows.'.$i.'.file')<div class="error">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    @if (count($galleryRows) < $maxPhotos)
                        <button type="button" class="btn secondary" wire:click="addGalleryRow">+ Add another photo</button>
                    @endif
                </div>
            </div>
        @endif

        @if ($step === 3)
            <div class="card">
                <h2 style="margin-top:0">Your services or products <span class="hint">(optional)</span></h2>

                <label>These are…</label>
                <div class="choices">
                    @foreach ($offeringTypes as $type)
                        <label>
                            <input type="radio" wire:model="offering_type" value="{{ $type }}">
                            {{ ucfirst($type) }}
                        </label>
                    @endforeach
                </div>

                <label for="offering_label">What should this section be called? <span class="hint">(optional)</span></label>
                <input id="offering_label" type="text" wire:model="offering_label" maxlength="50"
                       placeholder="e.g. Our Treatments, Packages, Offerings">

                <label style="margin-top: 1rem;">
                    <input type="checkbox" wire:model="ai_elaborate_offerings">
                    Let AI expand and improve descriptions
                </label>

                <div style="margin-top: 1rem;">
                    @foreach ($offerings as $i => $offering)
                        <fieldset wire:key="offering-{{ $i }}" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                            <div class="grid-2">
                                <div>
                                    <label style="margin-top:0">Name</label>
                                    <input type="text" wire:model="offerings.{{ $i }}.name" maxlength="100"
                                           placeholder="e.g. Full-day island tour">
                                </div>
                                <div>
                                    <label style="margin-top:0">Price <span class="hint">(optional)</span></label>
                                    <input type="text" wire:model="offerings.{{ $i }}.price" maxlength="50"
                                           placeholder="e.g. R1,450">
                                </div>
                            </div>
                            <label>Short description <span class="hint">(optional)</span></label>
                            <input type="text" wire:model="offerings.{{ $i }}.description" maxlength="500">
                            <label>Photo <span class="hint">(optional)</span></label>
                            <div class="photo-preview" style="width: 80px; height: 80px; margin-bottom: .4rem;">
                                @if ($offering['image'])
                                    <img src="{{ $offering['image']->temporaryUrl() }}" alt="Product preview">
                                @else
                                    <span class="placeholder">No photo</span>
                                @endif
                            </div>
                            <input type="file" wire:model="offerings.{{ $i }}.image" accept="image/jpeg,image/png,image/gif,image/webp">
                            <button type="button" class="btn secondary" style="margin-top:.6rem; padding:.25rem .8rem"
                                    wire:click="removeOffering({{ $i }})">Remove</button>
                        </fieldset>
                    @endforeach
                </div>
                @if (count($offerings) < $maxOfferings)
                    <button type="button" class="btn secondary" wire:click="addOffering">+ Add another</button>
                @endif
            </div>
        @endif

        @if ($step === 4)
            <div class="card">
                <h2 style="margin-top:0">Choose your options</h2>

                <p class="hint">Template: <strong>{{ $templates[$site_type]['label'] ?? ucfirst($site_type) }}</strong>
                    — change it on step 1 if needed.</p>

                <label>Sections to include</label>
                <div class="choices">
                    @foreach ($allSections as $section)
                        <label>
                            <input type="checkbox" wire:model="sections" value="{{ $section }}">
                            {{ ucfirst($section) }}
                        </label>
                    @endforeach
                </div>
                @error('sections')<div class="error">{{ $message }}</div>@enderror

                <div class="grid-2">
                    <div>
                        <label>Design style</label>
                        <div class="choices">
                            @foreach ($styles as $styleOption)
                                <label>
                                    <input type="radio" wire:model="style" value="{{ $styleOption }}">
                                    {{ ucfirst($styleOption) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label>Color scheme</label>
                        <div class="choices">
                            @foreach ($colorSchemes as $scheme)
                                <label>
                                    <input type="radio" wire:model="color_scheme" value="{{ $scheme }}">
                                    {{ ucfirst($scheme) }}
                                </label>
                            @endforeach
                        </div>
                        <label for="accent_color">Accent colour <span class="hint">(optional)</span></label>
                        <input id="accent_color" type="color" wire:model="accent_color">
                    </div>
                </div>

                <label>Extras</label>
                <div class="choices">
                    @foreach ($featureLabels as $feature => $featureLabel)
                        <label>
                            <input type="checkbox" wire:model="features" value="{{ $feature }}">
                            {{ $featureLabel }}
                        </label>
                    @endforeach
                </div>

                <label for="extra_instructions">Anything else the designer should know? <span class="hint">(optional)</span></label>
                <textarea id="extra_instructions" wire:model="extra_instructions" maxlength="2000"
                    placeholder="e.g. Mention our Tuesday special. Keep the tone playful."></textarea>
            </div>
        @endif

        <div class="wizard-nav" style="margin-bottom: 3rem;">
            <div class="actions">
                @if ($step > 1)
                    <button type="button" class="btn secondary" wire:click="previousStep">Back</button>
                @endif
                <a class="btn secondary" href="{{ route('dashboard') }}">Cancel</a>
            </div>
            <div class="actions">
                @if ($step < 4)
                    <button type="button" class="btn" wire:click="nextStep">Next</button>
                @else
                    <button type="submit" class="btn" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Generate my website ({{ $generationCost }} credits)</span>
                        <span wire:loading wire:target="save">Starting generation…</span>
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
