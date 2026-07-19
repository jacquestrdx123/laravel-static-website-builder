<?php

namespace Tests\Unit;

use App\Http\Controllers\WebsiteController;
use App\Services\WebsiteGenerator;
use Tests\TestCase;

class BlueprintTest extends TestCase
{
    private function generator(): WebsiteGenerator
    {
        return new WebsiteGenerator(new \Anthropic\Client(apiKey: 'test'));
    }

    public function test_every_site_type_has_a_blueprint(): void
    {
        foreach (WebsiteController::SITE_TYPES as $type) {
            $blueprint = $this->generator()->blueprintFor($type);

            $this->assertNotNull($blueprint, "Missing blueprint for site type: $type");
            $this->assertStringContainsString('site_type_blueprint type="'.$type.'"', $blueprint);
            // Every blueprint must teach the editable-content annotations.
            $this->assertStringContainsString('data-offering', $blueprint);
            $this->assertStringContainsString('data-content="contact-email"', $blueprint);
            $this->assertStringContainsString('data-content="tagline"', $blueprint);
        }
    }

    public function test_unknown_or_unsafe_site_types_return_null(): void
    {
        $this->assertNull($this->generator()->blueprintFor('spaceship'));
        $this->assertNull($this->generator()->blueprintFor('../secrets'));
        $this->assertNull($this->generator()->blueprintFor(''));
    }
}
