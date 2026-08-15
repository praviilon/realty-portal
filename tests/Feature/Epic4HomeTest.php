<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\ResidentialProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic4HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_home_shows_featured_active_listings(): void
    {
        ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Витринная, 5']);
        ResidentialProperty::factory()->moderation()->create(['address' => 'ул. Скрытая, 9']);

        $response = $this->get('/');

        $response->assertSee('ул. Витринная, 5');
        $response->assertDontSee('ул. Скрытая, 9');
    }

    public function test_home_shows_faq_accordion(): void
    {
        Faq::factory()->create(['category' => 'Общие вопросы', 'question' => 'Тестовый вопрос №1?']);

        $this->get('/')->assertSee('Тестовый вопрос №1?');
    }

    public function test_search_form_redirects_to_catalog_with_filters(): void
    {
        Livewire::test(\App\Livewire\Home\Index::class)
            ->set('searchDealType', 'rent')
            ->set('searchPropertyType', 'house')
            ->call('search')
            ->assertRedirect(route('residential.search', ['dealType' => 'rent', 'propertyType' => 'house']));
    }
}
