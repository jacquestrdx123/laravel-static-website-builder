<?php

namespace App\Services;

use App\Models\Website;
use Anthropic\Client;
use RuntimeException;

class PosterGenerator
{
    public function __construct(private ?Client $client = null)
    {
        $this->client ??= new Client(apiKey: config('services.anthropic.key'));
    }

    public function generate(Website $website, string $brief, string $format): string
    {
        if (blank(config('services.anthropic.key'))) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $dimensions = config('sites.poster_formats.'.$format);
        if (! is_array($dimensions)) {
            throw new RuntimeException('Unknown poster format.');
        }

        $response = $this->client->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 16384,
            outputConfig: ['format' => [
                'type' => 'json_schema',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'html' => ['type' => 'string'],
                    ],
                    'required' => ['html'],
                    'additionalProperties' => false,
                ],
            ]],
            messages: [[
                'role' => 'user',
                'content' => $this->prompt($website, $brief, $dimensions),
            ]],
        );

        $text = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded) || ! isset($decoded['html'])) {
            throw new RuntimeException('The model returned an unexpected poster format.');
        }

        return (string) $decoded['html'];
    }

    /** @param  array{label: string, width: int, height: int}  $dimensions */
    private function prompt(Website $website, string $brief, array $dimensions): string
    {
        $settings = json_encode($website->settings, JSON_PRETTY_PRINT);

        return <<<PROMPT
Create a self-contained HTML marketing poster for "{$website->name}".

Brief: {$brief}

Canvas: {$dimensions['width']}px by {$dimensions['height']}px.

Business settings (JSON):
{$settings}

Return JSON with one field "html": a complete HTML document with embedded CSS only.
- Fixed dimensions matching the canvas
- No external resources, no JavaScript
- Bold, readable typography suitable for social print
- Use brand-relevant colors from the business context when possible
PROMPT;
    }
}
