<?php

namespace Tests\Unit;

use App\WebsiteBuilder\HeroPresets;
use Tests\TestCase;

class HeroPresetsTest extends TestCase
{
    public function test_catalog_covers_every_export_and_every_style(): void
    {
        $presets = HeroPresets::all();
        $ids = array_column($presets, 'id');

        $this->assertCount(38, $presets);
        $this->assertCount(38, array_unique($ids));

        $required = ['id', 'name', 'what', 'structure', 'dna', 'motion', 'palette', 'type_pairing', 'fits_styles', 'fits_site_types', 'fits_schemes'];

        foreach ($presets as $preset) {
            foreach ($required as $key) {
                $this->assertArrayHasKey($key, $preset, $preset['id'].' missing '.$key);
            }
            $this->assertNotEmpty($preset['dna']);
            $this->assertNotEmpty($preset['motion']);
            $this->assertFileExists(HeroPresets::screenshotPath($preset['id']));
        }

        $styles = [];
        foreach ($presets as $preset) {
            foreach ($preset['fits_styles'] as $style) {
                $styles[$style] = true;
            }
        }

        foreach (['minimal', 'bold', 'elegant', 'playful', 'corporate'] as $style) {
            $this->assertArrayHasKey($style, $styles, 'No presets tagged '.$style);
        }
    }

    public function test_pick_is_deterministic_and_style_compatible(): void
    {
        $settings = [
            'style' => 'elegant',
            'site_type' => 'restaurant',
            'color_scheme' => 'dark',
        ];

        $first = HeroPresets::pick($settings, 42);
        $second = HeroPresets::pick($settings, 42);
        $other = HeroPresets::pick($settings, 43);

        $this->assertSame($first['id'], $second['id']);
        $this->assertArrayHasKey('do_not', $first);
        $this->assertNotEmpty($first['structure']);

        $this->assertContains('elegant', $this->stylesFor($first['id']));
        $this->assertContains('restaurant', $this->typesFor($first['id']));
        $this->assertNotSame($first['id'], $other['id']);
    }

    public function test_locked_preset_id_wins(): void
    {
        $picked = HeroPresets::pick([
            'style' => 'playful',
            'site_type' => 'event',
            'hero_preset' => 'hero-08',
        ], 1);

        $this->assertSame('hero-08', $picked['id']);
        $this->assertSame('DarkShield', $picked['name']);
    }

    public function test_screenshot_path_rejects_unsafe_ids(): void
    {
        $this->assertNull(HeroPresets::screenshotPath('../secrets'));
        $this->assertNull(HeroPresets::screenshotPath('hero-99'));
    }

    /** @return list<string> */
    private function stylesFor(string $id): array
    {
        foreach (HeroPresets::all() as $preset) {
            if ($preset['id'] === $id) {
                return $preset['fits_styles'];
            }
        }

        return [];
    }

    /** @return list<string> */
    private function typesFor(string $id): array
    {
        foreach (HeroPresets::all() as $preset) {
            if ($preset['id'] === $id) {
                return $preset['fits_site_types'];
            }
        }

        return [];
    }
}
