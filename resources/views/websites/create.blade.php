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
            <textarea id="description" name="description" required maxlength="2000"
                placeholder="e.g. We are a family-run bakery in Stellenbosch specialising in sourdough and wedding cakes. Open Tue-Sun...">{{ old('description') }}</textarea>
            @error('description')<div class="error">{{ $message }}</div>@enderror

            <label for="contact_email">Contact email shown on the site <span class="hint">(optional)</span></label>
            <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}">
        </div>

        <div class="card">
            <h2 style="margin-top:0">2. Upload your photos</h2>
            <p class="hint">JPEG, PNG, GIF or WebP. Max 8&nbsp;MB each, up to {{ config('sites.max_images') }} photos.
                The AI can see your photos and designs around them.</p>
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
            @error('images')<div class="error">{{ $message }}</div>@enderror
            @error('images.*')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="card">
            <h2 style="margin-top:0">3. Choose your options</h2>

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
            <textarea id="extra_instructions" name="extra_instructions" maxlength="2000"
                placeholder="e.g. Mention our Tuesday special. Keep the tone playful.">{{ old('extra_instructions') }}</textarea>
        </div>

        <div class="actions" style="margin-bottom: 3rem;">
            <button type="submit">Generate my website ({{ config('sites.generation_cost') }} credit)</button>
            <a class="btn secondary" href="{{ route('dashboard') }}">Cancel</a>
        </div>
    </form>
@endsection
