<?php

namespace App\Livewire\CommercialProperty;

use App\Models\CommercialProperty;
use App\Models\CommercialRentDetail;
use App\Models\CommercialSaleDetail;
use App\Models\PropertyPhoto;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Шаговая форма создания/редактирования объявления (коммерческая недвижимость) —
 * эпик 13 дорожной карты (Веха 2). В отличие от жилой недвижимости (эпик 7),
 * у коммерческой набор полей зависит от deal_type (аренда/продажа) — цена и
 * связанные условия хранятся в отдельных 1:1-таблицах commercial_rent_details /
 * commercial_sale_details (см. раздел 3 технического плана).
 *
 * Координаты (lat/lng) вводятся вручную — выбор адреса через геокодер на
 * карте появится в эпике 20 (Веха 2), как и для жилой недвижимости.
 */
#[Layout('layouts.app')]
class CreateWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public ?CommercialProperty $editing = null;

    // Шаг 1
    public string $dealType = 'sale';

    public string $purposeType = 'office';

    public string $buildingType = 'business_center';

    // Шаг 2
    public string $address = '';

    public ?float $lat = null;

    public ?float $lng = null;

    public ?string $metroStation = null;

    public ?int $metroDistanceMin = null;

    // Шаг 3
    public ?int $floor = null;

    /** @var string[] */
    public array $floorFeatures = [];

    public ?int $totalFloors = null;

    public ?int $area = null;

    public ?float $ceilingHeight = null;

    public string $entranceType = 'separate';

    public string $heatingType = 'central';

    public string $finishingType = 'fine';

    public string $furniture = 'none';

    public string $description = '';

    // Шаг 4 — цена и условия (набор полей зависит от dealType)
    public ?int $price = null;

    public ?int $pricePerMonth = null;

    public ?int $deposit = null;

    public ?int $commission = null;

    public bool $utilitiesIncluded = false;

    public string $rentType = 'direct';

    // Шаг 5
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $newPhotos = [];

    public function mount(?CommercialProperty $commercialProperty = null): void
    {
        if ($commercialProperty && $commercialProperty->exists) {
            abort_unless(Auth::id() === $commercialProperty->user_id, 403);

            $this->editing = $commercialProperty;
            $this->dealType = $commercialProperty->deal_type;
            $this->purposeType = $commercialProperty->purpose_type;
            $this->buildingType = $commercialProperty->building_type;
            $this->address = $commercialProperty->address;
            $this->lat = (float) $commercialProperty->lat;
            $this->lng = (float) $commercialProperty->lng;
            $this->metroStation = $commercialProperty->metro_station;
            $this->metroDistanceMin = $commercialProperty->metro_distance_min;
            $this->floor = $commercialProperty->floor;
            // Санитизация устаревших значений (например, удалённого
            // 'separate_entrance') — иначе повторная валидация при
            // сохранении объявления упадёт (см. аналогичный паттерн в
            // App\Livewire\Workspace\CreateWizard::mount()).
            $this->floorFeatures = array_values(array_intersect(
                $commercialProperty->floor_features ?? [],
                array_keys(CommercialProperty::floorFeatureLabels())
            ));
            $this->totalFloors = $commercialProperty->total_floors;
            $this->area = $commercialProperty->area;
            $this->ceilingHeight = $commercialProperty->ceiling_height ? (float) $commercialProperty->ceiling_height : null;
            $this->entranceType = $commercialProperty->entrance_type ?? 'separate';
            $this->heatingType = $commercialProperty->heating_type ?? 'central';
            $this->finishingType = $commercialProperty->finishing_type ?? 'fine';
            $this->furniture = $commercialProperty->furniture ?? 'none';
            $this->description = $commercialProperty->description;

            if ($commercialProperty->deal_type === 'rent' && $commercialProperty->rentDetail) {
                $this->pricePerMonth = $commercialProperty->rentDetail->price_per_month;
                $this->deposit = $commercialProperty->rentDetail->deposit;
                $this->commission = $commercialProperty->rentDetail->commission;
                $this->utilitiesIncluded = $commercialProperty->rentDetail->utilities_included;
                $this->rentType = $commercialProperty->rentDetail->rent_type;
            } elseif ($commercialProperty->deal_type === 'sale' && $commercialProperty->saleDetail) {
                $this->price = $commercialProperty->saleDetail->price;
                $this->commission = $commercialProperty->saleDetail->commission;
            }
        }
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'dealType' => ['required', 'in:sale,rent'],
                'purposeType' => ['required', 'in:office,retail,warehouse,free'],
                'buildingType' => ['required', 'in:administrative,business_center,residential,shopping_center'],
            ],
            2 => [
                'address' => ['required', 'string', 'min:5', 'max:255'],
                'lat' => ['required', 'numeric', 'between:-90,90'],
                'lng' => ['required', 'numeric', 'between:-180,180'],
                'metroStation' => ['nullable', 'string', 'max:255'],
                'metroDistanceMin' => ['nullable', 'integer', 'min:0', 'max:180'],
            ],
            3 => [
                'floor' => ['required', 'integer', 'min:1', 'max:200'],
                'floorFeatures' => ['array'],
                'floorFeatures.*' => ['string', 'in:shop_window,high_traffic,parking,security'],
                'totalFloors' => ['required', 'integer', 'min:1', 'max:200', 'gte:floor'],
                'area' => ['required', 'integer', 'min:1', 'max:100000'],
                'ceilingHeight' => ['nullable', 'numeric', 'min:2', 'max:20'],
                'entranceType' => ['nullable', 'in:separate,common'],
                'heatingType' => ['nullable', 'in:central,autonomous,none'],
                'finishingType' => ['nullable', 'in:none,rough,fine'],
                'furniture' => ['nullable', 'in:none,partial,full'],
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
                'newPhotos.*' => ['nullable', 'image', 'max:5120'],
            ],
            default => [],
        };
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
            ...$this->rulesForStep(5),
        ]);

        $attributes = [
            'user_id' => Auth::id(),
            'deal_type' => $this->dealType,
            'purpose_type' => $this->purposeType,
            'building_type' => $this->buildingType,
            'entrance_type' => $this->entranceType,
            'floor' => $this->floor,
            'floor_features' => $this->floorFeatures,
            'total_floors' => $this->totalFloors,
            'area' => $this->area,
            'ceiling_height' => $this->ceilingHeight,
            'heating_type' => $this->heatingType,
            'finishing_type' => $this->finishingType,
            'furniture' => $this->furniture,
            'address' => $this->address,
            'metro_station' => $this->metroStation ?: null,
            'metro_distance_min' => $this->metroDistanceMin,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'description' => $this->description,
            // Любое создание/редактирование уходит на повторную модерацию.
            'status' => 'moderation',
            'rejection_reason' => null,
        ];

        if ($this->editing) {
            $this->editing->update($attributes);
            $listing = $this->editing;
        } else {
            $listing = CommercialProperty::create($attributes);
        }

        if ($this->dealType === 'rent') {
            $listing->saleDetail()->delete();
            CommercialRentDetail::updateOrCreate(
                ['property_id' => $listing->id],
                [
                    'price_per_month' => $this->pricePerMonth,
                    'deposit' => $this->deposit,
                    'commission' => $this->commission,
                    'utilities_included' => $this->utilitiesIncluded,
                    'rent_type' => $this->rentType,
                ]
            );
        } else {
            $listing->rentDetail()->delete();
            CommercialSaleDetail::updateOrCreate(
                ['property_id' => $listing->id],
                [
                    'price' => $this->price,
                    'commission' => $this->commission,
                ]
            );
        }

        $nextSortOrder = $listing->photos()->max('sort_order') + 1;

        foreach ($this->newPhotos as $index => $photo) {
            $path = $photo->store('property-photos/commercial-' . $listing->id, 'public');

            PropertyPhoto::create([
                'photoable_type' => CommercialProperty::class,
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
        return view('livewire.commercial-property.create-wizard', [
            'dealTypeLabels' => CommercialProperty::dealTypeLabels(),
            'purposeTypeLabels' => CommercialProperty::purposeTypeLabels(),
            'buildingTypeLabels' => CommercialProperty::buildingTypeLabels(),
            'entranceTypeLabels' => CommercialProperty::entranceTypeLabels(),
            'heatingTypeLabels' => CommercialProperty::heatingTypeLabels(),
            'finishingTypeLabels' => CommercialProperty::finishingTypeLabels(),
            'furnitureLabels' => CommercialProperty::furnitureLabels(),
            'floorFeatureLabels' => CommercialProperty::floorFeatureLabels(),
            'rentTypeLabels' => CommercialProperty::rentTypeLabels(),
        ]);
    }
}
