/**
 * Подбор адреса при создании объявления — эпик 20 (Веха 2, "Карта — выбор
 * адреса при создании объявления (геокодер)"). Регистрирует Alpine-компонент
 * `addressGeocoder(address, lat, lng, apiKey)`, используемый в
 * resources/views/components/address-picker.blade.php.
 *
 * Источники данных:
 *  - HTTP Geocoder API Яндекса (geocode-maps.yandex.ru/1.x) — подсказки по
 *    мере ввода адреса и обратное геокодирование (координаты -> адрес).
 *  - Yandex Maps JS API 3.0 (тот же скрипт, что и в resources/js/yandex-map.js)
 *    — превью-карта с меткой, кликабельной для уточнения точки.
 *
 * ВАЖНО: как и yandex-map.js, этот код не был протестирован в реальном
 * браузере с настоящим YANDEX_MAPS_API_KEY (песочница без доступа в
 * интернет к api-maps.yandex.ru и geocode-maps.yandex.ru). Логика написана
 * по документации API — перед продакшеном проверьте визуально после того,
 * как получите ключ (раздел 7.5 плана).
 */
function addressGeocoder(initialAddress, initialLat, initialLng, apiKey) {
    return {
        apiKey,
        query: initialAddress || '',
        lat: initialLat ? Number(initialLat) : null,
        lng: initialLng ? Number(initialLng) : null,
        suggestions: [],
        showSuggestions: false,
        map: null,
        marker: null,

        init() {
            this.loadScript()
                .then(() => window.ymaps3.ready)
                .then(() => this.renderMap())
                .catch((error) => {
                    console.error('Не удалось загрузить Yandex Maps JS API 3.0:', error);
                });
        },

        loadScript() {
            if (window.ymaps3) {
                return Promise.resolve();
            }

            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = `https://api-maps.yandex.ru/v3/?apikey=${this.apiKey}&lang=ru_RU`;
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('script load failed'));
                document.head.appendChild(script);
            });
        },

        async renderMap() {
            const {YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapListener} = window.ymaps3;

            const hasInitialPosition = this.lat !== null && this.lng !== null;
            const center = hasInitialPosition ? [this.lng, this.lat] : [37.618423, 55.751244]; // Москва по умолчанию

            this.map = new YMap(this.$refs.pickerMap, {location: {center, zoom: hasInitialPosition ? 15 : 10}});
            this.map.addChild(new YMapDefaultSchemeLayer());
            this.map.addChild(new YMapDefaultFeaturesLayer());

            if (hasInitialPosition) {
                this.placeMarker(this.lat, this.lng);
            }

            if (YMapListener) {
                this.map.addChild(new YMapListener({
                    layer: 'any',
                    onClick: (_object, event) => this.handleMapClick(event),
                }));
            }
        },

        handleMapClick(event) {
            const coordinates = event?.coordinates ?? event?.originalEvent?.coordinates;

            if (!coordinates) {
                return;
            }

            const [lng, lat] = coordinates;
            this.setPosition(lat, lng);
            this.reverseGeocode(lat, lng);
        },

        placeMarker(lat, lng) {
            const {YMapMarker} = window.ymaps3;

            if (this.marker) {
                this.map.removeChild(this.marker);
                this.marker = null;
            }

            const markerEl = document.createElement('div');
            markerEl.className = 'w-4 h-4 rounded-full bg-primary-600 border-2 border-white shadow';

            this.marker = new YMapMarker({coordinates: [lng, lat]}, markerEl);
            this.map.addChild(this.marker);
        },

        setPosition(lat, lng) {
            this.lat = lat;
            this.lng = lng;
            this.placeMarker(lat, lng);

            this.$wire.set('lat', lat);
            this.$wire.set('lng', lng);
        },

        async search() {
            if (!this.query || this.query.trim().length < 3) {
                this.suggestions = [];
                this.showSuggestions = false;

                return;
            }

            try {
                const url = `https://geocode-maps.yandex.ru/1.x/?apikey=${this.apiKey}&format=json&results=5&geocode=${encodeURIComponent(this.query)}`;
                const response = await fetch(url);
                const data = await response.json();

                const members = data?.response?.GeoObjectCollection?.featureMember ?? [];

                this.suggestions = members.map((member) => {
                    const [lng, lat] = member.GeoObject.Point.pos.split(' ').map(Number);

                    return {
                        address: member.GeoObject.metaDataProperty.GeocoderMetaData.text,
                        lat,
                        lng,
                    };
                });

                this.showSuggestions = this.suggestions.length > 0;
            } catch (error) {
                console.error('Ошибка геокодирования адреса:', error);
            }
        },

        select(item) {
            this.query = item.address;
            this.showSuggestions = false;
            this.suggestions = [];

            this.$wire.set('address', item.address);
            this.setPosition(item.lat, item.lng);

            if (this.map) {
                this.map.setLocation({center: [item.lng, item.lat], zoom: 15});
            }
        },

        async reverseGeocode(lat, lng) {
            try {
                const url = `https://geocode-maps.yandex.ru/1.x/?apikey=${this.apiKey}&format=json&results=1&geocode=${lng},${lat}`;
                const response = await fetch(url);
                const data = await response.json();

                const member = data?.response?.GeoObjectCollection?.featureMember?.[0];

                if (!member) {
                    return;
                }

                const address = member.GeoObject.metaDataProperty.GeocoderMetaData.text;
                this.query = address;
                this.$wire.set('address', address);
            } catch (error) {
                console.error('Ошибка обратного геокодирования:', error);
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('addressGeocoder', addressGeocoder);
});
