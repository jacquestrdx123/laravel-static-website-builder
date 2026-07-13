@extends('layouts.app')

@section('title', 'Edit content — '.$website->name)

@section('content')
    <style>
        .photo-preview {
            width: 80px; height: 80px; border-radius: 8px; border: 1px solid var(--line);
            background: #fff; overflow: hidden; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .photo-preview.banner { width: 100%; max-width: 320px; height: 120px; }
        .photo-preview .placeholder { color: var(--ink-soft); font-size: .75rem; text-align: center; padding: .4rem; }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .photo-card {
            border: 1px solid var(--line); border-radius: 8px; padding: .8rem;
            background: #fff; display: flex; flex-direction: column; gap: .6rem;
        }
        .photo-card img { width: 100%; height: 140px; object-fit: cover; border-radius: 6px; border: 1px solid var(--line); }
        .photo-type { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--ink-soft); }
        .offering-photo { display: flex; gap: .8rem; align-items: flex-start; margin-top: .5rem; }
        .offering-photo .caption { font-size: .85rem; color: var(--ink-soft); margin-top: .25rem; }
    </style>

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

        @if ($images->isNotEmpty())
            <div class="card">
                <h2 style="margin-top:0">Your photos</h2>
                <p class="hint" style="margin-top:0">
                    These are the images on your site. Notes you added at creation are shown below each photo.
                </p>

                <div class="photo-grid">
                    @foreach ($images as $image)
                        <div class="photo-card">
                            @if ($image->existsOnDisk())
                                <img src="{{ $image->previewUrl() }}" alt="{{ $image->original_name }}">
                            @else
                                <div class="photo-preview"><span class="placeholder">File missing</span></div>
                            @endif
                            <div>
                                <div class="photo-type">{{ $image->typeLabel() }}</div>
                                <div style="font-size:.9rem">{{ $image->original_name }}</div>
                                @if (filled($image->description))
                                    <p style="margin:.4rem 0 0; font-size:.9rem">{{ $image->description }}</p>
                                @else
                                    <p class="hint" style="margin:.4rem 0 0">No note for this photo.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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
            <p class="hint" style="margin-top:0">
                Descriptions below reflect what is currently on your site, including any text the AI wrote during generation.
            </p>

            <label>These are…</label>
            <div class="choices">
                @foreach (\App\Http\Controllers\WebsiteController::OFFERING_TYPES as $type)
                    <label><input type="radio" name="offering_type" value="{{ $type }}"
                        @checked(old('offering_type', $website->settings['offering_type'] ?? 'services') === $type)>{{ ucfirst($type) }}</label>
                @endforeach
            </div>

            <label for="offering_label">What should this section be called? <span class="hint">(optional)</span></label>
            <input id="offering_label" type="text" name="offering_label" maxlength="50"
                   placeholder="e.g. Our Treatments, Packages, Offerings"
                   value="{{ old('offering_label', $website->settings['offering_label'] ?? '') }}">
            @error('offering_label')<div class="error">{{ $message }}</div>@enderror

            <div id="offerings" style="margin-top: 1rem;">
                @foreach ($offerings as $i => $offering)
                    @php
                        $linkedImage = filled($offering['image_id'] ?? null)
                            ? ($imagesById[$offering['image_id']] ?? null)
                            : null;
                    @endphp
                    <fieldset class="offering-row" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                        @if ($linkedImage)
                            <div class="offering-photo">
                                @if ($linkedImage->existsOnDisk())
                                    <div class="photo-preview">
                                        <img src="{{ $linkedImage->previewUrl() }}" alt="{{ $linkedImage->original_name }}">
                                    </div>
                                @endif
                                <div>
                                    <div class="photo-type">{{ $linkedImage->typeLabel() }} photo</div>
                                    <div style="font-size:.9rem">{{ $linkedImage->original_name }}</div>
                                    @if (filled($linkedImage->description))
                                        <div class="caption">{{ $linkedImage->description }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

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
                        <label>Description <span class="hint">(as shown on your site)</span></label>
                        <textarea name="offerings[{{ $i }}][description]" maxlength="500" rows="3"
                                  style="min-height:4rem">{{ $offering['description'] ?? '' }}</textarea>
                        <input type="hidden" name="offerings[{{ $i }}][image_id]" value="{{ $offering['image_id'] ?? '' }}">
                        <label>Replace photo <span class="hint">(optional)</span></label>
                        <input type="file" name="offerings[{{ $i }}][image]" accept="image/jpeg,image/png,image/gif,image/webp" class="offering-photo-input">
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
                            row.querySelectorAll('input, textarea').forEach(function (field) {
                                field.name = field.name.replace(/offerings\[\d+\]/, 'offerings[' + i + ']');
                            });
                        });
                        addButton.style.display = list.children.length >= max ? 'none' : '';
                    }

                    addButton.addEventListener('click', function () {
                        const row = list.querySelector('.offering-row').cloneNode(true);
                        row.querySelectorAll('input, textarea').forEach(function (input) {
                            if (input.type === 'file') {
                                input.value = '';
                            } else if (input.type === 'hidden') {
                                input.value = '';
                            } else {
                                input.value = '';
                            }
                        });
                        row.querySelectorAll('.offering-photo').forEach(function (block) {
                            block.remove();
                        });
                        list.appendChild(row);
                        renumber();
                    });

                    list.addEventListener('click', function (e) {
                        if (!e.target.classList.contains('remove-offering')) return;
                        const row = e.target.closest('.offering-row');
                        if (list.children.length > 1) {
                            row.remove();
                        } else {
                            row.querySelectorAll('input, textarea').forEach(function (input) {
                                if (input.type === 'file') {
                                    input.value = '';
                                } else if (input.type === 'hidden') {
                                    input.value = '';
                                } else {
                                    input.value = '';
                                }
                            });
                            row.querySelectorAll('.offering-photo').forEach(function (block) {
                                block.remove();
                            });
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
