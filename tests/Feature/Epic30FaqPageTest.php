<?php

namespace Tests\Feature;

use App\Livewire\Faq\Index as FaqPage;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic30FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_page_loads(): void
    {
        $this->get('/faq')->assertOk();
    }

    public function test_faq_page_shows_all_questions_by_default(): void
    {
        Faq::factory()->create(['category' => 'Общие вопросы', 'question' => 'Общий вопрос №1?']);
        Faq::factory()->create(['category' => 'Оплата', 'question' => 'Вопрос про оплату №1?']);

        $this->get('/faq')
            ->assertSee('Общий вопрос №1?')
            ->assertSee('Вопрос про оплату №1?');
    }

    public function test_filtering_by_category_shows_only_matching_questions(): void
    {
        Faq::factory()->create(['category' => 'Общие вопросы', 'question' => 'Общий вопрос №2?']);
        Faq::factory()->create(['category' => 'Оплата', 'question' => 'Вопрос про оплату №2?']);

        Livewire::test(FaqPage::class)
            ->call('setCategory', 'Оплата')
            ->assertSee('Вопрос про оплату №2?')
            ->assertDontSee('Общий вопрос №2?');
    }

    public function test_category_buttons_are_shown(): void
    {
        Faq::factory()->create(['category' => 'Размещение объявлений']);

        $this->get('/faq')->assertSee('Размещение объявлений');
    }

    public function test_footer_links_to_faq_page(): void
    {
        $this->get(route('home'))->assertSee(route('faq.index'), false);
    }
}
