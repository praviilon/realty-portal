<?php

namespace App\Livewire\Workspace;

use App\Models\PropertyPhoto;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Шаговая форма создания/редактирования объявления (рабочее пространство) —
 * эпик 23 дорожной карты (Веха 3). По образцу CommercialProperty\CreateWizard
 * (эпик 13), но с двумя отличиями: цена — не 1:1, а список ставок по разным
 * периодам (workspace_pricing, минимум одна строка), и есть дополнительный
 * шаг "Доступ и удобства" (access_time/amenities/extra_options).
 */
#[Layout('layouts.app')]
class CreateWizard extends Component
{
    use WithFileUploads;

    /**
     * Лимит фото на объявление — по просьбе пользователя (раньше выбор
     * слишком большого количества фото за раз мог "класть" страницу с
     * ошибкой 503 на этапе загрузки). См. также App\Livewire\Property\CreateWizard
     * и App\Livewire\CommercialProperty\CreateWizard — одинаковый лимит везде.
     */
    protected const MAX_PHOTOS = 5;

    public int $step = 1;

    public ?Workspace $editing = null;

    // Шаг 1 — основное
    public string $workspaceType = 'workspace';

    public ?string $workspaceSubtype = 'fixed';

    public string $ownerType = 'owner';

    public string $contactType = 'calls_and_messages';

    // Шаг 2 — адрес
    public string $address = '';

    public ?float $lat = null;

    public ?float $lng = null;

    public ?string $metroStation = null;

    public ?int $metroDistanceMin = null;

    // Шаг 3 — характеристики
    public string $buildingType = 'business_center';

    public string $entranceType = 'separate';

    public ?int $floor = null;

    public ?int $totalFloors = null;

    /** @var string[] */
    public array $floorFeatures = [];

    public ?int $area = null;

    /** @var array<int, array{type: string, time_from: ?string, time_to: ?string}> */
    public array $accessTime = [
        ['type' => 'weekdays', 'time_from' => '09:00', 'time_to' => '20:00'],
    ];

    /** @var string[] */
    public array $amenities = [];

    /** @var string[] */
    public array $extraOptions = [];

    public string $description = '';

    // Шаг 4 — цена и условия
    /** @var array<int, array{period: string, price: ?int}> */
    public array $pricing = [
        ['period' => 'hour', 'price' => null],
    ];

    public ?int $deposit = null;

    public bool $utilitiesIncluded = false;

    // Шаг 5 — фотографии
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

    public function mount(?Workspace $workspace = null): void
    {
        if ($workspace && $workspace->exists) {
            abort_unless(Auth::id() === $workspace->user_id, 403);

            $this->editing = $workspace;
            $this->workspaceType = $workspace->workspace_type;
            $this->workspaceSubtype = $workspace->workspace_subtype;
            $this->ownerType = $workspace->owner_type;
            $this->contactType = $workspace->contact_type;
            $this->address = $workspace->address;
            $this->lat = (float) $workspace->lat;
            $this->lng = (float) $workspace->lng;
            $this->metroStation = $workspace->metro_station;
            $this->metroDistanceMin = $workspace->metro_distance_min;
            $this->buildingType = $workspace->building_type;
            $this->entranceType = $workspace->entrance_type ?? 'separate';
            $this->floor = $workspace->floor;
            $this->totalFloors = $workspace->total_floors;
            // array_intersect — на случай, если у редактируемого объявления
            // ещё сохранено старое значение 'separate_entrance' (убрано из
            // Workspace::floorFeatureLabels() по просьбе пользователя):
            // иначе оно осталось бы выбранным в $this->floorFeatures без
            // соответствующего чекбокса в форме и валидация на submit()
            // упала бы, так как 'separate_entrance' больше не входит в
            // список допустимых значений.
            $this->floorFeatures = array_values(array_intersect(
                $workspace->floor_features ?? [],
                array_keys(Workspace::floorFeatureLabels())
            ));
            $this->area = $workspace->area;
            $this->accessTime = $workspace->access_time ?: $this->accessTime;
            $this->amenities = $workspace->amenities ?? [];
            $this->extraOptions = $workspace->extra_options ?? [];
            $this->description = $workspace->description;
            $this->deposit = $workspace->deposit;
            $this->utilitiesIncluded = $workspace->utilities_included;

            $existingPricing = $workspace->pricing()->get(['period', 'price'])
                ->map(fn (WorkspacePricing $row) => ['period' => $row->period, 'price' => $row->price])
                ->all();

            if ($existingPricing !== []) {
                $this->pricing = $existingPricing;
            }
        }
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'workspaceType' => ['required', 'in:workspace,office,meeting_room,conference_room'],
                'workspaceSubtype' => ['nullable', 'in:fixed,flexible'],
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
                'buildingType' => ['required', 'in:administrative,business_center,residential,shopping_center'],
                'entranceType' => ['nullable', 'in:separate,common'],
                'floor' => ['required', 'integer', 'min:1', 'max:200'],
                'totalFloors' => ['required', 'integer', 'min:1', 'max:200', 'gte:floor'],
                'floorFeatures' => ['array'],
                'floorFeatures.*' => ['string', 'in:parking,security,reception'],
                'area' => ['required', 'integer', 'min:1', 'max:100000'],
                'accessTime' => ['required', 'array', 'min:1'],
                'accessTime.*.type' => ['required', 'in:weekdays,weekends,daily,round_the_clock'],
                'accessTime.*.time_from' => ['nullable', 'date_format:H:i'],
                'accessTime.*.time_to' => ['nullable', 'date_format:H:i'],
                'amenities' => ['array'],
                'amenities.*' => ['string', 'in:wifi,coffee,kitchen,printer,whiteboard,tv_screen,phone_booth,air_conditioning'],
                'extraOptions' => ['array'],
                'extraOptions.*' => ['string', 'in:cleaning,catering,reception_service,secretary_support,tech_support'],
                'description' => ['required', 'string', 'min:10', 'max:5000'],
            ],
            4 => [
                'pricing' => ['required', 'array', 'min:1'],
                'pricing.*.period' => ['required', 'distinct', 'in:hour,day,week,month'],
                'pricing.*.price' => ['required', 'integer', 'min:1'],
                'deposit' => ['nullable', 'integer', 'min:0'],
                'utilitiesIncluded' => ['boolean'],
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

    public function addAccessTimeRow(): void
    {
        $this->accessTime[] = ['type' => 'weekdays', 'time_from' => '09:00', 'time_to' => '20:00'];
    }

    public function removeAccessTimeRow(int $index): void
    {
        if (count($this->accessTime) <= 1) {
            return;
        }

        unset($this->accessTime[$index]);
        $this->accessTime = array_values($this->accessTime);
    }

    public function addPricingRow(): void
    {
        $this->pricing[] = ['period' => 'day', 'price' => null];
    }

    public function removePricingRow(int $index): void
    {
        if (count($this->pricing) <= 1) {
            return;
        }

        unset($this->pricing[$index]);
        $this->pricing = array_values($this->pricing);
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
            'workspace_type' => $this->workspaceType,
            'workspace_subtype' => $this->workspaceType === 'workspace' ? $this->workspaceSubtype : null,
            'owner_type' => $this->ownerType,
            'contact_type' => $this->contactType,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'metro_station' => $this->metroStation ?: null,
            'metro_distance_min' => $this->metroDistanceMin,
            'building_type' => $this->buildingType,
            'entrance_type' => $this->entranceType,
            'floor' => $this->floor,
            'total_floors' => $this->totalFloors,
            'floor_features' => $this->floorFeatures,
            'area' => $this->area,
            'access_time' => $this->accessTime,
            'amenities' => $this->amenities,
            'extra_options' => $this->extraOptions,
            'description' => $this->description,
            'deposit' => $this->deposit,
            'utilities_included' => $this->utilitiesIncluded,
            // Любое создание/редактирование уходит на повторную модерацию.
            'status' => 'moderation',
            'rejection_reason' => null,
        ];

        if ($this->editing) {
            $this->editing->update($attributes);
            $listing = $this->editing;
        } else {
            $listing = Workspace::create($attributes);
        }

        $listing->pricing()->delete();
        foreach ($this->pricing as $row) {
            WorkspacePricing::create([
                'workspace_id' => $listing->id,
                'period' => $row['period'],
                'price' => $row['price'],
            ]);
        }

        $nextSortOrder = $listing->photos()->max('sort_order') + 1;

        foreach ($this->newPhotos as $index => $photo) {
            $path = $photo->store('property-photos/workspace-' . $listing->id, 'public');

            PropertyPhoto::create([
                'photoable_type' => Workspace::class,
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
        return view('livewire.workspace.create-wizard', [
            'workspaceTypeLabels' => Workspace::workspaceTypeLabels(),
            'workspaceSubtypeLabels' => Workspace::workspaceSubtypeLabels(),
            'ownerTypeLabels' => Workspace::ownerTypeLabels(),
            'contactTypeLabels' => Workspace::contactTypeLabels(),
            'buildingTypeLabels' => Workspace::buildingTypeLabels(),
            'entranceTypeLabels' => Workspace::entranceTypeLabels(),
            'floorFeatureLabels' => Workspace::floorFeatureLabels(),
            'accessTimeTypeLabels' => Workspace::accessTimeTypeLabels(),
            'amenityLabels' => Workspace::amenityLabels(),
            'extraOptionLabels' => Workspace::extraOptionLabels(),
            'pricingPeriodLabels' => Workspace::pricingPeriodLabels(),
            'photoSlotsRemaining' => max(0, self::MAX_PHOTOS - $this->totalPhotoCount()),
            'maxPhotos' => self::MAX_PHOTOS,
        ]);
    }
}
