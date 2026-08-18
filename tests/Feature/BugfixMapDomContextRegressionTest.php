<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Регрессия сразу ПОСЛЕ доработки "гонка при выборе адреса, карта каталога
 * не показывала все объекты, утечка слушателя при переключении Список/Карта"
 * (см. BugfixMapRaceConditionAndBoundsTest): карты Yandex Maps перестали
 * загружаться вообще, "Загрузка карты…" висела вечно на всех страницах, в
 * консоли — реальная внутренняя ошибка Yandex Maps API 3.0:
 *
 *   Не удалось загрузить Yandex Maps JS API 3.0: Error: DomContext:
 *   attaching to entity with destroyed DomContext
 *
 * Воспроизведено и подтверждено вживую через Playwright (изолированный тест
 * фабричной функции yandexMap(...) с мок-объектом, повторяющим ключевое
 * поведение реального API — сущность-обёртка карты хранит ссылку на свой
 * DOM-контейнер и бросает именно эту ошибку при попытке addChild()/
 * setLocation(), если контейнер уже не в document, что и происходит внутри
 * ymaps3): см. /tmp/repro-domcontext.js и /tmp/repro-degenerate-bounds.js.
 *
 * Два независимых механизма, оба воспроизведены на СТАРОМ (до этого фикса)
 * коде и оба исправлены:
 *
 * 1) Livewire-компонент каталога (Search::render()) диспатчит window-событие
 *    catalog:pins-updated на КАЖДЫЙ свой рендер, в том числе на тот, что
 *    скрывает карту при переключении вкладки "Карта" -> "Список". Сам morph
 *    (удаление DOM-контейнера карты) происходит СИНХРОННО, а наш собственный
 *    Alpine-хук destroy() (снимающий window-слушатель) вызывается АСИНХРОННО,
 *    через MutationObserver — между этими двумя моментами есть окно, в
 *    котором window-событие может прилететь на уже "осиротевший" this.map
 *    (JS-объект ещё жив, а его DOM-контейнер уже удалён). Вызов
 *    map.setLocation() в этот момент на реальном API падает с "DomContext:
 *    attaching to entity with destroyed DomContext". Исправлено: обработчик
 *    теперь дополнительно проверяет this.mapReady и document.contains(el)
 *    перед тем, как трогать карту, а сам вызов обёрнут в try/catch.
 * 2) Карта каталога передавала вычисленные bounds (границы, вмещающие ВСЕ
 *    объявления) прямо в конструктор `new YMap(el, {location: {bounds}})`.
 *    Если два и более объявления имеют абсолютно одинаковые координаты
 *    (например, разные объявления в одном доме), bounds получается нулевой
 *    площади — на реальном API это тоже приводило к падению уже во время
 *    самого СОЗДАНИЯ карты. Исправлено: карта теперь всегда создаётся с
 *    безопасным center/zoom, а вписывание всех объявлений в область
 *    видимости — отдельный, необязательный шаг ПОСЛЕ создания карты, в
 *    try/catch, плюс вырожденный (нулевой площади) bounds теперь заменяется
 *    на center/zoom ещё до передачи в API.
 *
 * Оба сценария относятся к классу ошибок, которые НЕ ловятся упрощёнными
 * PHPUnit/мок-тестами (см. предупреждение в BugfixMapRaceConditionAndBoundsTest)
 * — эти проверки текстовые/структурные, полноценное подтверждение — через
 * Playwright с более точным мок-API (см. /tmp/repro-*.js) и через реальное
 * тестирование пользователем на продакшене.
 */
class BugfixMapDomContextRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.yandex_maps.api_key', 'test-key-for-assertions-only');
    }

    public function test_pins_updated_handler_checks_map_ready_and_container_is_still_attached(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertMatchesRegularExpression(
            '/_pinsUpdatedHandler = \(event\) => \{.*?if \(!this\.map \|\| !this\.mapReady\)\s*\{\s*return;/s',
            $js,
            'Обработчик catalog:pins-updated должен проверять и this.map, и this.mapReady'
        );

        $this->assertStringContainsString('document.contains(this._el)', $js);
    }

    public function test_pins_updated_handler_wraps_map_calls_in_try_catch(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertMatchesRegularExpression(
            '/_pinsUpdatedHandler = \(event\) => \{.*?try \{\s*this\.map\.setLocation\(this\.computeLocation\(\)\);\s*this\.renderMarkers\(\);\s*\} catch \(error\)/s',
            $js
        );
    }

    public function test_yandex_map_js_destroys_underlying_ymap_instance_on_teardown(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertMatchesRegularExpression(
            '/destroy\(\)\s*\{.*?if \(this\.map\)\s*\{\s*try\s*\{\s*this\.map\.destroy\(\);/s',
            $js,
            'destroy() должен вызывать штатный this.map.destroy() из Yandex Maps API, а не просто отбрасывать ссылку'
        );
    }

    public function test_address_geocoder_js_also_destroys_underlying_ymap_instance_on_teardown(): void
    {
        $js = file_get_contents(resource_path('js/address-geocoder.js'));

        $this->assertStringContainsString('destroy() {', $js);
        $this->assertMatchesRegularExpression(
            '/destroy\(\)\s*\{.*?if \(this\.map\)\s*\{\s*try\s*\{\s*this\.map\.destroy\(\);/s',
            $js
        );
    }

    public function test_compute_location_falls_back_to_center_for_degenerate_bounds(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertMatchesRegularExpression(
            '/if \(minLat === maxLat && minLng === maxLng\)\s*\{\s*return \{center:/s',
            $js,
            'computeLocation() должен подменять нулевую (вырожденную) область границ на center/zoom'
        );
    }

    public function test_map_is_constructed_with_safe_location_not_raw_bounds(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        $this->assertStringContainsString('safeInitialLocation()', $js);
        $this->assertStringContainsString('new YMap(el, {location: this.safeInitialLocation()})', $js);

        // computeLocation() (который может вернуть bounds) применяется
        // ОТДЕЛЬНЫМ вызовом setLocation() уже после создания карты, а не в
        // конструкторе.
        $this->assertMatchesRegularExpression(
            '/this\.mapReady = true;.*?if \(this\.pins\.length >= 2\)\s*\{\s*try\s*\{\s*this\.map\.setLocation\(this\.computeLocation\(\)\);/s',
            $js
        );
    }

    public function test_render_map_marks_ready_before_optional_enhancements_and_guards_them(): void
    {
        $js = file_get_contents(resource_path('js/yandex-map.js'));

        // mapReady = true должен стоять ДО renderMarkers()/bounds-fit/area
        // listener, чтобы падение любого из необязательных шагов не
        // оставляло пользователя с вечной надписью "Загрузка карты…".
        $mapReadyPos = strpos($js, 'this.mapReady = true;');
        $renderMarkersCallPos = strpos($js, 'this.renderMarkers();', $mapReadyPos ?: 0);

        $this->assertNotFalse($mapReadyPos);
        $this->assertNotFalse($renderMarkersCallPos);
        $this->assertGreaterThan($mapReadyPos, $renderMarkersCallPos);

        $this->assertMatchesRegularExpression(
            '/try \{\s*this\.renderMarkers\(\);\s*\} catch \(error\)/s',
            $js
        );
    }
}
