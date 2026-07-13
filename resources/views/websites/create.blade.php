@extends('layouts.app')

@section('title', 'New website')

@section('content')
    <h1>Build a new website</h1>
    <p class="muted">
        Describe your business, upload up to {{ config('sites.max_images') }} photos, choose your options,
        and the AI builds you a complete static website. Costs {{ config('sites.generation_cost') }} credit.
    </p>

    <form method="POST" action="{{ route('websites.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <h2 style="margin-top:0">1. Tell us about it</h2>

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

        <div class="card">
            <h2 style="margin-top:0">2. Upload your photos</h2>
            <p class="hint">Modern websites rely on striking visuals — the higher quality images you provide, the more
                professional and polished your site will look. Use clear, well-lit photos that showcase your business at its best.</p>
            <p class="hint" style="margin-top: 0.5rem;">JPEG, PNG, GIF or WebP. Max 8&nbsp;MB each, up to {{ config('sites.max_images') }} photos.
                The AI can see your photos and designs around them.</p>
            @error('images')<div class="error">{{ $message }}</div>@enderror
            @error('images.*')<div class="error">{{ $message }}</div>@enderror
            @error('image_descriptions.*')<div class="error">{{ $message }}</div>@enderror

            <div id="photo-uploads" style="margin-top: 1rem;">
                <div class="photo-row" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div class="photo-preview" style="width: 80px; height: 80px; border: 2px dashed var(--line); border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; background: var(--bg-alt);">
                            <span class="preview-placeholder" style="color: var(--muted); font-size: 0.75rem; text-align: center;">No photo</span>
                            <img class="preview-image" style="display: none; width: 100%; height: 100%; object-fit: cover;" alt="Preview">
                        </div>
                        <div style="flex: 1;">
                            <label style="margin-top: 0;">Choose photo</label>
                            <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" class="photo-input">
                            <label style="margin-top: 0.5rem;">Brief description <span class="hint">(helps the AI use this photo appropriately)</span></label>
                            <input type="text" name="image_descriptions[]" maxlength="200" class="photo-description"
                                   placeholder="e.g. Our storefront, Team photo, Fresh bread display">
                        </div>
                    </div>
                    <button type="button" class="btn secondary remove-photo" style="margin-top: .6rem; padding: .25rem .8rem;">Remove</button>
                </div>
            </div>
            <button type="button" id="add-photo" class="btn secondary">+ Add another photo</button>

            <script>
                (function () {
                    const maxPhotos = {{ config('sites.max_images') }};
                    const photoList = document.getElementById('photo-uploads');
                    const addPhotoButton = document.getElementById('add-photo');

                    function renumberPhotos() {
                        const rows = photoList.querySelectorAll('.photo-row');
                        addPhotoButton.style.display = rows.length >= maxPhotos ? 'none' : '';
                    }

                    function setupPreview(row) {
                        const input = row.querySelector('.photo-input');
                        const preview = row.querySelector('.preview-image');
                        const placeholder = row.querySelector('.preview-placeholder');
                        const descInput = row.querySelector('.photo-description');

                        input.addEventListener('change', function () {
                            if (this.files && this.files[0]) {
                                const reader = new FileReader();
                                reader.onload = function (e) {
                                    preview.src = e.target.result;
                                    preview.style.display = 'block';
                                    placeholder.style.display = 'none';
                                };
                                reader.readAsDataURL(this.files[0]);
                                if (typeof updateOfferingImageSelects === 'function') {
                                    updateOfferingImageSelects();
                                }
                            } else {
                                preview.style.display = 'none';
                                placeholder.style.display = 'block';
                                if (typeof updateOfferingImageSelects === 'function') {
                                    updateOfferingImageSelects();
                                }
                            }
                        });

                        descInput.addEventListener('input', function () {
                            if (typeof updateOfferingImageSelects === 'function') {
                                updateOfferingImageSelects();
                            }
                        });
                    }

                    addPhotoButton.addEventListener('click', function () {
                        if (photoList.querySelectorAll('.photo-row').length >= maxPhotos) return;

                        const row = photoList.querySelector('.photo-row').cloneNode(true);
                        row.querySelector('.photo-input').value = '';
                        row.querySelector('.photo-description').value = '';
                        row.querySelector('.preview-image').style.display = 'none';
                        row.querySelector('.preview-image').src = '';
                        row.querySelector('.preview-placeholder').style.display = 'block';
                        photoList.appendChild(row);
                        setupPreview(row);
                        renumberPhotos();
                        updateOfferingImageSelects();
                    });

                    photoList.addEventListener('click', function (e) {
                        if (!e.target.classList.contains('remove-photo')) return;
                        const row = e.target.closest('.photo-row');
                        if (photoList.querySelectorAll('.photo-row').length > 1) {
                            row.remove();
                        } else {
                            row.querySelector('.photo-input').value = '';
                            row.querySelector('.photo-description').value = '';
                            row.querySelector('.preview-image').style.display = 'none';
                            row.querySelector('.preview-image').src = '';
                            row.querySelector('.preview-placeholder').style.display = 'block';
                        }
                        renumberPhotos();
                        updateOfferingImageSelects();
                    });

                    photoList.querySelectorAll('.photo-row').forEach(setupPreview);
                    renumberPhotos();
                })();
            </script>
        </div>

        <div class="card">
            <h2 style="margin-top:0">3. Your services or products <span class="hint">(optional)</span></h2>
            <p class="hint">List what you offer and the AI will feature each item on the site with
                your exact names and prices. Leave empty to let the AI write this from your description.</p>

            <label>These are…</label>
            <div class="choices">
                @php $offeringLabels = ['services' => 'Services', 'products' => 'Products', 'menu' => 'Menu items']; @endphp
                @foreach (\App\Http\Controllers\WebsiteController::OFFERING_TYPES as $type)
                    <label><input type="radio" name="offering_type" value="{{ $type }}"
                        @checked(old('offering_type', 'services') === $type)>{{ $offeringLabels[$type] }}</label>
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
                        <label>Photo <span class="hint">(optional — pick from photos uploaded above)</span></label>
                        <select name="offerings[{{ $i }}][image_index]" class="offering-image-select">
                            <option value="">No photo</option>
                        </select>
                        <button type="button" class="btn secondary remove-offering" style="margin-top:.6rem; padding:.25rem .8rem">Remove</button>
                    </fieldset>
                @endforeach
            </div>
            <button type="button" id="add-offering" class="btn secondary">+ Add another</button>
            @error('offerings')<div class="error">{{ $message }}</div>@enderror

            <script>
                (function () {
                    const max = {{ \App\Http\Controllers\WebsiteController::MAX_OFFERINGS }};
                    const list = document.getElementById('offerings');
                    const addButton = document.getElementById('add-offering');
                    const oldImageIndexes = @json(collect(old('offerings', []))->pluck('image_index')->values());

                    function renumber() {
                        list.querySelectorAll('.offering-row').forEach(function (row, i) {
                            row.querySelectorAll('input, select').forEach(function (field) {
                                field.name = field.name.replace(/offerings\[\d+\]/, 'offerings[' + i + ']');
                            });
                        });
                        addButton.style.display = list.children.length >= max ? 'none' : '';
                    }

                    window.updateOfferingImageSelects = function () {
                        const photoRows = document.querySelectorAll('#photo-uploads .photo-row');
                        const photos = [];

                        photoRows.forEach(function (row, index) {
                            const input = row.querySelector('.photo-input');
                            const descInput = row.querySelector('.photo-description');
                            if (input && input.files && input.files[0]) {
                                const desc = descInput ? descInput.value : '';
                                photos.push({
                                    index: index,
                                    name: input.files[0].name,
                                    description: desc
                                });
                            }
                        });

                        list.querySelectorAll('.offering-image-select').forEach(function (select, rowIndex) {
                            const current = select.value;
                            select.innerHTML = '<option value="">No photo</option>';

                            photos.forEach(function (photo) {
                                const option = document.createElement('option');
                                option.value = String(photo.index);
                                const label = photo.description
                                    ? 'Photo ' + (photo.index + 1) + ' — ' + photo.description
                                    : 'Photo ' + (photo.index + 1) + ' — ' + photo.name;
                                option.textContent = label;
                                select.appendChild(option);
                            });

                            if (current && select.querySelector('option[value="' + current + '"]')) {
                                select.value = current;
                            } else if (oldImageIndexes[rowIndex] !== undefined && oldImageIndexes[rowIndex] !== null && oldImageIndexes[rowIndex] !== '') {
                                select.value = String(oldImageIndexes[rowIndex]);
                            }
                        });
                    };

                    addButton.addEventListener('click', function () {
                        const row = list.querySelector('.offering-row').cloneNode(true);
                        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                        row.querySelectorAll('select').forEach(function (select) { select.value = ''; });
                        list.appendChild(row);
                        renumber();
                        updateOfferingImageSelects();
                    });

                    list.addEventListener('click', function (e) {
                        if (!e.target.classList.contains('remove-offering')) return;
                        const row = e.target.closest('.offering-row');
                        if (list.children.length > 1) {
                            row.remove();
                        } else {
                            row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                            row.querySelectorAll('select').forEach(function (select) { select.value = ''; });
                        }
                        renumber();
                        updateOfferingImageSelects();
                    });

                    renumber();
                    updateOfferingImageSelects();
                })();
            </script>
        </div>

        <div class="card">
            <h2 style="margin-top:0">4. Choose your options</h2>

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

        <div class="actions" style="margin-bottom: 3rem;">
            <button type="submit">Generate my website ({{ config('sites.generation_cost') }} credit)</button>
            <a class="btn secondary" href="{{ route('dashboard') }}">Cancel</a>
        </div>
    </form>
@endsection
