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
 * ИСПРАВЛЕНО (доработка после Вехи 3, п.4 — карта нигде не работала даже с
 * реальным ключом): метод инициализации был назван `init()`, что совпадает
 * со служебным именем — Alpine автоматически вызывает метод `init` у любого
 * x-data объекта сразу после его создания, ещё до обработки директивы
 * x-init. Из-за этого `init()` вызывался дважды подряд (один раз Alpine'ом
 * автоматически, второй раз — явно через x-init="init()"), что означало
 * двойную загрузку скрипта Yandex Maps API и создание двух карт поверх
 * одного и того же контейнера. Переименовано в `initMap()`, чтобы вызов
 * происходил ровно один раз — так, как явно указано в x-init.
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
        mapReady: false,
        marker: null,

        initMap() {
            this.loadScript()
                .then(() => window.ymaps3.ready)
                .then(() => this.renderMap())
                .catch((error) => {
                    console.error('Не удалось загрузить Yandex Maps JS API 3.0:', error);
                });
        },

        // ИСПРАВЛЕНО (по итогам разбора регрессии "DomContext: attaching to
        // entity with destroyed DomContext" — см. подробный комментарий в
        // resources/js/yandex-map.js): раньше JS-объект карты просто
        // "бросался" при уходе со 2-го шага мастера создания объявления —
        // сама Yandex Maps API никогда не узнавала, что её контейнер исчез.
        // У YMap есть штатный метод destroy() именно для этого случая (см.
        // документацию API 3.0, класс YMap) — вызываем его здесь (Alpine
        // вызывает destroy() автоматически при удалении элемента, аналогично
        // init()).
        destroy() {
            if (this.map) {
                try {
                    this.map.destroy();
                } catch (error) {
                    // Карта могла быть уже разрушена или её контейнер уже не
                    // в DOM — это не критично, просто освобождаем ссылку.
                }
                this.map = null;
            }

            this.mapReady = false;
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

            // ИСПРАВЛЕНО (по итогам разбора регрессии "DomContext: attaching
            // to entity with destroyed DomContext" в resources/js/yandex-map.js
            // — см. подробный комментарий там): карта считается готовой, как
            // только она сама и базовые слои созданы. Необязательные шаги
            // ниже (маркер выбранной точки, обработчик кликов) обёрнуты в
            // try/catch и больше не могут оставить пользователя с вечной
            // надписью "Загрузка карты…", если сами вдруг упадут на реальном
            // API.
            this.mapReady = true;

            if (hasInitialPosition) {
                try {
                    this.placeMarker(this.lat, this.lng);
                } catch (error) {
                    console.error('Не удалось отметить точку на карте:', error);
                }
            }

            if (!YMapListener) {
                console.error('YMapListener недоступен в window.ymaps3 — уточнение адреса кликом по карте не будет работать.');
            } else {
                try {
                    this.map.addChild(new YMapListener({
                        layer: 'any',
                        onClick: (_object, event) => this.handleMapClick(event),
                    }));
                } catch (error) {
                    console.error('Не удалось включить уточнение адреса кликом по карте:', error);
                }
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
            // ИСПРАВЛЕНО (доработка "не обновляется карта/широта/долгота при
            // выборе адреса"): карта грузится асинхронно (сторонний скрипт
            // Yandex Maps через сеть), и это может занять больше времени, чем
            // пользователю — набрать адрес и кликнуть по подсказке. Если
            // select()/setPosition() срабатывали раньше, чем renderMap()
            // успевал создать this.map, здесь падало
            // "Cannot read properties of null (reading 'addChild')", и это
            // исключение обрывало setPosition() ДО того, как он успевал
            // вызвать $wire.set('lat', ...)/$wire.set('lng', ...) — снаружи
            // выглядело так, будто широта и долгота вообще не обновляются.
            // Теперь при отсутствующей карте просто ничего не рисуем: как
            // только renderMap() всё же завершится, он сам расставит маркер
            // по актуальным this.lat/this.lng (см. renderMap ниже).
            if (!this.map) {
                return;
            }

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

            // Синхронизируем с Livewire-компонентом ПЕРВЫМ делом, до попытки
            // отрисовать маркер — карта может быть ещё не готова (см.
            // комментарий в placeMarker выше), а поля широты/долготы должны
            // обновляться в любом случае, независимо от состояния карты.
            this.$wire.set('lat', lat);
            this.$wire.set('lng', lng);

            this.placeMarker(lat, lng);
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
                try {
                    this.map.setLocation({center: [item.lng, item.lat], zoom: 15});
                } catch (error) {
                    console.error('Не удалось переместить карту к выбранному адресу:', error);
                }
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
