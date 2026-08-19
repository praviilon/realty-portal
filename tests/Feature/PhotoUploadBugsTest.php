<?php

namespace Tests\Feature;

use App\Livewire\CommercialProperty\CreateWizard as CommercialPropertyWizard;
use App\Livewire\Property\CreateWizard as ResidentialPropertyWizard;
use App\Livewire\Workspace\CreateWizard as WorkspaceWizard;
use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Исправление багов загрузки фото на шаге 5 мастеров создания/редактирования
 * объявлений (по просьбе пользователя):
 * 1) повторный выбор файлов стирал уже выбранные/загруженные фото;
 * 2) не было ограничения на количество фото за раз (503 при слишком
 *    большом выборе) — теперь лимит 5 фото на объявление;
 * 3) у уже загруженных (при редактировании) фото не было возможности
 *    удаления;
 * 4) файлы не удалялись из storage ни при удалении нового (ещё не
 *    отправленного) фото крестиком, ни при удалении уже сохранённого —
 *    см. также tests/Feature/DataCleanupTest.php для сценариев с удалением
 *    объявления/пользователя целиком.
 */
class PhotoUploadBugsTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_photos_twice_accumulates_instead_of_replacing(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(ResidentialPropertyWizard::class);

        $component->set('incomingPhotos', [UploadedFile::fake()->image('a.jpg')]);
        $component->assertCount('newPhotos', 1);

        // Повторный выбор — раньше это полностью стирало $newPhotos.
        $component->set('incomingPhotos', [UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')]);
        $component->assertCount('newPhotos', 3);
    }

    public function test_accumulation_fix_applies_to_commercial_wizard_too(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(CommercialPropertyWizard::class);
        $component->set('incomingPhotos', [UploadedFile::fake()->image('a.jpg')]);
        $component->set('incomingPhotos', [UploadedFile::fake()->image('b.jpg')]);

        $component->assertCount('newPhotos', 2);
    }

    public function test_accumulation_fix_applies_to_workspace_wizard_too(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(WorkspaceWizard::class);
        $component->set('incomingPhotos', [UploadedFile::fake()->image('a.jpg')]);
        $component->set('incomingPhotos', [UploadedFile::fake()->image('b.jpg')]);

        $component->assertCount('newPhotos', 2);
    }

    public function test_selecting_more_than_five_photos_at_once_is_capped(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $sixPhotos = collect(range(1, 6))->map(fn ($i) => UploadedFile::fake()->image("photo{$i}.jpg"))->all();

        $component = Livewire::actingAs($user)->test(ResidentialPropertyWizard::class);
        $component->set('incomingPhotos', $sixPhotos);

        $component->assertCount('newPhotos', 5);
        $component->assertHasErrors(['incomingPhotos']);
    }

    public function test_accumulating_beyond_five_across_multiple_picks_is_capped(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(ResidentialPropertyWizard::class);
        $component->set('incomingPhotos', collect(range(1, 3))->map(fn ($i) => UploadedFile::fake()->image("a{$i}.jpg"))->all());
        $component->assertCount('newPhotos', 3);

        // Ещё 3 сверху — влезет только 2 (до лимита 5).
        $component->set('incomingPhotos', collect(range(1, 3))->map(fn ($i) => UploadedFile::fake()->image("b{$i}.jpg"))->all());
        $component->assertCount('newPhotos', 5);
        $component->assertHasErrors(['incomingPhotos']);
    }

    public function test_submit_rejects_more_than_five_total_photos_as_defense_in_depth(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // Напрямую через set() — обходит updatedIncomingPhotos(), проверяем
        // именно серверную защиту в rulesForStep(5).
        $sixPhotos = collect(range(1, 6))->map(fn ($i) => UploadedFile::fake()->image("photo{$i}.jpg"))->all();

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->set('area', 45)
            ->set('floor', 3)
            ->set('totalFloors', 9)
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->set('price', 6500000)
            ->call('nextStep')
            ->set('newPhotos', $sixPhotos)
            ->call('submit')
            ->assertHasErrors(['newPhotos']);

        $this->assertNull(ResidentialProperty::first());
    }

    public function test_removing_new_photo_deletes_its_temporary_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(ResidentialPropertyWizard::class);
        $component->set('incomingPhotos', [UploadedFile::fake()->image('a.jpg')]);

        $temp = $component->get('newPhotos')[0];
        $absolutePath = $temp->getRealPath() ?: $temp->getPathname();
        $this->assertFileExists($absolutePath);

        $component->call('removeNewPhoto', 0);

        $component->assertCount('newPhotos', 0);
        $this->assertFileDoesNotExist($absolutePath);
    }

    public function test_editing_listing_shows_delete_button_on_existing_photos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id]);
        $photo = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => 'property-photos/' . $listing->id . '/existing.jpg',
        ]);
        Storage::disk('public')->put($photo->path, 'fake-image-content');

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class, ['residentialProperty' => $listing])
            ->set('step', 5)
            ->assertSee('removeExistingPhoto(' . $photo->id . ')', false);
    }

    public function test_owner_can_delete_existing_photo_while_editing(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id]);
        $photo = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => 'property-photos/' . $listing->id . '/existing.jpg',
        ]);
        Storage::disk('public')->put($photo->path, 'fake-image-content');
        Storage::disk('public')->assertExists($photo->path);

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class, ['residentialProperty' => $listing])
            ->call('removeExistingPhoto', $photo->id);

        $this->assertNull(PropertyPhoto::find($photo->id));
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_removing_existing_photo_frees_up_a_slot_for_new_ones(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id]);

        // 5 уже загруженных фото — лимит исчерпан.
        for ($i = 0; $i < 5; $i++) {
            $photo = PropertyPhoto::factory()->create([
                'photoable_type' => ResidentialProperty::class,
                'photoable_id' => $listing->id,
                'path' => "property-photos/{$listing->id}/existing-{$i}.jpg",
            ]);
            Storage::disk('public')->put($photo->path, 'fake-image-content');
        }

        // photoSlotsRemaining — не публичное свойство компонента, а
        // значение, которое render() передаёт во view, поэтому проверяем
        // его через assertViewHas(), а не assertSet().
        $component = Livewire::actingAs($user)->test(ResidentialPropertyWizard::class, ['residentialProperty' => $listing]);
        $component->assertViewHas('photoSlotsRemaining', 0);

        $component->set('incomingPhotos', [UploadedFile::fake()->image('new.jpg')]);
        $component->assertCount('newPhotos', 0); // некуда — лимит исчерпан

        $firstPhotoId = $listing->photos()->first()->id;
        $component->call('removeExistingPhoto', $firstPhotoId);
        $component->assertViewHas('photoSlotsRemaining', 1);

        $component->set('incomingPhotos', [UploadedFile::fake()->image('new2.jpg')]);
        $component->assertCount('newPhotos', 1);
    }

    public function test_user_cannot_remove_existing_photo_of_someone_elses_listing(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $owner->id]);
        $photo = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => "property-photos/{$listing->id}/existing.jpg",
        ]);
        Storage::disk('public')->put($photo->path, 'fake-image-content');

        // Мастер редактирования для чужого объявления сам по себе уже
        // недоступен (403 — существующая логика mount()), так что "чужого"
        // удаления в принципе быть не может — регресс-тест на эту защиту.
        $this->actingAs($other)
            ->get(route('residential.edit', $listing))
            ->assertForbidden();

        $this->assertNotNull(PropertyPhoto::find($photo->id));
    }

    /**
     * Полный сквозной сценарий редактирования: удалили старое фото,
     * добавили новое, сохранили — на странице объекта должно остаться
     * только актуальное фото.
     */
    public function test_full_edit_flow_replaces_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $oldPhoto = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => "property-photos/{$listing->id}/old.jpg",
        ]);
        Storage::disk('public')->put($oldPhoto->path, 'fake-image-content');

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class, ['residentialProperty' => $listing])
            ->call('removeExistingPhoto', $oldPhoto->id)
            ->set('incomingPhotos', [UploadedFile::fake()->image('new.jpg')])
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame(1, $listing->photos()->count());
        Storage::disk('public')->assertMissing($oldPhoto->path);
        $this->assertTrue($listing->photos()->first()->is_main);
    }
}
