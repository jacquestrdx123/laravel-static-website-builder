<?php

namespace App\Services;

use App\Models\Website;
use Anthropic\Client;
use RuntimeException;

class NewsletterGenerator
{
    public function __construct(private ?Client $client = null)
    {
        $this->client ??= new Client(apiKey: config('services.anthropic.key'));
    }

    /**
     * @return array{subject: string, html: string, text: string}
     */
    public function generate(Website $website, string $topic, ?string $angle = null): array
    {
        if (blank(config('services.anthropic.key'))) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $response = $this->client->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 8192,
            outputConfig: ['format' => [
                'type' => 'json_schema',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'subject' => ['type' => 'string'],
                        'html' => ['type' => 'string'],
                        'text' => ['type' => 'string'],
                    ],
                    'required' => ['subject', 'html', 'text'],
                    'additionalProperties' => false,
                ],
            ]],
            messages: [[
                'role' => 'user',
                'content' => $this->prompt($website, $topic, $angle),
            ]],
        );

        $text = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded) || ! isset($decoded['subject'], $decoded['html'], $decoded['text'])) {
            throw new RuntimeException('The model returned an unexpected newsletter format.');
        }

        return [
            'subject' => (string) $decoded['subject'],
            'html' => (string) $decoded['html'],
            'text' => (string) $decoded['text'],
        ];
    }

    private function prompt(Website $website, string $topic, ?string $angle): string
    {
        $settings = json_encode($website->settings, JSON_PRETTY_PRINT);
        $angleText = $angle ?? 'general update';

        return <<<PROMPT
Write a marketing email newsletter for the business "{$website->name}".

Topic: {$topic}
Angle: {$angleText}

Business settings and offerings (JSON):
{$settings}

Return JSON with:
- subject: compelling email subject line
- html: email body as simple, inline-styled HTML (no external CSS, no scripts)
- text: plain-text version

Match the tone of the business. Include a clear call to action.
PROMPT;
    }
}
