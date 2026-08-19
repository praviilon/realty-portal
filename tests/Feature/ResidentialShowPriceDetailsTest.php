<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Доработка по просьбе пользователя: шаг 4 мастера создания объявления
 * ("Цена") для жилой недвижимости вынесен по аналогии с коммерческой —
 * для аренды цена в месяц/депозит/комиссия/тип аренды/"коммунальные
 * платежи включены", для продажи — цена/комиссия. Эти поля теперь также
 * отображаются на странице объекта — см.
 * resources/views/livewire/property/show.blade.php и
 * App\Livewire\CommercialProperty\Show (тот же паттерн для коммерческой
 * недвижимости).
 */
class ResidentialShowPriceDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_rent_specific_price_fields(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'rent',
            'price' => 90000,
            'deposit' => 90000,
            'commission' => 40000,
            'rent_type' => 'sublease',
            'utilities_included' => true,
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('90 000')
            ->assertSee('/мес.', false)
            ->assertSee('Тип аренды')
            ->assertSee('Субаренда')
            ->assertSee('Депозит')
            ->assertSee('Комиссия')
            ->assertSee('40 000')
            ->assertSee('Коммунальные платежи')
            ->assertSee('Включены');
    }

    public function test_show_page_displays_utilities_not_included_for_rent(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'rent',
            'price' => 50000,
            'deposit' => null,
            'commission' => null,
            'rent_type' => 'direct',
            'utilities_included' => false,
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('Прямая аренда')
            ->assertSee('Не включены')
            ->assertDontSee('Депозит')
            ->assertDontSee('Комиссия');
    }

    public function test_show_page_displays_sale_commission_when_set(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'price' => 6500000,
            'commission' => 50000,
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertDontSee('/мес.', false)
            ->assertSee('Комиссия')
            ->assertSee('50 000')
            ->assertDontSee('Тип аренды')
            ->assertDontSee('Депозит')
            ->assertDontSee('Коммунальные платежи');
    }

    public function test_show_page_hides_commission_block_for_sale_when_not_set(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'price' => 6500000,
            'commission' => null,
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertDontSee('Комиссия')
            ->assertDontSee('Тип аренды');
    }
}
