<div>
    <style>
        .photo-preview {
            width: 80px; height: 80px; border-radius: 8px; border: 1px solid var(--line);
            background: #fff; overflow: hidden; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .photo-preview .placeholder { color: var(--ink-soft); font-size: .75rem; text-align: center; padding: .4rem; }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .photo-card {
            border: 1px solid var(--line); border-radius: 8px; padding: .8rem;
            background: #fff; display: flex; flex-direction: column; gap: .6rem;
        }
        .photo-card img { width: 100%; height: 140px; object-fit: cover; border-radius: 6px; border: 1px solid var(--line); }
        .photo-type { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--ink-soft); }
        .offering-row-layout {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 1rem;
            align-items: start;
        }
        @media (max-width: 640px) {
            .offering-row-layout { grid-template-columns: 1fr; }
        }
        .offering-photo-col .photo-preview { width: 120px; height: 120px; }
        .offering-photo-col .photo-preview.placeholder-box {
            color: var(--ink-soft);
            font-size: .8rem;
            text-align: center;
            padding: .5rem;
        }
    </style>

    <h1>Edit content</h1>
    <p class="muted">
        Update your {{ $website->settings['offering_label'] ?? $website->settings['offering_type'] ?? 'services' }}, prices, tagline, and contact
        email directly on <strong>{{ $website->name }}</strong>. The design stays exactly as it is.
    </p>

    @unless ($editable)
        <div class="flash err">
            This site was generated before content editing existed. Changes will be saved, but you'll
            need to <strong>regenerate once</strong> (from the website page) before they appear on the site.
        </div>
    @endunless

    <form wire:submit="save">
        @if ($images->isNotEmpty())
            <div class="card">
                <h2 style="margin-top:0">Your photos</h2>
                <p class="hint" style="margin-top:0">
                    These are the images on your site. Notes you added at creation are shown below each photo.
                </p>

                <div class="photo-grid">
                    @foreach ($images as $image)
                        <div class="photo-card" wire:key="image-{{ $image->id }}">
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
            <input id="tagline" type="text" wire:model="tagline" maxlength="200">
            @error('tagline')<div class="error">{{ $message }}</div>@enderror

            <label for="contact_email">Contact email</label>
            <input id="contact_email" type="email" wire:model="contact_email">
            @error('contact_email')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="card">
            <h2 style="margin-top:0">Your services or products</h2>
            <p class="hint" style="margin-top:0">
                Descriptions below reflect what is currently on your site, including any text the AI wrote during generation.
            </p>

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
            @error('offering_label')<div class="error">{{ $message }}</div>@enderror

            <div style="margin-top: 1rem;">
                @foreach ($offerings as $i => $offering)
                    @php
                        $linkedImage = filled($offering['image_id'] ?? null)
                            ? ($imagesById[$offering['image_id']] ?? null)
                            : null;
                    @endphp
                    <fieldset wire:key="edit-offering-{{ $i }}" class="offering-row" style="border: 1px solid var(--line); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem;">
                        <div class="offering-row-layout">
                            <div class="offering-photo-col">
                                @if ($offering['image'] ?? null)
                                    <div class="photo-preview">
                                        <img src="{{ $offering['image']->temporaryUrl() }}" alt="New product photo">
                                    </div>
                                    <div class="photo-type" style="margin-top:.4rem">New photo</div>
                                @elseif ($linkedImage && $linkedImage->displayUrl())
                                    <div class="photo-preview">
                                        <img src="{{ $linkedImage->displayUrl() }}" alt="{{ $linkedImage->original_name }}">
                                    </div>
                                    <div class="photo-type" style="margin-top:.4rem">{{ $linkedImage->typeLabel() }} photo</div>
                                    <div style="font-size:.85rem">{{ $linkedImage->original_name }}</div>
                                @else
                                    <div class="photo-preview placeholder-box">
                                        <span>No product photo yet</span>
                                    </div>
                                @endif
                            </div>

                            <div class="offering-fields-col">
                                <div class="grid-2">
                                    <div>
                                        <label style="margin-top:0">Name</label>
                                        <input type="text" wire:model="offerings.{{ $i }}.name" maxlength="100">
                                    </div>
                                    <div>
                                        <label style="margin-top:0">Price <span class="hint">(optional)</span></label>
                                        <input type="text" wire:model="offerings.{{ $i }}.price" maxlength="50">
                                    </div>
                                </div>
                                <label>Description <span class="hint">(as shown on your site)</span></label>
                                <textarea wire:model="offerings.{{ $i }}.description" maxlength="500" rows="3"
                                          style="min-height:4rem"></textarea>
                                <label>{{ ($offering['image'] ?? null) || $linkedImage ? 'Replace photo' : 'Add photo' }} <span class="hint">(optional)</span></label>
                                <input type="file" wire:model="offerings.{{ $i }}.image" accept="image/jpeg,image/png,image/gif,image/webp">
                                @error('offerings.'.$i.'.image')<div class="error">{{ $message }}</div>@enderror
                                <button type="button" class="btn secondary" style="margin-top:.6rem; padding:.25rem .8rem"
                                        wire:click="removeOffering({{ $i }})">Remove</button>
                            </div>
                        </div>
                    </fieldset>
                @endforeach
            </div>
            @if (count($offerings) < $maxOfferings)
                <button type="button" class="btn secondary" wire:click="addOffering">+ Add another</button>
            @endif
            @error('offerings')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="actions" style="margin-bottom: 3rem;">
            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save &amp; update site (free)</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
            <a class="btn secondary" href="{{ route('websites.show', $website) }}">Cancel</a>
        </div>
    </form>
</div>
