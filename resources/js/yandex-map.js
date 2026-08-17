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
 * ВАЖНО: этот код не был протестирован в реальном браузере с настоящим
 * YANDEX_MAPS_API_KEY (песочница без доступа в интернет к api-maps.yandex.ru).
 * Логика написана по документации API 3.0 — перед продакшеном проверьте
 * визуально после того, как получите ключ (раздел 7.5 плана). Это касается
 * в первую очередь имени/формы события клика по карте (YMapListener/onClick)
 * и класса YMapFeature для отрисовки полигона выделения.
 */
function yandexMap(initialPins, apiKey, selectable) {
    return {
        map: null,
        markers: [],
        pins: initialPins || [],
        selectable: !!selectable,

        selectingArea: false,
        hasAreaSelection: false,
        areaClickPoints: [],
        areaFeature: null,
        areaListener: null,

        init(el) {
            this.loadScript(apiKey)
                .then(() => window.ymaps3.ready)
                .then(() => this.renderMap(el))
                .catch((error) => {
                    console.error('Не удалось загрузить Yandex Maps JS API 3.0:', error);
                });

            window.addEventListener('catalog:pins-updated', (event) => {
                this.pins = event.detail?.pins ?? this.pins;

                if (this.map) {
                    this.renderMarkers();
                }
            });
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

            const center = this.pins.length
                ? [Number(this.pins[0].lng), Number(this.pins[0].lat)]
                : [37.618423, 55.751244]; // Москва, если объявлений ещё нет

            this.map = new YMap(el, {location: {center, zoom: 10}});
            this.map.addChild(new YMapDefaultSchemeLayer());
            this.map.addChild(new YMapDefaultFeaturesLayer());

            this.renderMarkers();

            if (this.selectable && YMapListener) {
                this.areaListener = new YMapListener({
                    layer: 'any',
                    onClick: (_object, event) => this.handleMapClick(event),
                });
                this.map.addChild(this.areaListener);
            }
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
