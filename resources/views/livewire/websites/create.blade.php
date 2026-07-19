<div>
    <p class="eyebrow">Create</p>
    <h1>Build a new website</h1>
    <p class="muted" style="max-width:40rem; margin-bottom:1.5rem;">
        Choose a site template, describe your business, add photos and offerings —
        and the AI builds a complete static website. Costs {{ $generationCost }} credit.
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
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: .75rem;
            margin: .75rem 0 1.25rem;
        }
        .template-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 1rem;
            background: var(--surface);
            cursor: pointer;
            text-align: left;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .template-card:hover { border-color: color-mix(in srgb, var(--brand) 45%, var(--line)); }
        .template-card.selected {
            border-color: var(--brand);
            background: var(--brand-soft);
            box-shadow: 0 0 0 1px var(--brand);
        }
        .template-card strong { display: block; margin-bottom: .35rem; }
        .template-card .summary { font-size: .9rem; color: var(--foreground); margin: 0 0 .5rem; }
        .template-card .best-for { font-size: .8rem; color: var(--muted); margin: 0; }
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
                    Visual style still comes from your design choices later.
                </p>

                <div class="template-grid" role="radiogroup" aria-label="Site template">
                    @foreach ($templates as $key => $template)
                        <button type="button"
                                class="template-card {{ $site_type === $key ? 'selected' : '' }}"
                                wire:click="$set('site_type', '{{ $key }}')"
                                role="radio"
                                aria-checked="{{ $site_type === $key ? 'true' : 'false' }}">
                            <strong>{{ $template['label'] }}</strong>
                            <p class="summary">{{ $template['summary'] }}</p>
                            <p class="best-for">Best for {{ $template['best_for'] }}</p>
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
                        <span wire:loading.remove wire:target="save">Generate my website ({{ $generationCost }} credit)</span>
                        <span wire:loading wire:target="save">Starting generation…</span>
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
