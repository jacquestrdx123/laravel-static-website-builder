<?php

namespace Tests\Feature;

use App\Jobs\GenerateWebsiteJob;
use App\Livewire\Websites\Create;
use App\Livewire\Websites\Index;
use App\Livewire\Websites\Show;
use App\Models\User;
use App\Models\Website;
use App\WebsiteBuilder\WebsiteOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireCustomerPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_a_livewire_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    }

    public function test_create_wizard_lists_every_site_type_template(): void
    {
        $user = User::factory()->create(['ai_credits' => 2]);

        $response = $this->actingAs($user)
            ->get(route('websites.create'))
            ->assertOk()
            ->assertSeeLivewire(Create::class);

        foreach (WebsiteOptions::siteTypeTemplates() as $template) {
            $response->assertSee($template['label'], false);
            $response->assertSee($template['summary'], false);
        }
    }

    public function test_selecting_a_site_type_applies_template_defaults(): void
    {
        $user = User::factory()->create(['ai_credits' => 2]);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('site_type', 'restaurant')
            ->assertSet('offering_type', 'products')
            ->assertSet('sections', WebsiteOptions::siteTypeTemplates()['restaurant']['default_sections']);
    }

    public function test_livewire_wizard_creates_a_website_and_queues_generation(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create(['ai_credits' => 2]);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('site_type', 'portfolio')
            ->set('name', 'Studio North')
            ->set('description', 'A photography studio in Cape Town.')
            ->set('sections', ['hero', 'gallery', 'contact'])
            ->set('style', 'minimal')
            ->set('color_scheme', 'light')
            ->set('offering_type', 'services')
            ->set('banner', UploadedFile::fake()->image('banner.jpg', 1200, 400))
            ->call('save')
            ->assertRedirect();

        $website = Website::first();
        $this->assertNotNull($website);
        $this->assertSame('portfolio', $website->settings['site_type']);
        $this->assertSame(1, $user->fresh()->ai_credits);
        Queue::assertPushed(GenerateWebsiteJob::class);
    }

    public function test_show_page_is_livewire_and_owner_only(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $website = $owner->websites()->create([
            'name' => 'Site',
            'slug' => 'site',
            'status' => Website::STATUS_READY,
            'settings' => ['site_type' => 'business'],
        ]);

        $this->actingAs($owner)
            ->get(route('websites.show', $website))
            ->assertOk()
            ->assertSeeLivewire(Show::class);

        $this->actingAs($other)
            ->get(route('websites.show', $website))
            ->assertForbidden();
    }
}
