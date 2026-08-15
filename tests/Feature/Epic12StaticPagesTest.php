<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Epic12StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_loads(): void
    {
        $this->get(route('about'))
            ->assertStatus(200)
            ->assertSee('О компании');
    }

    public function test_help_page_loads(): void
    {
        $this->get(route('help'))
            ->assertStatus(200)
            ->assertSee('Как разместить объявление');
    }

    public function test_terms_page_loads(): void
    {
        $this->get(route('legal.terms'))
            ->assertStatus(200)
            ->assertSee('Пользовательское соглашение');
    }

    public function test_privacy_page_loads(): void
    {
        $this->get(route('legal.privacy'))
            ->assertStatus(200)
            ->assertSee('Политика конфиденциальности');
    }

    public function test_footer_links_to_static_pages(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee(route('about'), false);
        $response->assertSee(route('help'), false);
        $response->assertSee(route('legal.terms'), false);
        $response->assertSee(route('legal.privacy'), false);
    }

    public function test_home_page_has_faq_anchor(): void
    {
        \App\Models\Faq::factory()->create();

        $this->get(route('home'))->assertSee('id="faq"', false);
    }
}
