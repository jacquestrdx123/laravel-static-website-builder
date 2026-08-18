<?php

namespace App\WebsiteBuilder;

use Illuminate\Support\Facades\File;

/**
 * Daily Hero composition library. Each preset is a 6-block cinematic DNA card
 * (what, structure, style, motion, type, palette). Selection is deterministic
 * from the brief + design_seed so regenerate still changes the look.
 */
final class HeroPresets
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        static $cache = null;

        if ($cache === null) {
            $path = resource_path('prompts/hero-presets.json');
            $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
            $cache = is_array($decoded) ? array_values($decoded) : [];
        }

        return $cache;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function pick(array $settings, int $seed): array
    {
        $presets = self::all();

        if ($presets === []) {
            throw new \RuntimeException('Hero preset catalog is empty.');
        }

        $locked = $settings['hero_preset'] ?? null;

        if (is_string($locked) && $locked !== '') {
            foreach ($presets as $preset) {
                if (($preset['id'] ?? null) === $locked) {
                    return self::toBrief($preset);
                }
            }
        }

        $style = is_string($settings['style'] ?? null) ? $settings['style'] : 'minimal';
        $siteType = is_string($settings['site_type'] ?? null) ? $settings['site_type'] : 'business';
        $scheme = is_string($settings['color_scheme'] ?? null) ? $settings['color_scheme'] : 'auto';

        $scored = [];

        foreach ($presets as $preset) {
            $styles = $preset['fits_styles'] ?? [];
            if (! in_array($style, $styles, true)) {
                continue;
            }

            $score = 0;
            $types = $preset['fits_site_types'] ?? [];
            if (in_array($siteType, $types, true)) {
                $score += 2;
            }

            $schemes = $preset['fits_schemes'] ?? [];
            if ($scheme !== 'auto' && in_array($scheme, $schemes, true)) {
                $score += 1;
            }

            $scored[] = [$score, $preset];
        }

        if ($scored === []) {
            $pool = $presets;
        } else {
            $scores = array_column($scored, 0);
            $max = max($scores);
            $pool = array_values(array_map(
                fn (array $row) => $row[1],
                array_filter($scored, fn (array $row) => $row[0] === $max)
            ));

            // Keep regenerate interesting: if the best bucket is a single
            // card, include the next-best style-compatible directors.
            if (count($pool) < 2) {
                $next = $max - 1;
                foreach ($scored as [$score, $preset]) {
                    if ($score === $next) {
                        $pool[] = $preset;
                    }
                }
            }
        }

        $preset = $pool[$seed % count($pool)];

        return self::toBrief($preset);
    }

    /**
     * @param  array<string, mixed>  $preset
     * @return array<string, mixed>
     */
    public static function toBrief(array $preset): array
    {
        return [
            'id' => $preset['id'],
            'name' => $preset['name'],
            'what' => $preset['what'],
            'structure' => $preset['structure'],
            'dna' => $preset['dna'],
            'motion' => $preset['motion'],
            'palette' => $preset['palette'],
            'type_pairing' => $preset['type_pairing'],
            'do_not' => [
                "Do not copy this preset's brand name, headline, or dummy copy.",
                'Do not recreate a fixed Figma stage with absolute pixel layout.',
                "Do not load Google Fonts, CDNs, or this preset's image/video assets.",
                "Do not paste the preset's HTML. Translate the composition into responsive CSS.",
            ],
        ];
    }

    public static function screenshotPath(string $id): ?string
    {
        if (! preg_match('/^[a-z0-9-]+$/', $id)) {
            return null;
        }

        $candidates = [
            resource_path('prompts/hero-stills/'.$id.'.jpg'),
            public_path('figma-exports/'.$id.'/assets/preview.jpg'),
            public_path('figma-exports/'.$id.'/assets/export.png'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
