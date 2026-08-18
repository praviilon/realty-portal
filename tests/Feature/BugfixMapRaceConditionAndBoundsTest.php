<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Доработки после отчёта пользователя "карты Yandex Maps работают, но..."
 * (три отдельных бага, все воспроизведены и подтверждены вживую в браузере
 * через Playwright с подменой скрипта api-maps.yandex.ru — см. описание
 * каждого случая ниже и комментарии в самих JS-файлах):
 *
 * 1) На шаге "Адрес" мастера создания объявления карта — асинхронная
 *    (грузится сторонний скрипт по сети). Если пользователь выбирал адрес
 *    из подсказок БЫСТРЕЕ, чем карта успевала загрузиться, JS падал с
 *    ошибкой "Cannot read properties of null (reading 'addChild')" внутри
 *    placeMarker(), и это обрывало setPosition() до вызова
 *    $wire.set('lat', ...)/$wire.set('lng', ...) — поля широты/долготы
 *    оставались пустыми, приходилось вводить их вручную. Исправлено:
 *    $wire.set(...) теперь вызывается ПЕРВЫМ, до попытки отрисовать
 *    маркер; placeMarker() безопасно ничего не делает, если карта ещё не
 *    готова (renderMap() сам расставит маркер по актуальным координатам,
 *    когда всё-таки загрузится).
 * 2) На карте каталога объявления могли быть физически отрисованы, но за
 *    пределами видимой области — карта раньше ВСЕГДА центрировалась на
 *    ПЕРВОМ пине с фиксированным zoom=10, поэтому при объявлениях в разных
 *    районах/городах большинство пинов оставались невидимы без ручного
 *    зума. Исправлено: при 2+ пинах карта показывает bounds, вмещающие
 *    ВСЕ точки.
 * 3) Переключение вкладок "Список"/"Карта" пересоздавало Alpine-компонент
 *    карты, но старый window.addEventListener('catalog:pins-updated', ...)
 *    никогда не снимался — при каждом повторном открытии карты копился ещё
 *    один "мёртвый" слушатель. Исправлено: обработчик снимается в
 *    destroy() (автоматический хук Alpine, аналогичный init(), но без
 *    риска двойного вызова, т.к. в блейде нет отдельной директивы под
 *    него).
 */
class BugfixMapRaceConditionAndBoundsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.yandex_maps.api_key', 'test-key-for-assertions-only');
    }

    public function test_address_geocoder_js_syncs_lat_lng_before_touching_the_map(): void
    {
        $js = file_get_contents(resource_path('js/address-geocoder.js'));

        // setPosition должен вызывать $wire.set РАНЬШЕ this.placeMarker(...).
        $wireSetPos = strpos($js, "this.\$wire.set('lat', lat);");
        $placeMarkerCallPos = strpos($js, 'this.placeMarker(lat, lng);', $wireSetPos ?: 0);

        $this->assertNotFalse($wireSetPos, '$wire.set для lat не найден в setPosition()');
        $this->assertNotFalse($placeMarkerCallPos, 'вызов this.placeMarker(lat, lng) не найден после $wire.set');
        $this->assertLessThan($placeMarkerCallPos, $wireSetPos, '$wire.set(lat) должен вызываться до placeMarker(), а не после');
    }

    public function test_place_marker_guards_against_map_not_ready_yet(): void
    {
        $js = file_get_contents(resource_path('js/address-geocoder.js'));

        $this->assertMatchesRegularExpression(
            '/placeMarker\(lat, lng\)\s*\{[^}]*if \(!this\.map\)\s*\{\s*return;/s',
            $js,
            'placeMarker() должен безопасно завершаться, если this.map ещё не создан (карта грузится асинхронно)'
        );
    }

    public function test_yandex_map_js_fits_bounds_to_all_pins(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertStringContainsString('computeLocation()', $js);
        $this->assertStringContainsString('bounds:', $js);
    }

    public function test_yandex_map_js_cleans_up_pins_updated_listener_on_destroy(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertStringContainsString('destroy() {', $js);
        $this->assertStringContainsString("window.removeEventListener('catalog:pins-updated'", $js);
    }

    public function test_catalog_map_shows_loading_placeholder_over_a_visible_container(): void
    {
        $user = User::factory()->create();
        ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        $content = Livewire::actingAs($user)->test(\App\Livewire\Catalog\Search::class)->set('view', 'map')->html();

        $this->assertStringContainsString('Загрузка карты', $content);

        // Контейнер карты (x-ref="mapCanvas") не должен скрываться через x-show —
        // иначе YMap() инициализируется в элементе нулевого размера. Проверяем
        // именно тег этого div целиком, а не весь остаток документа (в котором
        // x-show встречается и в других, не связанных с картой местах).
        preg_match('/<div x-ref="mapCanvas"[^>]*>/', $content, $matches);
        $this->assertNotEmpty($matches, 'div с x-ref="mapCanvas" не найден');
        $this->assertStringNotContainsString('x-show', $matches[0]);
    }

    public function test_address_picker_shows_loading_placeholder_and_manual_edit_hint(): void
    {
        $user = User::factory()->create();

        $content = Livewire::actingAs($user)->test(\App\Livewire\Property\CreateWizard::class)->set('step', 2)->html();

        $this->assertStringContainsString('Загрузка карты', $content);
        $this->assertStringContainsString('можно отредактировать вручную', $content);
    }
}
