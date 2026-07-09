<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\RawContentBlockDeltaEvent;
use Anthropic\Messages\RawMessageDeltaEvent;
use Anthropic\Messages\RawMessageStartEvent;
use Anthropic\Messages\TextDelta;
use App\Models\Website;
use App\Models\WebsiteImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
        // The system spec is a static file and is byte-identical on every
        // request, so it forms a cacheable prefix: the first generation pays
        // a one-off cache write, subsequent ones within the TTL read it at
        // ~10% of the normal input price. Everything volatile (the brief,
        // the photos) comes after the breakpoint, in the user turn.
        $stream = $this->client->messages->createStream(
            model: config('services.anthropic.model'),
            maxTokens: config('services.anthropic.max_tokens'),
            thinking: ['type' => 'adaptive'],
            system: [[
                'type' => 'text',
                'text' => $this->systemPrompt(),
                'cacheControl' => ['type' => 'ephemeral', 'ttl' => config('services.anthropic.cache_ttl')],
            ]],
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
            } elseif ($event instanceof RawMessageStartEvent) {
                Log::info('Website generation started', [
                    'website_id' => $website->id,
                    'input_tokens' => $event->message->usage->inputTokens,
                    'cache_write_tokens' => $event->message->usage->cacheCreationInputTokens,
                    'cache_read_tokens' => $event->message->usage->cacheReadInputTokens,
                ]);
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

            File::put($target, $this->relativizeUrls($relative, $file['content']));
        }
    }

    /**
     * Rewrite root-absolute references (/styles.css, /assets/x.jpg) to
     * document-relative ones. Previews are served from a subdirectory
     * (/storage/sites/{slug}/), so a leading slash would resolve against the
     * app's domain root and 404. Relative paths work both there and on the
     * published domain root.
     */
    public function relativizeUrls(string $relativePath, string $content): string
    {
        // How deep this document sits inside the site, e.g. pages/about.html
        // needs "../" to reach a sibling of index.html.
        $prefix = str_repeat('../', substr_count($relativePath, '/'));

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['html', 'htm'], true)) {
            // src="/x", href="/x", poster="/x" — but not protocol-relative "//cdn".
            $content = preg_replace(
                '/\b(src|href|poster)=(["\'])\/(?!\/)/i',
                '$1=$2'.$prefix,
                $content
            );
            // srcset lists: each candidate URL may start with "/".
            $content = preg_replace_callback(
                '/\bsrcset=(["\'])(.*?)\1/is',
                fn ($m) => 'srcset='.$m[1].preg_replace('/(^|,\s*)\/(?!\/)/', '$1'.$prefix, $m[2]).$m[1],
                $content
            );
        }

        if (in_array($extension, ['html', 'htm', 'css'], true)) {
            // CSS url(/assets/x.jpg) in stylesheets and inline <style> blocks.
            $content = preg_replace(
                '/\burl\((["\']?)\/(?!\/)/i',
                'url($1'.$prefix,
                $content
            );
        }

        return $content;
    }

    /**
     * The permanent generation spec. Loaded verbatim from a static file so
     * the cached prompt prefix is byte-identical across requests - never
     * interpolate anything volatile (dates, ids, per-user data) into it.
     */
    private function systemPrompt(): string
    {
        return File::get(resource_path('prompts/website-generator-system.md'));
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
            'offering_type' => $settings['offering_type'] ?? 'services',
            'offerings' => $settings['offerings'] ?? [],
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
