/**
 * Карта объектов — Yandex Maps JS API 3.0 (см. раздел 1 и 7.5 технического плана).
 *
 * Регистрирует Alpine-компонент `yandexMap(pins, apiKey)`, используемый в
 * resources/views/components/yandex-map.blade.php. Подгружает скрипт API 3.0
 * динамически (ванильный JS, без npm-пакета), расставляет пины по lat/lng из
 * результатов поиска и обновляет их при изменении фильтров каталога
 * (событие `catalog:pins-updated`, которое диспатчит Livewire-компонент).
 *
 * ВАЖНО: этот код не был протестирован в реальном браузере с настоящим
 * YANDEX_MAPS_API_KEY (песочница без доступа в интернет к api-maps.yandex.ru).
 * Логика написана по документации API 3.0 — перед продакшеном проверьте
 * визуально после того, как получите ключ (раздел 7.5 плана).
 */
function yandexMap(initialPins, apiKey) {
    return {
        map: null,
        markers: [],
        pins: initialPins || [],

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
            const {YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer} = window.ymaps3;

            const center = this.pins.length
                ? [Number(this.pins[0].lng), Number(this.pins[0].lat)]
                : [37.618423, 55.751244]; // Москва, если объявлений ещё нет

            this.map = new YMap(el, {location: {center, zoom: 10}});
            this.map.addChild(new YMapDefaultSchemeLayer());
            this.map.addChild(new YMapDefaultFeaturesLayer());

            this.renderMarkers();
        },

        renderMarkers() {
            const {YMapMarker} = window.ymaps3;

            this.markers.forEach((marker) => this.map.removeChild(marker));
            this.markers = [];

            this.pins.forEach((pin) => {
                const markerEl = document.createElement('div');
                markerEl.className = 'flex items-center justify-center px-2 py-1 rounded-full bg-blue-600 text-white text-xs font-semibold shadow cursor-pointer whitespace-nowrap';
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
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('yandexMap', yandexMap);
});
