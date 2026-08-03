<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Website;
use App\Services\PublishedSiteHost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds the sites shown on the public /examples page.
 *
 *     php artisan db:seed --class=DemoSitesSeeder
 *
 * These are hand-authored rather than AI-generated, so seeding costs no
 * credits and makes no API calls. The HTML lives in stubs/demo-sites/ so the
 * showcase can be rebuilt on a fresh server - without this the /examples page
 * deploys correctly and then sits empty.
 *
 * Idempotent: re-running refreshes the files and republishes in place.
 */
class DemoSitesSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('is_admin', true)->orderBy('id')->first() ?? User::orderBy('id')->first();

        if (! $owner) {
            $this->command?->warn('No users exist - create one first, then re-run this seeder.');

            return;
        }

        $host = app(PublishedSiteHost::class);
        $stubs = __DIR__.'/stubs/demo-sites';

        foreach ($this->demos() as $demo) {
            $source = $stubs.'/'.$demo['slug'];

            if (! File::exists($source.'/index.html')) {
                $this->command?->warn("Skipping {$demo['slug']}: no stub at {$source}");

                continue;
            }

            $website = Website::updateOrCreate(
                ['slug' => $demo['slug']],
                [
                    'user_id' => $owner->id,
                    'name' => $demo['name'],
                    'settings' => $demo['settings'],
                    'status' => Website::STATUS_READY,
                    'is_demo' => true,
                    'error' => null,
                    'generated_at' => now(),
                ]
            );

            // Mirror what WebsiteGenerator would have written, then publish
            // through the normal host so /srv/websites matches a real site.
            File::deleteDirectory($website->sitePath());
            File::ensureDirectoryExists($website->sitePath());
            File::copyDirectory($source, $website->sitePath());

            $host->publish($website);
            $website->update([
                'status' => Website::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            $this->command?->info("Published demo: {$website->slug}");
        }
    }

    /** @return list<array{slug:string,name:string,settings:array<string,mixed>}> */
    private function demos(): array
    {
        return [
            [
                'slug' => 'kgosi-plumbing',
                'name' => 'Kgosi Plumbing & Geysers',
                'settings' => [
                    'description' => 'Family-run plumbing business in Pretoria East, trading since 2009. Emergency callouts, geyser replacement and leak detection for homes and small complexes.',
                    'tagline' => 'Burst pipe? We answer the phone.',
                    'contact_email' => 'hello@kgosiplumbing.co.za',
                    'site_type' => 'business',
                    'sections' => ['hero', 'about', 'services', 'testimonials', 'faq', 'contact'],
                    'style' => 'corporate',
                    'color_scheme' => 'light',
                    'accent_color' => '#1D4ED8',
                    'features' => ['sticky_header', 'smooth_scroll', 'seo_meta', 'contact_form'],
                    'offering_type' => 'services',
                    'offering_label' => 'What we do',
                    'offerings' => [
                        ['name' => '24/7 emergency callout', 'description' => 'Burst pipes and major leaks, within 90 minutes.', 'price' => 'From R850 callout', 'image_id' => null],
                        ['name' => 'Geyser replacement', 'description' => 'Supply and fit, SABS-approved, includes CoC.', 'price' => 'From R6 500', 'image_id' => null],
                        ['name' => 'Leak detection', 'description' => 'Acoustic and thermal detection.', 'price' => 'From R1 200', 'image_id' => null],
                        ['name' => 'Blocked drains', 'description' => 'High-pressure jetting and camera inspection.', 'price' => 'From R950', 'image_id' => null],
                    ],
                    'extra_instructions' => null,
                ],
            ],
            [
                'slug' => 'table-bay-coffee',
                'name' => 'Table Bay Coffee Roasters',
                'settings' => [
                    'description' => 'Small-batch specialty coffee roastery in Woodstock, Cape Town. Single origins, blends, subscriptions and wholesale for cafes.',
                    'tagline' => 'Roasted in Woodstock, on your table by Thursday.',
                    'contact_email' => 'orders@tablebaycoffee.co.za',
                    'site_type' => 'restaurant',
                    'sections' => ['hero', 'about', 'services', 'gallery', 'testimonials', 'contact'],
                    'style' => 'elegant',
                    'color_scheme' => 'dark',
                    'accent_color' => '#C2410C',
                    'features' => ['animations', 'smooth_scroll', 'sticky_header', 'seo_meta', 'contact_form'],
                    'offering_type' => 'products',
                    'offering_label' => 'Our coffee',
                    'offerings' => [
                        ['name' => 'Ethiopia Yirgacheffe', 'description' => 'Washed. Jasmine, bergamot, peach.', 'price' => 'R210 / 250g', 'image_id' => null],
                        ['name' => 'Table Mountain Blend', 'description' => 'Brazil and Colombia. Chocolate, hazelnut.', 'price' => 'R165 / 250g', 'image_id' => null],
                        ['name' => 'Rwanda Nyungwe', 'description' => 'Red berry, brown sugar.', 'price' => 'R195 / 250g', 'image_id' => null],
                        ['name' => 'Monthly subscription', 'description' => 'Two bags a month, free Cape Metro delivery.', 'price' => 'R340 / month', 'image_id' => null],
                    ],
                    'extra_instructions' => null,
                ],
            ],
            [
                'slug' => 'naledi-photography',
                'name' => 'Naledi Mokoena Photography',
                'settings' => [
                    'description' => 'Johannesburg-based documentary wedding and portrait photographer working across Gauteng and the Free State.',
                    'tagline' => 'Weddings, portraits and the quiet moments between.',
                    'contact_email' => 'naledi@naledimokoena.co.za',
                    'site_type' => 'portfolio',
                    'sections' => ['hero', 'gallery', 'about', 'pricing', 'testimonials', 'contact'],
                    'style' => 'minimal',
                    'color_scheme' => 'light',
                    'accent_color' => '#0F766E',
                    'features' => ['smooth_scroll', 'animations', 'back_to_top', 'seo_meta', 'contact_form'],
                    'offering_type' => 'services',
                    'offering_label' => 'Packages',
                    'offerings' => [
                        ['name' => 'Full wedding day', 'description' => 'Ten hours, second shooter, 600+ edited images.', 'price' => 'R28 500', 'image_id' => null],
                        ['name' => 'Intimate ceremony', 'description' => 'Four hours, 200+ edited images.', 'price' => 'R14 000', 'image_id' => null],
                        ['name' => 'Couples and engagement', 'description' => 'Ninety minutes, 60 edited images.', 'price' => 'R4 800', 'image_id' => null],
                        ['name' => 'Family portraits', 'description' => 'One hour, 40 edited images.', 'price' => 'R3 900', 'image_id' => null],
                    ],
                    'extra_instructions' => null,
                ],
            ],
        ];
    }
}
