<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\RawContentBlockDeltaEvent;
use Anthropic\Messages\RawMessageDeltaEvent;
use Anthropic\Messages\TextDelta;
use App\Models\Website;
use App\Models\WebsiteImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WebsiteGenerator
{
    public function __construct(private ?Client $client = null)
    {
        $this->client ??= new Client(apiKey: config('services.anthropic.key'));
    }

    /**
     * Generate the static site for the given website and write it to disk.
     *
     * @throws RuntimeException when generation fails for any reason
     */
    public function generate(Website $website): void
    {
        if (blank(config('services.anthropic.key'))) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $sitePath = $website->sitePath();
        File::deleteDirectory($sitePath);
        File::ensureDirectoryExists($sitePath.'/assets');

        $assetNames = $this->copyImagesToAssets($website, $sitePath);

        $response = $this->requestSite($website, $assetNames);

        $this->writeFiles($sitePath, $response['files']);

        if (! File::exists($sitePath.'/index.html')) {
            throw new RuntimeException('The model did not produce an index.html file.');
        }
    }

    /** Copy the customer's uploaded images into the site's assets directory. */
    private function copyImagesToAssets(Website $website, string $sitePath): array
    {
        $names = [];

        foreach ($website->images as $image) {
            $source = Storage::disk('local')->path($image->path);
            $name = $image->assetName();
            File::copy($source, $sitePath.'/assets/'.$name);
            $names[] = 'assets/'.$name;
        }

        return $names;
    }

    /** @return array{files: list<array{path: string, content: string}>} */
    private function requestSite(Website $website, array $assetNames): array
    {
        $stream = $this->client->messages->createStream(
            model: config('services.anthropic.model'),
            maxTokens: config('services.anthropic.max_tokens'),
            thinking: ['type' => 'adaptive'],
            system: $this->systemPrompt(),
            outputConfig: ['format' => $this->outputSchema()],
            messages: [[
                'role' => 'user',
                'content' => $this->userContent($website, $assetNames),
            ]],
        );

        $buffer = '';
        $stopReason = null;

        foreach ($stream as $event) {
            if ($event instanceof RawContentBlockDeltaEvent && $event->delta instanceof TextDelta) {
                $buffer .= $event->delta->text;
            } elseif ($event instanceof RawMessageDeltaEvent) {
                $stopReason = $event->delta->stopReason;
            }
        }

        if ($stopReason === 'refusal') {
            throw new RuntimeException('The AI declined to generate this website. Please adjust your content and try again.');
        }

        if ($stopReason === 'max_tokens') {
            throw new RuntimeException('The generated website was too large. Try reducing the number of sections.');
        }

        $decoded = json_decode($buffer, true);

        if (! is_array($decoded) || ! isset($decoded['files']) || ! is_array($decoded['files'])) {
            throw new RuntimeException('The model returned an unexpected response format.');
        }

        return $decoded;
    }

    /** Write model-produced files inside the site directory, rejecting unsafe paths. */
    private function writeFiles(string $sitePath, array $files): void
    {
        $root = realpath($sitePath);

        foreach ($files as $file) {
            if (! is_array($file) || ! is_string($file['path'] ?? null) || ! is_string($file['content'] ?? null)) {
                continue;
            }

            $relative = ltrim($file['path'], '/');

            if (! preg_match('#^[A-Za-z0-9._/-]+$#', $relative) || str_contains($relative, '..')) {
                continue;
            }

            $target = $sitePath.'/'.$relative;
            File::ensureDirectoryExists(dirname($target));

            // Belt-and-braces: the resolved parent must stay inside the site dir.
            if (! str_starts_with(realpath(dirname($target)) ?: '', $root)) {
                continue;
            }

            File::put($target, $file['content']);
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert web designer and front-end developer. You build complete, production-quality
static websites using only HTML, CSS, and vanilla JavaScript - no frameworks, no build steps,
no CDN dependencies, no external fonts or scripts. Everything must work offline from plain files.

Requirements for every site you produce:
- Semantic, accessible HTML (landmarks, alt text, sufficient color contrast, keyboard navigable).
- Fully responsive: flawless on mobile, tablet, and desktop. Use CSS grid/flexbox and relative units.
- A cohesive, distinctive visual identity. Never produce generic "AI slop" design: no overused
  system-font stacks presented as design, no cliched purple-gradient-on-white schemes, no
  cookie-cutter layouts. Use characterful type pairings (system-available fonts are fine when
  chosen deliberately), a cohesive palette, and considered spacing.
- All CSS in styles.css, all JavaScript in script.js, referenced with relative paths.
- Reference the customer's photos with the exact relative asset paths you are given. Design
  around the actual content of the photos, which you can see.
- Real, well-written copy based on the business details provided - no lorem ipsum.
- SEO basics when requested: title, meta description, Open Graph tags.
- Contact forms must degrade gracefully as static sites: use a mailto: fallback or a clearly
  marked form action placeholder comment.

Return the complete site as a JSON object with a "files" array. Each entry has "path" (relative,
e.g. "index.html", "styles.css", "script.js") and "content" (the full file contents). Do not
include the provided image assets in the files array - they are already in place.
PROMPT;
    }

    private function outputSchema(): array
    {
        return [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'files' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'path' => ['type' => 'string'],
                                'content' => ['type' => 'string'],
                            ],
                            'required' => ['path', 'content'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['files'],
                'additionalProperties' => false,
            ],
        ];
    }

    /** Build the user turn: the brief, the toggles, and the customer's photos as vision blocks. */
    private function userContent(Website $website, array $assetNames): array
    {
        $settings = $website->settings;

        $brief = [
            'business_name' => $website->name,
            'site_type' => $settings['site_type'] ?? 'business',
            'description' => $settings['description'] ?? '',
            'tagline' => $settings['tagline'] ?? null,
            'contact_email' => $settings['contact_email'] ?? null,
            'sections' => $settings['sections'] ?? ['hero', 'about', 'contact'],
            'style' => $settings['style'] ?? 'minimal',
            'color_scheme' => $settings['color_scheme'] ?? 'light',
            'accent_color' => $settings['accent_color'] ?? null,
            'features' => $settings['features'] ?? [],
            'extra_instructions' => $settings['extra_instructions'] ?? null,
            'image_assets' => $assetNames,
        ];

        $content = [[
            'type' => 'text',
            'text' => "Build a static website from this brief:\n\n"
                .json_encode($brief, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ."\n\nThe customer's photos follow, in the same order as image_assets. "
                .'Use them where they fit best (hero, gallery, about, etc.).',
        ]];

        foreach ($website->images as $index => $image) {
            $content[] = [
                'type' => 'text',
                'text' => 'Photo '.($index + 1).' — available at '.$assetNames[$index]
                    .' (original filename: '.$image->original_name.')',
            ];
            $content[] = $this->imageBlock($image);
        }

        return $content;
    }

    private function imageBlock(WebsiteImage $image): array
    {
        $data = base64_encode(Storage::disk('local')->get($image->path));

        return [
            'type' => 'image',
            'source' => [
                'type' => 'base64',
                'mediaType' => $image->mime_type,
                'data' => $data,
            ],
        ];
    }
}
