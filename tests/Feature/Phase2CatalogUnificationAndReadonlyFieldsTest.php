<?php

namespace Tests\Feature;

use App\Livewire\Catalog\CommercialSearch;
use App\Livewire\Catalog\WorkspaceSearch;
use App\Models\CommercialProperty;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Доработки по отчёту пользователя после проверки карт (см. описание в
 * BugfixMapRaceConditionAndBoundsTest):
 *
 * 1) Поля «Адрес» (верхнее), «Широта» и «Долгота» на всех трёх мастерах
 *    создания объявления стали нередактируемыми вручную (readonly) —
 *    заполняются только через геокодер/карту (нижнее поле-подсказку или
 *    клик по карте), чтобы пользователь не мог застрять на шаге, введя
 *    адрес без реальных координат.
 * 2) Каталоги коммерческой недвижимости и рабочих пространств унифицированы
 *    с каталогом жилой недвижимости: переключатель "Список"/"Карта" вместо
 *    старой фиксированной боковой карты (рабочие пространства), и функция
 *    "выделение области на карте" (ранее была только в жилом каталоге).
 * 3) Вёрстка главной страницы: вкладки типов недвижимости и белая область
 *    фильтров поиска теперь имеют одинаковую ширину (единая grid-обёртка),
 *    верхний правый угол области фильтров больше не скруглён.
 */
class Phase2CatalogUnificationAndReadonlyFieldsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Небольшой квадрат вокруг центра Москвы (широта/долгота), используем
     * как "выделенную область" в тестах ниже — см. Epic19MapAreaSelectionTest.
     */
    private function squareAroundMoscow(): array
    {
        return [
            ['lat' => 55.70, 'lng' => 37.55],
            ['lat' => 55.70, 'lng' => 37.70],
            ['lat' => 55.85, 'lng' => 37.70],
            ['lat' => 55.85, 'lng' => 37.55],
        ];
    }

    // --- 1) Нередактируемые поля адреса/широты/долготы во всех 3 мастерах ---

    public function test_residential_wizard_step2_marks_address_lat_lng_readonly(): void
    {
        $user = User::factory()->create();

        $content = Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('step', 2)
            ->html();

        preg_match('/<input[^>]*id="address"[^>]*>/', $content, $addressTag);
        preg_match('/<input[^>]*id="lat"[^>]*>/', $content, $latTag);
        preg_match('/<input[^>]*id="lng"[^>]*>/', $content, $lngTag);

        $this->assertNotEmpty($addressTag, 'поле address не найдено');
        $this->assertNotEmpty($latTag, 'поле lat не найдено');
        $this->assertNotEmpty($lngTag, 'поле lng не найдено');
        $this->assertStringContainsString('readonly', $addressTag[0]);
        $this->assertStringContainsString('readonly', $latTag[0]);
        $this->assertStringContainsString('readonly', $lngTag[0]);
    }

    public function test_commercial_wizard_step2_marks_address_lat_lng_readonly(): void
    {
        $user = User::factory()->create();

        $content = Livewire::actingAs($user)
            ->test(\App\Livewire\CommercialProperty\CreateWizard::class)
            ->set('step', 2)
            ->html();

        preg_match('/<input[^>]*id="address"[^>]*>/', $content, $addressTag);
        preg_match('/<input[^>]*id="lat"[^>]*>/', $content, $latTag);
        preg_match('/<input[^>]*id="lng"[^>]*>/', $content, $lngTag);

        $this->assertNotEmpty($addressTag, 'поле address не найдено');
        $this->assertNotEmpty($latTag, 'поле lat не найдено');
        $this->assertNotEmpty($lngTag, 'поле lng не найдено');
        $this->assertStringContainsString('readonly', $addressTag[0]);
        $this->assertStringContainsString('readonly', $latTag[0]);
        $this->assertStringContainsString('readonly', $lngTag[0]);
    }

    public function test_workspace_wizard_step2_marks_address_lat_lng_readonly(): void
    {
        $user = User::factory()->create();

        $content = Livewire::actingAs($user)
            ->test(\App\Livewire\Workspace\CreateWizard::class)
            ->set('step', 2)
            ->html();

        preg_match('/<input[^>]*id="address"[^>]*>/', $content, $addressTag);
        preg_match('/<input[^>]*id="lat"[^>]*>/', $content, $latTag);
        preg_match('/<input[^>]*id="lng"[^>]*>/', $content, $lngTag);

        $this->assertNotEmpty($addressTag, 'поле address не найдено');
        $this->assertNotEmpty($latTag, 'поле lat не найдено');
        $this->assertNotEmpty($lngTag, 'поле lng не найдено');
        $this->assertStringContainsString('readonly', $addressTag[0]);
        $this->assertStringContainsString('readonly', $latTag[0]);
        $this->assertStringContainsString('readonly', $lngTag[0]);
    }

    public function test_address_picker_hint_no_longer_claims_fields_are_manually_editable(): void
    {
        $user = User::factory()->create();
        Config::set('services.yandex_maps.api_key', 'test-fake-key');

        $content = Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('step', 2)
            ->html();

        $this->assertStringNotContainsString('можно отредактировать вручную', $content);
    }

    public function test_wizard_can_still_be_completed_via_wire_set_bypassing_readonly_html(): void
    {
        // readonly в HTML блокирует ТОЛЬКО ручной ввод с клавиатуры в браузере;
        // геокодер/карта заполняют поля через $wire.set(), что эквивалентно
        // Livewire::set() в тесте — весь сценарий создания объявления должен
        // по-прежнему проходить (полное покрытие — Epic7CreateWizardTest).
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->assertSet('step', 3);
    }

    // --- 2) Унификация каталогов: выделение области + переключатель Список/Карта ---

    public function test_commercial_catalog_area_selection_filters_listings(): void
    {
        $inside = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'address' => 'ул. Внутри области, 1',
            'lat' => 55.75,
            'lng' => 37.62,
        ]);

        $outside = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'address' => 'ул. Вне области, 2',
            'lat' => 59.93,
            'lng' => 30.34,
        ]);

        \App\Models\CommercialSaleDetail::factory()->create(['property_id' => $inside->id]);
        \App\Models\CommercialSaleDetail::factory()->create(['property_id' => $outside->id]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'sale')
            ->call('applyAreaSelection', $this->squareAroundMoscow())
            ->assertSee('ул. Внутри области, 1')
            ->assertDontSee('ул. Вне области, 2')
            ->call('clearAreaSelection')
            ->assertSee('ул. Вне области, 2');
    }

    public function test_commercial_catalog_map_view_is_selectable_with_area_selection_ui(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');

        Livewire::test(CommercialSearch::class)
            ->set('view', 'map')
            ->assertSee('Выделить область на карте', false);
    }

    public function test_workspace_catalog_area_selection_filters_listings(): void
    {
        $inside = Workspace::factory()->create([
            'status' => 'active',
            'address' => 'ул. Внутри области, 1',
            'lat' => 55.75,
            'lng' => 37.62,
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $inside->id, 'period' => 'day', 'price' => 3000]);

        $outside = Workspace::factory()->create([
            'status' => 'active',
            'address' => 'ул. Вне области, 2',
            'lat' => 59.93,
            'lng' => 30.34,
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $outside->id, 'period' => 'day', 'price' => 3000]);

        Livewire::test(WorkspaceSearch::class)
            ->call('applyAreaSelection', $this->squareAroundMoscow())
            ->assertSee('ул. Внутри области, 1')
            ->assertDontSee('ул. Вне области, 2')
            ->call('clearAreaSelection')
            ->assertSee('ул. Вне области, 2');
    }

    public function test_workspace_catalog_defaults_to_list_view_with_toggle(): void
    {
        Livewire::test(WorkspaceSearch::class)
            ->assertSet('view', 'list')
            ->assertDontSee('sticky top-6', false);
    }

    public function test_workspace_catalog_map_view_is_selectable_with_area_selection_ui(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');

        Livewire::test(WorkspaceSearch::class)
            ->set('view', 'map')
            ->assertSee('Выделить область на карте', false);
    }

    // --- 3) Вёрстка главной страницы: единая ширина вкладок и формы поиска ---

    public function test_home_page_wraps_tabs_and_search_form_in_shared_width_grid(): void
    {
        $content = $this->get('/')->getContent();

        // Вкладки и форма поиска теперь — вложенные элементы одной общей
        // grid-обёртки (см. комментарий в resources/views/livewire/home/index.blade.php),
        // а не независимые соседние блоки с разной шириной.
        $wrapperPos = strpos($content, 'inline-grid text-sm');
        $formPos = strpos($content, 'wire:submit="search"');

        $this->assertNotFalse($wrapperPos, 'grid-обёртка вкладок/формы не найдена');
        $this->assertNotFalse($formPos, 'форма поиска не найдена');
        $this->assertLessThan($formPos, $wrapperPos, 'форма поиска должна быть внутри grid-обёртки вкладок');
    }

    public function test_home_page_search_form_top_right_corner_is_square(): void
    {
        $content = $this->get('/')->getContent();

        preg_match('/<form[^>]*wire:submit="search"[^>]*>/', $content, $formTag);

        $this->assertNotEmpty($formTag, 'форма поиска не найдена');
        $this->assertStringNotContainsString('rounded-tr-xl', $formTag[0], 'верхний правый угол формы поиска не должен быть скруглён');
        $this->assertStringContainsString('rounded-b-xl', $formTag[0]);
    }

    public function test_home_page_html_structure_is_balanced(): void
    {
        // Регрессия на "забытый" закрывающий </div> после оборачивания
        // вкладок и формы в новую grid-обёртку — простая проверка баланса
        // тегов div/form на всей странице.
        $content = $this->get('/')->getContent();

        $openDivs = preg_match_all('/<div\b[^>]*>/', $content);
        $closeDivs = substr_count($content, '</div>');
        $openForms = preg_match_all('/<form\b[^>]*>/', $content);
        $closeForms = substr_count($content, '</form>');

        $this->assertSame($openDivs, $closeDivs, 'количество открывающих и закрывающих </div> не совпадает');
        $this->assertSame($openForms, $closeForms, 'количество открывающих и закрывающих </form> не совпадает');
    }
}
