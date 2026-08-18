/**
 * Карта объектов — Yandex Maps JS API 3.0 (см. раздел 1 и 7.5 технического плана).
 *
 * Регистрирует Alpine-компонент `yandexMap(pins, apiKey, selectable)`, используемый
 * в resources/views/components/yandex-map.blade.php. Подгружает скрипт API 3.0
 * динамически (ванильный JS, без npm-пакета), расставляет пины по lat/lng из
 * результатов поиска и обновляет их при изменении фильтров каталога
 * (событие `catalog:pins-updated`, которое диспатчит Livewire-компонент).
 *
 * Эпик 19 (Веха 2, "Карта — выделение области"): если selectable=true, включает
 * простое выделение прямоугольной области по двум противоположным углам
 * (клик — клик), которая затем передаётся в Livewire-компонент
 * (`App\Livewire\Catalog\Search::applyAreaSelection`) и фильтрует список через
 * MySQL ST_Contains/SPATIAL INDEX на бэкенде — сам расчёт "точка внутри
 * многоугольника" здесь не делается, только сбор координат и визуальная отрисовка.
 *
 * ИСПРАВЛЕНО (доработка после Вехи 3, п.4 отчёта пользователя — карта не
 * работала нигде на сайте даже с реальным YANDEX_MAPS_API_KEY в .env):
 * метод инициализации раньше назывался `init(el)`, что совпадает со
 * специальным именем, которое Alpine вызывает АВТОМАТИЧЕСКИ и БЕЗ АРГУМЕНТОВ
 * для любого объекта x-data, где есть метод `init` (см. документацию Alpine:
 * "If your Alpine.data() component contains an init() method, Alpine will
 * automatically call it"). Из-за этого `init(el)` вызывался дважды: один раз
 * автоматически Alpine'ом сразу после x-data (с el === undefined — что ломало
 * new YMap(undefined, ...) и приводило к ошибке, которую видно только в
 * консоли браузера), и второй раз — уже с правильным el — через явную
 * директиву x-init="init($refs.mapCanvas)". Помимо гонки/лишней ошибки это
 * означало двойную загрузку тяжёлого стороннего скрипта Yandex Maps API
 * (второй <script>-тег добавлялся в document.head, пока первый ещё грузился).
 * Метод переименован в `initMap(el)`, чтобы не пересекаться со служебным
 * именем Alpine и вызываться ровно один раз — так, как явно указано в
 * x-init в блейд-компоненте.
 */
function yandexMap(initialPins, apiKey, selectable) {
    return {
        map: null,
        mapReady: false,
        markers: [],
        pins: initialPins || [],
        selectable: !!selectable,

        selectingArea: false,
        hasAreaSelection: false,
        areaClickPoints: [],
        areaFeature: null,
        areaListener: null,
        _pinsUpdatedHandler: null,

        initMap(el) {
            this.loadScript(apiKey)
                .then(() => window.ymaps3.ready)
                .then(() => this.renderMap(el))
                .catch((error) => {
                    console.error('Не удалось загрузить Yandex Maps JS API 3.0:', error);
                });

            // ИСПРАВЛЕНО (доработка "объекты на карте не отображаются"):
            // переключение вкладок "Список"/"Карта" уничтожает и заново
            // создаёт этот Alpine-компонент (блок под x-if/@if($view==='map')),
            // но старый window.addEventListener никогда не снимался — при
            // каждом повторном открытии вкладки "Карта" накапливался ещё один
            // слушатель, привязанный к уже уничтожённому this (this.map там
            // навсегда null). Храним ссылку на обработчик и снимаем её в
            // destroy() (Alpine вызывает этот метод автоматически при
            // удалении элемента — как и init(), но, в отличие от init(),
            // здесь нет дублирующего вызова через директиву, поэтому это
            // безопасно использовать).
            this._pinsUpdatedHandler = (event) => {
                this.pins = event.detail?.pins ?? this.pins;

                if (this.map) {
                    this.map.setLocation(this.computeLocation());
                    this.renderMarkers();
                }
            };
            window.addEventListener('catalog:pins-updated', this._pinsUpdatedHandler);
        },

        destroy() {
            if (this._pinsUpdatedHandler) {
                window.removeEventListener('catalog:pins-updated', this._pinsUpdatedHandler);
            }
        },

        /**
         * ИСПРАВЛЕНО (доработка "объекты на карте не отображаются"): раньше
         * карта всегда центрировалась на ПЕРВОМ пине с фиксированным zoom=10.
         * При объявлениях, разбросанных по разным районам/городам, это легко
         * оставляло большинство пинов ЗА пределами видимой области — они
         * технически отрисовывались, но пользователь их просто не видел без
         * ручного скролла/зума карты, что выглядело как "объекты не
         * отображаются". Теперь при 2+ пинах карта показывает область,
         * вмещающую ВСЕ точки (bounds), а не только первую.
         */
        computeLocation() {
            if (!this.pins.length) {
                return {center: [37.618423, 55.751244], zoom: 10}; // Москва, если объявлений ещё нет
            }

            if (this.pins.length === 1) {
                return {center: [Number(this.pins[0].lng), Number(this.pins[0].lat)], zoom: 14};
            }

            let minLat = Number(this.pins[0].lat);
            let maxLat = minLat;
            let minLng = Number(this.pins[0].lng);
            let maxLng = minLng;

            this.pins.forEach((pin) => {
                const lat = Number(pin.lat);
                const lng = Number(pin.lng);

                minLat = Math.min(minLat, lat);
                maxLat = Math.max(maxLat, lat);
                minLng = Math.min(minLng, lng);
                maxLng = Math.max(maxLng, lng);
            });

            return {bounds: [[minLng, minLat], [maxLng, maxLat]]};
        },

        loadScript(apiKey) {
            if (window.ymaps3) {
                return Promise.resolve();
            }

            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = `https://api-maps.yandex.ru/v3/?apikey=${apiKey}&lang=ru_RU`;
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('script load failed'));
                document.head.appendChild(script);
            });
        },

        async renderMap(el) {
            const {YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapListener} = window.ymaps3;

            this.map = new YMap(el, {location: this.computeLocation()});
            this.map.addChild(new YMapDefaultSchemeLayer());
            this.map.addChild(new YMapDefaultFeaturesLayer());

            this.renderMarkers();

            if (this.selectable) {
                if (!YMapListener) {
                    // Раньше это молча ничего не делало — кнопка "Выделить
                    // область" переключалась, но клики по карте никогда не
                    // обрабатывались, и понять причину можно было только
                    // построчной отладкой. Теперь хотя бы видно в консоли.
                    console.error('YMapListener недоступен в window.ymaps3 — выделение области на карте не будет работать.');
                } else {
                    this.areaListener = new YMapListener({
                        layer: 'any',
                        onClick: (_object, event) => this.handleMapClick(event),
                    });
                    this.map.addChild(this.areaListener);
                }
            }

            // Кнопка "Выделить область" и клики по карте технически ничего
            // не ломают до этого момента (карта просто ещё не существует),
            // но пользователю explicitly видно, что карта ещё грузится —
            // раньше на её месте был просто пустой прямоугольник без
            // подсказки, из-за сети это могло занимать заметное время.
            this.mapReady = true;
        },

        renderMarkers() {
            const {YMapMarker} = window.ymaps3;

            this.markers.forEach((marker) => this.map.removeChild(marker));
            this.markers = [];

            this.pins.forEach((pin) => {
                const markerEl = document.createElement('div');
                markerEl.className = 'flex items-center justify-center px-2 py-1 rounded-full bg-primary-600 text-white text-xs font-semibold shadow cursor-pointer whitespace-nowrap';
                markerEl.textContent = new Intl.NumberFormat('ru-RU').format(pin.price) + ' ₽';
                markerEl.title = pin.address;

                if (pin.url) {
                    markerEl.addEventListener('click', () => {
                        window.location.href = pin.url;
                    });
                }

                const marker = new YMapMarker(
                    {coordinates: [Number(pin.lng), Number(pin.lat)]},
                    markerEl
                );

                this.map.addChild(marker);
                this.markers.push(marker);
            });
        },

        toggleAreaSelection() {
            if (this.selectingArea) {
                this.selectingArea = false;
                this.areaClickPoints = [];

                return;
            }

            this.selectingArea = true;
            this.areaClickPoints = [];
        },

        handleMapClick(event) {
            if (!this.selectingArea) {
                return;
            }

            const coordinates = event?.coordinates ?? event?.originalEvent?.coordinates;

            if (!coordinates) {
                return;
            }

            const [lng, lat] = coordinates;
            this.areaClickPoints.push({lat, lng});

            if (this.areaClickPoints.length >= 2) {
                this.applyRectangleSelection();
            }
        },

        applyRectangleSelection() {
            const [p1, p2] = this.areaClickPoints;

            // Прямоугольник по двум противоположным углам — проще и понятнее
            // в интерфейсе, чем произвольная от руки нарисованная фигура.
            const polygon = [
                {lat: p1.lat, lng: p1.lng},
                {lat: p1.lat, lng: p2.lng},
                {lat: p2.lat, lng: p2.lng},
                {lat: p2.lat, lng: p1.lng},
            ];

            this.drawAreaOverlay(polygon);

            this.selectingArea = false;
            this.hasAreaSelection = true;
            this.areaClickPoints = [];

            this.$wire.call('applyAreaSelection', polygon);
        },

        drawAreaOverlay(polygon) {
            const {YMapFeature} = window.ymaps3;

            if (!YMapFeature) {
                return;
            }

            if (this.areaFeature) {
                this.map.removeChild(this.areaFeature);
                this.areaFeature = null;
            }

            const ring = [...polygon.map((p) => [p.lng, p.lat]), [polygon[0].lng, polygon[0].lat]];

            this.areaFeature = new YMapFeature({
                geometry: {type: 'Polygon', coordinates: [ring]},
                style: {
                    fill: 'rgba(37, 99, 235, 0.15)',
                    stroke: [{color: '#2563eb', width: 2}],
                },
            });

            this.map.addChild(this.areaFeature);
        },

        clearAreaSelection() {
            this.selectingArea = false;
            this.hasAreaSelection = false;
            this.areaClickPoints = [];

            if (this.areaFeature) {
                this.map.removeChild(this.areaFeature);
                this.areaFeature = null;
            }

            this.$wire.call('clearAreaSelection');
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('yandexMap', yandexMap);
});
