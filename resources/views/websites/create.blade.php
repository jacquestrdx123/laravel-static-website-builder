@extends('layouts.app')

@section('title', 'New website')

@section('content')
    <p class="eyebrow">Create</p>
    <h1>Build a new website</h1>
    <p class="muted" style="max-width:40rem; margin-bottom:1.5rem;">
        A short step-by-step wizard — describe your business, add your photos, list your offerings,
        and the AI builds you a complete static website. Costs {{ config('sites.generation_cost') }} credit.
    </p>

    <style>
        .wizard-progress {
            display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .wizard-step-label {
            flex: 1; min-width: 120px; text-align: center; padding: .6rem .8rem;
            border-radius: 999px; border: 1px solid var(--line); background: var(--surface);
            font-size: .85rem; color: var(--muted); transition: all .2s;
        }
        .wizard-step-label.active { background: var(--foreground); color: var(--background); border-color: var(--foreground); font-weight: 600; }
        .wizard-step-label.done { background: var(--brand-soft); color: var(--brand); border-color: color-mix(in srgb, var(--brand) 35%, var(--line)); }
        .wizard-panel { display: none; }
        .wizard-panel.active { display: block; }
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
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .photo-preview .placeholder { color: var(--ink-soft); font-size: .75rem; text-align: center; padding: .4rem; }
        .coming-soon { opacity: .65; }
    </style>

    <form id="wizard-form" method="POST" action="{{ route('websites.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="wizard-progress" aria-label="Wizard progress">
            <div class="wizard-step-label active" data-step-label="1">1. About</div>
            <div class="wizard-step-label" data-step-label="2">2. Photos</div>
            <div class="wizard-step-label" data-step-label="3">3. Offerings</div>
            <div class="wizard-step-label" data-step-label="4">4. Design</div>
        </div>

        {{-- Step 1: About --}}
        <div class="card wizard-panel active" data-step="1">
            <h2 style="margin-top:0">Tell us about your business</h2>

            <label for="name">Business / site name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="100">
            @error('name')<div class="error">{{ $message }}</div>@enderror

            <label for="tagline">Tagline <span class="hint">(optional)</span></label>
            <input id="tagline" type="text" name="tagline" value="{{ old('tagline') }}" maxlength="200">

            <label for="description">Describe your business and what the site should say</label>
            <p class="hint" style="margin-top: 0.25rem; margin-bottom: 0.5rem;">The more detail you provide, the better your website will be.
                Include what makes you unique, your story, key services, location, opening hours — anything you'd want visitors to know.</p>
            <textarea id="description" name="description" required maxlength="2000"
                placeholder="e.g. We are a family-run bakery in Stellenbosch specialising in sourdough and wedding cakes. Open Tue-Sun...">{{ old('description') }}</textarea>
            @error('description')<div class="error">{{ $message }}</div>@enderror

            <label for="contact_email">Contact email shown on the site <span class="hint">(optional)</span></label>
            <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}">
        </div>

        {{-- Step 2: Photos --}}
        <div class="card wizard-panel" data-step="2">
            <h2 style="margin-top:0">Your photos</h2>
            <p class="hint">Great visuals make a professional site. Upload a logo and banner if you have them,
                then add any other photos for the gallery. Product photos are added in the next step.</p>
            <p class="hint">JPEG, PNG, GIF or WebP. Max 8&nbsp;MB each. Up to {{ config('sites.max_images') }} photos total across all uploads.</p>
            @error('logo')<div class="error">{{ $message }}</div>@enderror
            @error('favicon')<div class="error">{{ $message }}</div>@enderror
            @error('banner')<div class="error">{{ $message }}</div>@enderror
            @error('gallery_images')<div class="error">{{ $message }}</div>@enderror
            @error('gallery_images.*')<div class="error">{{ $message }}</div>@enderror
            @error('gallery_descriptions.*')<div class="error">{{ $message }}</div>@enderror

            <div class="photo-slot">
                <h3>Logo</h3>
                <p class="hint">Your business logo — shown in the header and footer.</p>
                <div class="photo-preview" data-preview="logo">
                    <span class="placeholder">No logo yet</span>
                    <img alt="Logo preview">
                </div>
                <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" data-preview-target="logo">
            </div>

            <div class="photo-slot">
                <h3>Favicon <span class="hint">(optional)</span></h3>
                <p class="hint">The small icon in the browser tab. Square images work best.</p>
                <div class="photo-preview" data-preview="favicon">
                    <span class="placeholder">No favicon yet</span>
                    <img alt="Favicon preview">
                </div>
                <input type="file" id="favicon" name="favicon" accept="image/jpeg,image/png,image/gif,image/webp" data-preview-target="favicon">

                <div class="coming-soon" style="margin-top: .8rem;">
                    <label>
                        <input type="checkbox" name="generate_favicon_from_logo" value="1" disabled
                               @checked(old('generate_favicon_from_logo'))>
                        Generate favicon from logo
                    </label>
                    <p class="hint">Coming soon — we'll automatically create a favicon from your logo.</p>
                </div>
            </div>

            <div class="photo-slot">
                <h3>Banner</h3>
                <p class="hint">A wide hero image for the top of your site — storefront, team, product lineup, etc.</p>
                <div class="photo-preview" data-preview="banner" style="width: 100%; max-width: 320px; height: 120px;">
                    <span class="placeholder">No banner yet</span>
                    <img alt="Banner preview">
                </div>
                <input type="file" id="banner" name="banner" accept="image/jpeg,image/png,image/gif,image/webp" data-preview-target="banner">
            </div>

            <div class="photo-slot">
                <h3>Other photos <span class="hint">(optional)</span></h3>
                <p class="hint">General photos for your gallery, about section, and elsewhere on the site.
                    Add a short note so the AI knows what each photo shows.</p>

                <div id="gallery-uploads">
                    <div class="gallery-row" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                        <div style="display: flex; gap: 1rem; align-items: flex-start;">
                            <div class="photo-preview" style="width: 80px; height: 80px;">
                                <span class="placeholder">No photo</span>
                                <img alt="Gallery preview">
                            </div>
                            <div style="flex: 1;">
                                <label style="margin-top: 0;">Choose photo</label>
                                <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/gif,image/webp" class="gallery-input">
                                <label style="margin-top: 0.5rem;">What does this photo show? <span class="hint">(optional)</span></label>
                                <input type="text" name="gallery_descriptions[]" maxlength="200" class="gallery-description"
                                       placeholder="e.g. Our storefront, Team photo, Workshop interior">
                            </div>
                        </div>
                        <button type="button" class="btn secondary remove-gallery" style="margin-top: .6rem; padding: .25rem .8rem;">Remove</button>
                    </div>
                </div>
                <button type="button" id="add-gallery" class="btn secondary">+ Add another photo</button>
            </div>
        </div>

        {{-- Step 3: Offerings --}}
        <div class="card wizard-panel" data-step="3">
            <h2 style="margin-top:0">Your services or products <span class="hint">(optional)</span></h2>
            <p class="hint">List what you offer and the AI will feature each item on the site with
                your exact names and prices. Upload a photo for each item right here. Leave empty to let the AI write this from your description.</p>

            <label>These are…</label>
            <div class="choices">
                @foreach (\App\Http\Controllers\WebsiteController::OFFERING_TYPES as $type)
                    <label><input type="radio" name="offering_type" value="{{ $type }}"
                        @checked(old('offering_type', 'services') === $type)>{{ ucfirst($type) }}</label>
                @endforeach
            </div>

            <label for="offering_label">What should this section be called? <span class="hint">(optional)</span></label>
            <input id="offering_label" type="text" name="offering_label" maxlength="50"
                   placeholder="e.g. Our Treatments, Packages, Offerings"
                   value="{{ old('offering_label') }}">
            @error('offering_label')<div class="error">{{ $message }}</div>@enderror

            <label style="margin-top: 1rem;">
                <input type="checkbox" name="ai_elaborate_offerings" value="1"
                       @checked(old('ai_elaborate_offerings'))>
                Let AI expand and improve descriptions
            </label>
            <p class="hint">When checked, the AI will elaborate on short descriptions to make them more compelling.
                Names and prices stay exactly as you enter them.</p>

            <div id="offerings" style="margin-top: 1rem;">
                @foreach (old('offerings', [['name' => '', 'description' => '', 'price' => '']]) as $i => $offering)
                    <fieldset class="offering-row" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                        <div class="grid-2">
                            <div>
                                <label style="margin-top:0">Name</label>
                                <input type="text" name="offerings[{{ $i }}][name]" maxlength="100"
                                       value="{{ $offering['name'] ?? '' }}" placeholder="e.g. Full-day island tour">
                            </div>
                            <div>
                                <label style="margin-top:0">Price <span class="hint">(optional)</span></label>
                                <input type="text" name="offerings[{{ $i }}][price]" maxlength="50"
                                       value="{{ $offering['price'] ?? '' }}" placeholder="e.g. R1,450 / from R99">
                            </div>
                        </div>
                        <label>Short description <span class="hint">(optional)</span></label>
                        <input type="text" name="offerings[{{ $i }}][description]" maxlength="500"
                               value="{{ $offering['description'] ?? '' }}" placeholder="One or two sentences about this item">
                        <label>Photo <span class="hint">(optional)</span></label>
                        <div class="photo-preview" style="width: 80px; height: 80px; margin-bottom: .4rem;">
                            <span class="placeholder">No photo</span>
                            <img alt="Product preview">
                        </div>
                        <input type="file" name="offerings[{{ $i }}][image]" accept="image/jpeg,image/png,image/gif,image/webp" class="offering-photo-input">
                        <button type="button" class="btn secondary remove-offering" style="margin-top:.6rem; padding:.25rem .8rem">Remove</button>
                    </fieldset>
                @endforeach
            </div>
            <button type="button" id="add-offering" class="btn secondary">+ Add another</button>
            @error('offerings')<div class="error">{{ $message }}</div>@enderror
            @error('offerings.*.image')<div class="error">{{ $message }}</div>@enderror
        </div>

        {{-- Step 4: Design --}}
        <div class="card wizard-panel" data-step="4">
            <h2 style="margin-top:0">Choose your options</h2>

            <label>Type of site</label>
            <div class="choices">
                @foreach (\App\Http\Controllers\WebsiteController::SITE_TYPES as $type)
                    <label><input type="radio" name="site_type" value="{{ $type }}"
                        @checked(old('site_type', 'business') === $type)>{{ ucfirst($type) }}</label>
                @endforeach
            </div>

            <label>Sections to include</label>
            <div class="choices">
                @foreach (\App\Http\Controllers\WebsiteController::SECTIONS as $section)
                    <label><input type="checkbox" name="sections[]" value="{{ $section }}"
                        @checked(in_array($section, old('sections', ['hero', 'about', 'gallery', 'contact'])))>{{ ucfirst($section) }}</label>
                @endforeach
            </div>
            @error('sections')<div class="error">{{ $message }}</div>@enderror

            <div class="grid-2">
                <div>
                    <label>Design style</label>
                    <div class="choices">
                        @foreach (\App\Http\Controllers\WebsiteController::STYLES as $style)
                            <label><input type="radio" name="style" value="{{ $style }}"
                                @checked(old('style', 'minimal') === $style)>{{ ucfirst($style) }}</label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label>Color scheme</label>
                    <div class="choices">
                        @foreach (\App\Http\Controllers\WebsiteController::COLOR_SCHEMES as $scheme)
                            <label><input type="radio" name="color_scheme" value="{{ $scheme }}"
                                @checked(old('color_scheme', 'light') === $scheme)>{{ ucfirst($scheme) }}</label>
                        @endforeach
                    </div>
                    <label for="accent_color">Accent colour <span class="hint">(optional)</span></label>
                    <input id="accent_color" type="color" name="accent_color" value="{{ old('accent_color', '#0e7a5f') }}">
                </div>
            </div>

            <label>Extras</label>
            <div class="choices">
                @php
                    $featureLabels = [
                        'smooth_scroll' => 'Smooth scrolling',
                        'animations' => 'Subtle animations',
                        'sticky_header' => 'Sticky header',
                        'back_to_top' => 'Back-to-top button',
                        'seo_meta' => 'SEO meta tags',
                        'contact_form' => 'Contact form',
                    ];
                @endphp
                @foreach (\App\Http\Controllers\WebsiteController::FEATURES as $feature)
                    <label><input type="checkbox" name="features[]" value="{{ $feature }}"
                        @checked(in_array($feature, old('features', ['seo_meta', 'smooth_scroll'])))>{{ $featureLabels[$feature] }}</label>
                @endforeach
            </div>

            <label for="extra_instructions">Anything else the designer should know? <span class="hint">(optional)</span></label>
            <p class="hint" style="margin-top: 0.25rem; margin-bottom: 0.5rem;">Specific requests help the AI tailor your site perfectly —
                mention tone, special offers, key phrases, or anything else you'd like highlighted.</p>
            <textarea id="extra_instructions" name="extra_instructions" maxlength="2000"
                placeholder="e.g. Mention our Tuesday special. Keep the tone playful.">{{ old('extra_instructions') }}</textarea>
        </div>

        <div class="wizard-nav" style="margin-bottom: 3rem;">
            <div class="actions">
                <button type="button" id="wizard-back" class="btn secondary" style="display: none;">Back</button>
                <a class="btn secondary" href="{{ route('dashboard') }}">Cancel</a>
            </div>
            <div class="actions">
                <button type="button" id="wizard-next" class="btn">Next</button>
                <button type="submit" id="wizard-submit" class="btn" style="display: none;">
                    Generate my website ({{ config('sites.generation_cost') }} credit)
                </button>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const totalSteps = 4;
            let currentStep = 1;
            const maxOfferings = {{ \App\Http\Controllers\WebsiteController::MAX_OFFERINGS }};
            const maxPhotos = {{ config('sites.max_images') }};

            const panels = document.querySelectorAll('.wizard-panel');
            const labels = document.querySelectorAll('[data-step-label]');
            const backBtn = document.getElementById('wizard-back');
            const nextBtn = document.getElementById('wizard-next');
            const submitBtn = document.getElementById('wizard-submit');
            const form = document.getElementById('wizard-form');

            function showStep(step) {
                currentStep = step;
                panels.forEach(function (panel) {
                    panel.classList.toggle('active', Number(panel.dataset.step) === step);
                });
                labels.forEach(function (label) {
                    const n = Number(label.dataset.stepLabel);
                    label.classList.remove('active', 'done');
                    if (n === step) label.classList.add('active');
                    if (n < step) label.classList.add('done');
                });
                backBtn.style.display = step > 1 ? '' : 'none';
                nextBtn.style.display = step < totalSteps ? '' : 'none';
                submitBtn.style.display = step === totalSteps ? '' : 'none';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function validateStep(step) {
                if (step !== 1) return true;
                const name = document.getElementById('name');
                const description = document.getElementById('description');
                let ok = true;
                if (!name.value.trim()) { name.reportValidity(); ok = false; }
                else if (!description.value.trim()) { description.reportValidity(); ok = false; }
                return ok;
            }

            backBtn.addEventListener('click', function () {
                if (currentStep > 1) showStep(currentStep - 1);
            });

            nextBtn.addEventListener('click', function () {
                if (!validateStep(currentStep)) return;
                if (currentStep < totalSteps) showStep(currentStep + 1);
            });

            labels.forEach(function (label) {
                label.addEventListener('click', function () {
                    const target = Number(label.dataset.stepLabel);
                    if (target < currentStep || validateStep(1)) {
                        if (target > currentStep && !validateStep(currentStep)) return;
                        showStep(target);
                    }
                });
                label.style.cursor = 'pointer';
            });

            function setupFilePreview(input) {
                const targetName = input.dataset.previewTarget;
                const container = targetName
                    ? document.querySelector('[data-preview="' + targetName + '"]')
                    : input.closest('.photo-slot, .gallery-row, .offering-row')?.querySelector('.photo-preview');
                if (!container) return;

                const img = container.querySelector('img');
                const placeholder = container.querySelector('.placeholder');

                input.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            img.src = e.target.result;
                            img.style.display = 'block';
                            if (placeholder) placeholder.style.display = 'none';
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        img.style.display = 'none';
                        img.src = '';
                        if (placeholder) placeholder.style.display = 'block';
                    }
                });
            }

            document.querySelectorAll('input[type="file"]').forEach(setupFilePreview);

            // Gallery repeater
            const galleryList = document.getElementById('gallery-uploads');
            const addGalleryBtn = document.getElementById('add-gallery');

            function renumberGallery() {
                const rows = galleryList.querySelectorAll('.gallery-row');
                addGalleryBtn.style.display = rows.length >= maxPhotos ? 'none' : '';
            }

            addGalleryBtn.addEventListener('click', function () {
                const row = galleryList.querySelector('.gallery-row').cloneNode(true);
                row.querySelector('.gallery-input').value = '';
                row.querySelector('.gallery-description').value = '';
                const preview = row.querySelector('.photo-preview img');
                preview.style.display = 'none';
                preview.src = '';
                row.querySelector('.placeholder').style.display = 'block';
                galleryList.appendChild(row);
                setupFilePreview(row.querySelector('.gallery-input'));
                renumberGallery();
            });

            galleryList.addEventListener('click', function (e) {
                if (!e.target.classList.contains('remove-gallery')) return;
                const row = e.target.closest('.gallery-row');
                if (galleryList.querySelectorAll('.gallery-row').length > 1) {
                    row.remove();
                } else {
                    row.querySelector('.gallery-input').value = '';
                    row.querySelector('.gallery-description').value = '';
                    row.querySelector('.photo-preview img').style.display = 'none';
                    row.querySelector('.placeholder').style.display = 'block';
                }
                renumberGallery();
            });

            renumberGallery();

            // Offerings repeater
            const offeringList = document.getElementById('offerings');
            const addOfferingBtn = document.getElementById('add-offering');

            function renumberOfferings() {
                offeringList.querySelectorAll('.offering-row').forEach(function (row, i) {
                    row.querySelectorAll('input').forEach(function (field) {
                        field.name = field.name.replace(/offerings\[\d+\]/, 'offerings[' + i + ']');
                    });
                });
                addOfferingBtn.style.display = offeringList.children.length >= maxOfferings ? 'none' : '';
            }

            addOfferingBtn.addEventListener('click', function () {
                const row = offeringList.querySelector('.offering-row').cloneNode(true);
                row.querySelectorAll('input[type="text"]').forEach(function (input) { input.value = ''; });
                row.querySelectorAll('input[type="file"]').forEach(function (input) { input.value = ''; });
                const preview = row.querySelector('.photo-preview img');
                preview.style.display = 'none';
                preview.src = '';
                row.querySelector('.placeholder').style.display = 'block';
                offeringList.appendChild(row);
                setupFilePreview(row.querySelector('.offering-photo-input'));
                renumberOfferings();
            });

            offeringList.addEventListener('click', function (e) {
                if (!e.target.classList.contains('remove-offering')) return;
                const row = e.target.closest('.offering-row');
                if (offeringList.children.length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input[type="text"]').forEach(function (input) { input.value = ''; });
                    row.querySelectorAll('input[type="file"]').forEach(function (input) { input.value = ''; });
                    row.querySelector('.photo-preview img').style.display = 'none';
                    row.querySelector('.placeholder').style.display = 'block';
                }
                renumberOfferings();
            });

            renumberOfferings();

            // Restore wizard step after validation errors
            @if ($errors->any())
                showStep(1);
            @endif
        })();
    </script>
@endsection
