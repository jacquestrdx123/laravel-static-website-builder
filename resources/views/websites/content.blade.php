@extends('layouts.app')

@section('title', 'Edit content — '.$website->name)

@section('content')
    <h1>Edit content</h1>
    <p class="muted">
        Update your {{ $website->settings['offering_label'] ?? $website->settings['offering_type'] ?? 'services' }}, prices, tagline, and contact
        email directly on <strong>{{ $website->name }}</strong> — free and instant, no AI credits used.
        The design stays exactly as it is.
    </p>

    @unless ($editable)
        <div class="flash err">
            This site was generated before content editing existed. Changes will be saved, but you'll
            need to <strong>regenerate once</strong> (from the website page) before they appear on the site.
        </div>
    @endunless

    <form method="POST" action="{{ route('websites.content.update', $website) }}" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <label for="tagline">Tagline</label>
            <input id="tagline" type="text" name="tagline" maxlength="200"
                   value="{{ old('tagline', $website->settings['tagline'] ?? '') }}">
            @error('tagline')<div class="error">{{ $message }}</div>@enderror

            <label for="contact_email">Contact email</label>
            <input id="contact_email" type="email" name="contact_email"
                   value="{{ old('contact_email', $website->settings['contact_email'] ?? '') }}">
            @error('contact_email')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="card">
            <h2 style="margin-top:0">Your services or products</h2>

            <label>These are…</label>
            <div class="choices">
                @php $offeringLabels = ['services' => 'Services', 'products' => 'Products', 'menu' => 'Menu items']; @endphp
                @foreach (\App\Http\Controllers\WebsiteController::OFFERING_TYPES as $type)
                    <label><input type="radio" name="offering_type" value="{{ $type }}"
                        @checked(old('offering_type', $website->settings['offering_type'] ?? 'services') === $type)>{{ $offeringLabels[$type] }}</label>
                @endforeach
            </div>

            <label for="offering_label">What should this section be called? <span class="hint">(optional)</span></label>
            <input id="offering_label" type="text" name="offering_label" maxlength="50"
                   placeholder="e.g. Our Treatments, Packages, Offerings"
                   value="{{ old('offering_label', $website->settings['offering_label'] ?? '') }}">
            @error('offering_label')<div class="error">{{ $message }}</div>@enderror

            @php
                $websiteImages = $website->images()->orderBy('sort')->get();
            @endphp
                $current = old('offerings', $website->settings['offerings'] ?? []);
                if (empty($current)) {
                    $current = [['name' => '', 'description' => '', 'price' => '']];
                }
            @endphp

            <div id="offerings" style="margin-top: 1rem;">
                @foreach ($current as $i => $offering)
                    <fieldset class="offering-row" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                        <div class="grid-2">
                            <div>
                                <label style="margin-top:0">Name</label>
                                <input type="text" name="offerings[{{ $i }}][name]" maxlength="100"
                                       value="{{ $offering['name'] ?? '' }}">
                            </div>
                            <div>
                                <label style="margin-top:0">Price <span class="hint">(optional)</span></label>
                                <input type="text" name="offerings[{{ $i }}][price]" maxlength="50"
                                       value="{{ $offering['price'] ?? '' }}">
                            </div>
                        </div>
                        <label>Short description <span class="hint">(optional)</span></label>
                        <input type="text" name="offerings[{{ $i }}][description]" maxlength="500"
                               value="{{ $offering['description'] ?? '' }}">
                        <label>Photo <span class="hint">(optional)</span></label>
                        <select name="offerings[{{ $i }}][image_id]">
                            <option value="">No photo</option>
                            @foreach ($websiteImages as $image)
                                <option value="{{ $image->id }}"
                                    @selected((string) old('offerings.'.$i.'.image_id', $offering['image_id'] ?? '') === (string) $image->id)>
                                    {{ $image->original_name }}
                                </option>
                            @endforeach
                        </select>
                        <label>Or upload a new photo <span class="hint">(optional)</span></label>
                        <input type="file" name="offerings[{{ $i }}][image]" accept="image/jpeg,image/png,image/gif,image/webp">
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

                    function renumber() {
                        list.querySelectorAll('.offering-row').forEach(function (row, i) {
                            row.querySelectorAll('input, select').forEach(function (field) {
                                if (field.type === 'file') {
                                    field.name = field.name.replace(/offerings\[\d+\]/, 'offerings[' + i + ']');
                                } else {
                                    field.name = field.name.replace(/offerings\[\d+\]/, 'offerings[' + i + ']');
                                }
                            });
                        });
                        addButton.style.display = list.children.length >= max ? 'none' : '';
                    }

                    addButton.addEventListener('click', function () {
                        const row = list.querySelector('.offering-row').cloneNode(true);
                        row.querySelectorAll('input').forEach(function (input) {
                            if (input.type === 'file') {
                                input.value = '';
                            } else {
                                input.value = '';
                            }
                        });
                        row.querySelectorAll('select').forEach(function (select) { select.value = ''; });
                        list.appendChild(row);
                        renumber();
                    });

                    list.addEventListener('click', function (e) {
                        if (!e.target.classList.contains('remove-offering')) return;
                        const row = e.target.closest('.offering-row');
                        if (list.children.length > 1) {
                            row.remove();
                        } else {
                            row.querySelectorAll('input').forEach(function (input) {
                                if (input.type !== 'file') {
                                    input.value = '';
                                } else {
                                    input.value = '';
                                }
                            });
                            row.querySelectorAll('select').forEach(function (select) { select.value = ''; });
                        }
                        renumber();
                    });

                    renumber();
                })();
            </script>
        </div>

        <div class="actions" style="margin-bottom: 3rem;">
            <button type="submit">Save &amp; update site (free)</button>
            <a class="btn secondary" href="{{ route('websites.show', $website) }}">Cancel</a>
        </div>
    </form>
@endsection
