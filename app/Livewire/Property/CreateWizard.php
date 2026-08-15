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

    public int $step = 1;

    public ?ResidentialProperty $editing = null;

    // Шаг 1
    public string $dealType = 'sale';

    public string $propertyType = 'apartment';

    // Шаг 2
    public string $address = '';

    public ?float $lat = null;

    public ?float $lng = null;

    // Шаг 3
    public ?int $area = null;

    public ?int $floor = null;

    public ?int $totalFloors = null;

    public ?int $price = null;

    public string $description = '';

    // Шаг 4
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $newPhotos = [];

    public function mount(?ResidentialProperty $residentialProperty = null): void
    {
        if ($residentialProperty && $residentialProperty->exists) {
            abort_unless(Auth::id() === $residentialProperty->user_id, 403);

            $this->editing = $residentialProperty;
            $this->dealType = $residentialProperty->deal_type;
            $this->propertyType = $residentialProperty->property_type;
            $this->address = $residentialProperty->address;
            $this->lat = (float) $residentialProperty->lat;
            $this->lng = (float) $residentialProperty->lng;
            $this->area = $residentialProperty->area;
            $this->floor = $residentialProperty->floor;
            $this->totalFloors = $residentialProperty->total_floors;
            $this->price = $residentialProperty->price;
            $this->description = $residentialProperty->description;
        }
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'dealType' => ['required', 'in:sale,rent'],
                'propertyType' => ['required', 'in:apartment,house,room,studio'],
            ],
            2 => [
                'address' => ['required', 'string', 'min:5', 'max:255'],
                'lat' => ['required', 'numeric', 'between:-90,90'],
                'lng' => ['required', 'numeric', 'between:-180,180'],
            ],
            3 => [
                'area' => ['required', 'integer', 'min:1', 'max:100000'],
                'floor' => ['required', 'integer', 'min:1', 'max:200'],
                'totalFloors' => ['required', 'integer', 'min:1', 'max:200', 'gte:floor'],
                'price' => ['required', 'integer', 'min:1'],
                'description' => ['required', 'string', 'min:10', 'max:5000'],
            ],
            4 => [
                'newPhotos.*' => ['nullable', 'image', 'max:5120'],
            ],
            default => [],
        };
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));

        $this->step = min($this->step + 1, 4);
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

    public function removeNewPhoto(int $index): void
    {
        unset($this->newPhotos[$index]);
        $this->newPhotos = array_values($this->newPhotos);
    }

    public function submit(): void
    {
        $this->validate([
            ...$this->rulesForStep(1),
            ...$this->rulesForStep(2),
            ...$this->rulesForStep(3),
            ...$this->rulesForStep(4),
        ]);

        $attributes = [
            'user_id' => Auth::id(),
            'deal_type' => $this->dealType,
            'property_type' => $this->propertyType,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'area' => $this->area,
            'floor' => $this->floor,
            'total_floors' => $this->totalFloors,
            'price' => $this->price,
            'description' => $this->description,
            // Любое создание/редактирование уходит на повторную модерацию.
            'status' => 'moderation',
            'rejection_reason' => null,
        ];

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
        ]);
    }
}
