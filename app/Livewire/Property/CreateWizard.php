<?php

namespace App\Livewire\Property;

use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Шаговая форма создания/редактирования объявления (жилая недвижимость) —
 * эпик 7 дорожной карты. Секция 2 технического плана описывает раздельные
 * Step-компоненты на тип объекта; здесь для MVP один компонент с шагами,
 * т.к. для жилой недвижимости набор полей один и тот же независимо от
 * property_type (в отличие от коммерческой/рабочих пространств Вехи 2-3).
 *
 * Координаты (lat/lng) вводятся вручную — выбор адреса через геокодер на
 * карте это отдельный эпик 20 (Веха 2, "Карта — выбор адреса при создании
 * объявления").
 */
#[Layout('layouts.app')]
class CreateWizard extends Component
{
    use WithFileUploads;

    /**
     * Лимит фото на объявление — по просьбе пользователя (раньше выбор
     * слишком большого количества фото за раз мог "класть" страницу с
     * ошибкой 503 на этапе загрузки). См. также App\Livewire\CommercialProperty\CreateWizard
     * и App\Livewire\Workspace\CreateWizard — одинаковый лимит везде.
     */
    protected const MAX_PHOTOS = 5;

    public int $step = 1;

    public ?ResidentialProperty $editing = null;

    // Шаг 1
    public string $dealType = 'sale';

    public string $propertyType = 'apartment';

    // Кто разместил объявление и способ связи — по аналогии с
    // App\Livewire\Workspace\CreateWizard (значения те же: owner/agent,
    // calls_and_messages/messages_only).
    public string $ownerType = 'owner';

    public string $contactType = 'calls_and_messages';

    // Шаг 2
    public string $address = '';

    public ?float $lat = null;

    public ?float $lng = null;

    public ?string $metroStation = null;

    public ?int $metroDistanceMin = null;

    // Шаг 3
    public ?int $area = null;

    public ?int $floor = null;

    public ?int $totalFloors = null;

    public string $heatingType = 'central';

    public string $finishingType = 'fine';

    public string $furniture = 'none';

    /** @var string[] */
    public array $floorFeatures = [];

    public string $description = '';

    // Шаг 4 — цена и условия (набор полей зависит от dealType, по аналогии
    // с App\Livewire\CommercialProperty\CreateWizard)
    public ?int $price = null;

    public ?int $pricePerMonth = null;

    public ?int $deposit = null;

    public ?int $commission = null;

    public bool $utilitiesIncluded = false;

    public string $rentType = 'direct';

    // Шаг 5
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $newPhotos = [];

    /**
     * Промежуточное свойство, к которому привязан сам <input type="file">
     * (см. x-photo-dropzone) — исправление бага, из-за которого повторный
     * выбор файлов ПОЛНОСТЬЮ заменял $newPhotos вместо добавления к нему
     * (стандартное поведение wire:model на файловом input). См.
     * updatedIncomingPhotos() ниже.
     */
    public array $incomingPhotos = [];

    public function mount(?ResidentialProperty $residentialProperty = null): void
    {
        if ($residentialProperty && $residentialProperty->exists) {
            abort_unless(Auth::id() === $residentialProperty->user_id, 403);

            $this->editing = $residentialProperty;
            $this->dealType = $residentialProperty->deal_type;
            $this->propertyType = $residentialProperty->property_type;
            $this->ownerType = $residentialProperty->owner_type;
            $this->contactType = $residentialProperty->contact_type;
            $this->address = $residentialProperty->address;
            $this->lat = (float) $residentialProperty->lat;
            $this->lng = (float) $residentialProperty->lng;
            $this->metroStation = $residentialProperty->metro_station;
            $this->metroDistanceMin = $residentialProperty->metro_distance_min;
            $this->area = $residentialProperty->area;
            $this->floor = $residentialProperty->floor;
            $this->totalFloors = $residentialProperty->total_floors;
            $this->heatingType = $residentialProperty->heating_type ?? 'central';
            $this->finishingType = $residentialProperty->finishing_type ?? 'fine';
            $this->furniture = $residentialProperty->furniture ?? 'none';
            // array_intersect — на случай, если у объявления сохранено
            // значение, которого больше нет в ResidentialProperty::floorFeatureLabels()
            // (см. аналогичную защиту в App\Livewire\Workspace\CreateWizard).
            $this->floorFeatures = array_values(array_intersect(
                $residentialProperty->floor_features ?? [],
                array_keys(ResidentialProperty::floorFeatureLabels())
            ));
            $this->description = $residentialProperty->description;

            if ($residentialProperty->deal_type === 'rent') {
                $this->pricePerMonth = $residentialProperty->price;
                $this->deposit = $residentialProperty->deposit;
                $this->commission = $residentialProperty->commission;
                $this->utilitiesIncluded = (bool) $residentialProperty->utilities_included;
                $this->rentType = $residentialProperty->rent_type ?? 'direct';
            } else {
                $this->price = $residentialProperty->price;
                $this->commission = $residentialProperty->commission;
            }
        }
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'dealType' => ['required', 'in:sale,rent'],
                'propertyType' => ['required', 'in:apartment,house,room,studio'],
                'ownerType' => ['required', 'in:owner,agent'],
                'contactType' => ['required', 'in:calls_and_messages,messages_only'],
            ],
            2 => [
                'address' => ['required', 'string', 'min:5', 'max:255'],
                'lat' => ['required', 'numeric', 'between:-90,90'],
                'lng' => ['required', 'numeric', 'between:-180,180'],
                'metroStation' => ['nullable', 'string', 'max:255'],
                'metroDistanceMin' => ['nullable', 'integer', 'min:0', 'max:180'],
            ],
            3 => [
                'area' => ['required', 'integer', 'min:1', 'max:100000'],
                'floor' => ['required', 'integer', 'min:1', 'max:200'],
                'totalFloors' => ['required', 'integer', 'min:1', 'max:200', 'gte:floor'],
                'heatingType' => ['nullable', 'in:central,autonomous,none'],
                'finishingType' => ['nullable', 'in:none,rough,fine'],
                'furniture' => ['nullable', 'in:none,partial,full'],
                'floorFeatures' => ['array'],
                'floorFeatures.*' => ['string', 'in:no_elevator'],
                'description' => ['required', 'string', 'min:10', 'max:5000'],
            ],
            4 => $this->dealType === 'rent' ? [
                'pricePerMonth' => ['required', 'integer', 'min:1'],
                'deposit' => ['nullable', 'integer', 'min:0'],
                'commission' => ['nullable', 'integer', 'min:0'],
                'utilitiesIncluded' => ['boolean'],
                'rentType' => ['required', 'in:direct,sublease'],
            ] : [
                'price' => ['required', 'integer', 'min:1'],
                'commission' => ['nullable', 'integer', 'min:0'],
            ],
            5 => [
                // Доп. проверка на сервере (даже если клиентское
                // ограничение в x-photo-dropzone почему-то не сработало) —
                // общее число фото (уже загруженные + новые) не больше
                // MAX_PHOTOS.
                'newPhotos' => [function ($attribute, $value, $fail) {
                    if ($this->totalPhotoCount() > self::MAX_PHOTOS) {
                        $fail('Можно загрузить не более ' . self::MAX_PHOTOS . ' фотографий на одно объявление.');
                    }
                }],
                'newPhotos.*' => ['nullable', 'image', 'max:5120'],
            ],
            default => [],
        };
    }

    /**
     * Общее число фото объявления: уже сохранённые (при редактировании) +
     * ещё не отправленные $newPhotos. Используется и для лимита при выборе
     * новых файлов (updatedIncomingPhotos), и при финальной проверке в
     * rulesForStep(5).
     */
    protected function totalPhotoCount(): int
    {
        $existingCount = $this->editing ? $this->editing->photos()->count() : 0;

        return $existingCount + count($this->newPhotos);
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));

        $this->step = min($this->step + 1, 5);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    /**
     * Объединяет только что выбранные файлы (несут в $incomingPhotos, к
     * которому привязан сам <input>) с уже накопленными в $newPhotos —
     * исправление бага, при котором повторный выбор фото стирал ранее
     * выбранные. Лишние сверх лимита файлы отбрасываются и сразу удаляются
     * из временного хранилища (иначе они остались бы висеть в
     * storage/app/livewire-tmp, ни разу не попав в объявление — та же
     * проблема "фото остаются в базе", которую попросили исправить).
     */
    public function updatedIncomingPhotos(): void
    {
        $this->resetErrorBag('incomingPhotos');

        $this->validate([
            'incomingPhotos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $slotsLeft = max(0, self::MAX_PHOTOS - $this->totalPhotoCount());

        foreach ($this->incomingPhotos as $index => $photo) {
            if ($index >= $slotsLeft) {
                $photo->delete();

                continue;
            }

            $this->newPhotos[] = $photo;
        }

        if (count($this->incomingPhotos) > $slotsLeft) {
            $this->addError('incomingPhotos', 'Можно загрузить не более ' . self::MAX_PHOTOS . ' фотографий на одно объявление.');
        }

        $this->incomingPhotos = [];
    }

    public function removeNewPhoto(int $index): void
    {
        // Удаляем сам временный файл (не только запись в массиве) — иначе
        // он остаётся висеть в storage/app/livewire-tmp до плановой
        // очистки Livewire, даже если пользователь отменил загрузку сразу
        // после выбора фото.
        if (isset($this->newPhotos[$index]) && method_exists($this->newPhotos[$index], 'delete')) {
            $this->newPhotos[$index]->delete();
        }

        unset($this->newPhotos[$index]);
        $this->newPhotos = array_values($this->newPhotos);
    }

    /**
     * Удаление уже сохранённого (не нового) фото при редактировании
     * объявления — раньше такой возможности не было вовсе (у уже
     * загруженных фото не было крестика). ->delete() на модели
     * PropertyPhoto удаляет и файл в storage (см. App\Models\PropertyPhoto::booted()).
     */
    public function removeExistingPhoto(int $photoId): void
    {
        if (! $this->editing) {
            return;
        }

        $photo = $this->editing->photos()->whereKey($photoId)->first();

        if ($photo) {
            $photo->delete();
            $this->editing->load('photos');
        }
    }

    public function submit(): void
    {
        $this->validate([
            ...$this->rulesForStep(1),
            ...$this->rulesForStep(2),
            ...$this->rulesForStep(3),
            ...$this->rulesForStep(4),
            ...$this->rulesForStep(5),
        ]);

        $attributes = [
            'user_id' => Auth::id(),
            'deal_type' => $this->dealType,
            'property_type' => $this->propertyType,
            'owner_type' => $this->ownerType,
            'contact_type' => $this->contactType,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'metro_station' => $this->metroStation ?: null,
            'metro_distance_min' => $this->metroDistanceMin,
            'area' => $this->area,
            'floor' => $this->floor,
            'total_floors' => $this->totalFloors,
            'heating_type' => $this->heatingType,
            'finishing_type' => $this->finishingType,
            'furniture' => $this->furniture,
            'floor_features' => $this->floorFeatures,
            'description' => $this->description,
            // Любое создание/редактирование уходит на повторную модерацию.
            'status' => 'moderation',
            'rejection_reason' => null,
        ];

        // Шаг 4 ("Цена") — набор полей зависит от dealType, по аналогии с
        // App\Livewire\CommercialProperty\CreateWizard::submit(). В отличие
        // от коммерческой недвижимости цена продажи/аренды в месяц хранится
        // в одной и той же колонке price (см. комментарий в миграции
        // 2026_08_19_000003_..._price_details_..._table) — на неё уже
        // завязаны каталог, сравнение, избранное и кабинет.
        if ($this->dealType === 'rent') {
            $attributes['price'] = $this->pricePerMonth;
            $attributes['deposit'] = $this->deposit;
            $attributes['commission'] = $this->commission;
            $attributes['rent_type'] = $this->rentType;
            $attributes['utilities_included'] = $this->utilitiesIncluded;
        } else {
            $attributes['price'] = $this->price;
            $attributes['deposit'] = null;
            $attributes['commission'] = $this->commission;
            $attributes['rent_type'] = null;
            $attributes['utilities_included'] = false;
        }

        if ($this->editing) {
            $this->editing->update($attributes);
            $listing = $this->editing;
        } else {
            $listing = ResidentialProperty::create($attributes);
        }

        $nextSortOrder = $listing->photos()->max('sort_order') + 1;

        foreach ($this->newPhotos as $index => $photo) {
            $path = $photo->store('property-photos/' . $listing->id, 'public');

            PropertyPhoto::create([
                'photoable_type' => ResidentialProperty::class,
                'photoable_id' => $listing->id,
                'path' => $path,
                'is_main' => $nextSortOrder === 1 && $index === 0,
                'sort_order' => $nextSortOrder + $index,
            ]);
        }

        session()->flash('status', $this->editing
            ? 'Объявление обновлено и отправлено на повторную модерацию.'
            : 'Объявление отправлено на модерацию. Оно появится в каталоге после проверки администратором.');

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.property.create-wizard', [
            'propertyTypeLabels' => ResidentialProperty::propertyTypeLabels(),
            'dealTypeLabels' => ResidentialProperty::dealTypeLabels(),
            'ownerTypeLabels' => ResidentialProperty::ownerTypeLabels(),
            'contactTypeLabels' => ResidentialProperty::contactTypeLabels(),
            'heatingTypeLabels' => ResidentialProperty::heatingTypeLabels(),
            'finishingTypeLabels' => ResidentialProperty::finishingTypeLabels(),
            'furnitureLabels' => ResidentialProperty::furnitureLabels(),
            'floorFeatureLabels' => ResidentialProperty::floorFeatureLabels(),
            'rentTypeLabels' => ResidentialProperty::rentTypeLabels(),
            'photoSlotsRemaining' => max(0, self::MAX_PHOTOS - $this->totalPhotoCount()),
            'maxPhotos' => self::MAX_PHOTOS,
        ]);
    }
}
