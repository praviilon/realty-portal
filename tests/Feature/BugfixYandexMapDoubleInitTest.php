<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Доработка после Вехи 3, п.4 отчёта пользователя: карта Yandex Maps не
 * работала нигде на сайте, даже после того как в .env был прописан реальный
 * YANDEX_MAPS_API_KEY.
 *
 * Причина (подтверждена вживую в браузере через Playwright с подменой
 * скрипта api-maps.yandex.ru): оба Alpine-компонента (`yandexMap` в
 * resources/js/yandex-map.js и `addressGeocoder` в
 * resources/js/address-geocoder.js) называли свой метод инициализации
 * `init` — а это зарезервированное имя, которое Alpine вызывает
 * АВТОМАТИЧЕСКИ и БЕЗ АРГУМЕНТОВ сразу после обработки x-data, ещё до
 * того как сработает явная директива x-init="init(...)". В итоге init()
 * вызывался дважды: один раз автоматически (с el === undefined, что
 * ломало создание карты), второй раз — уже правильно, через x-init. Помимо
 * гонки это означало двойную вставку тяжёлого стороннего <script> Yandex
 * Maps в document.head, что на реальном скрипте API стабильно ломает его
 * инициализацию целиком (в тесте с подставным скриптом это воспроизводилось
 * как "Identifier ... has already been declared").
 *
 * Метод переименован в `initMap`, чтобы не пересекаться со служебным именем
 * Alpine. Этот тест — текстовая защита от регресса: проверяет, что блейд-
 * компоненты карты зовут именно initMap(...), а не init(...), и что сами
 * JS-файлы больше не объявляют метод с именем `init`.
 */
class BugfixYandexMapDoubleInitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.yandex_maps.api_key', 'test-key-for-assertions-only');
    }

    public function test_yandex_map_component_calls_init_map_not_init(): void
    {
        $user = User::factory()->create();
        ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        // Карта рендерится только на вкладке "Карта" ($view === 'map') —
        // по умолчанию каталог открывается в режиме списка.
        $content = Livewire::actingAs($user)->test(\App\Livewire\Catalog\Search::class)->set('view', 'map')->html();

        $this->assertStringContainsString('x-init="initMap($refs.mapCanvas)"', $content);
        $this->assertStringNotContainsString('x-init="init(', $content);
    }

    public function test_address_picker_component_calls_init_map_not_init(): void
    {
        $user = User::factory()->create();

        // Подбор адреса с картой показывается только на шаге 2 мастера.
        $content = Livewire::actingAs($user)->test(\App\Livewire\Property\CreateWizard::class)->set('step', 2)->html();

        $this->assertStringContainsString('x-init="initMap()"', $content);
        $this->assertStringNotContainsString('x-init="init()"', $content);
    }

    public function test_yandex_map_js_defines_init_map_and_not_a_bare_init_method(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertStringContainsString('initMap(el) {', $js);
        $this->assertDoesNotMatchRegularExpression('/(?<![a-zA-Z])init\(el\)\s*\{/', $js);
    }

    public function test_address_geocoder_js_defines_init_map_and_not_a_bare_init_method(): void
    {
        $js = file_get_contents(resource_path('js/address-geocoder.js'));

        $this->assertStringContainsString('initMap() {', $js);
        $this->assertDoesNotMatchRegularExpression('/(?<![a-zA-Z])init\(\)\s*\{/', $js);
    }
}
