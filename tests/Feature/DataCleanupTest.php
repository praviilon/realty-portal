<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupOrphanedData;
use App\Filament\Pages\DataCleanup;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\CommercialProperty;
use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use App\Models\User;
use App\Notifications\ListingStatusChanged;
use App\Services\StorageCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * По просьбе пользователя: "удаление пользователя не подчищает фото
 * объявлений и уведомления — это надо доработать, чтобы удаление реально
 * удаляло фото и уведомления", плюс "механизм очистки" для того, что уже
 * накопилось раньше. См. App\Models\PropertyPhoto::booted(),
 * App\Models\User::deleteAccount() и App\Services\StorageCleanupService.
 */
class DataCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_photo_model_removes_its_storage_file(): void
    {
        Storage::fake('public');
        $listing = ResidentialProperty::factory()->create();
        $photo = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => "property-photos/{$listing->id}/a.jpg",
        ]);
        Storage::disk('public')->put($photo->path, 'content');
        Storage::disk('public')->assertExists($photo->path);

        $photo->delete();

        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_user_delete_account_removes_photos_and_notifications_and_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $residential = ResidentialProperty::factory()->create(['user_id' => $user->id]);
        $residentialPhoto = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $residential->id,
            'path' => "property-photos/{$residential->id}/a.jpg",
        ]);
        Storage::disk('public')->put($residentialPhoto->path, 'content');

        $commercial = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active', 'deal_type' => 'sale']);
        \App\Models\CommercialSaleDetail::factory()->create(['property_id' => $commercial->id]);
        $commercialPhoto = PropertyPhoto::factory()->create([
            'photoable_type' => CommercialProperty::class,
            'photoable_id' => $commercial->id,
            'path' => "property-photos/commercial-{$commercial->id}/a.jpg",
        ]);
        Storage::disk('public')->put($commercialPhoto->path, 'content');

        $user->notify(new ListingStatusChanged($residential));
        $this->assertSame(1, $user->notifications()->count());

        $user->update(['avatar_path' => 'avatars/' . $user->id . '-test.webp']);
        Storage::disk('public')->put($user->avatar_path, 'avatar-content');

        $user->deleteAccount();

        $this->assertNull(User::find($user->id));
        $this->assertNull(ResidentialProperty::find($residential->id));
        $this->assertNull(CommercialProperty::find($commercial->id));
        $this->assertNull(PropertyPhoto::find($residentialPhoto->id));
        $this->assertNull(PropertyPhoto::find($commercialPhoto->id));
        Storage::disk('public')->assertMissing($residentialPhoto->path);
        Storage::disk('public')->assertMissing($commercialPhoto->path);
        Storage::disk('public')->assertMissing('avatars/' . $user->id . '-test.webp');
        $this->assertSame(0, \Illuminate\Notifications\DatabaseNotification::where('notifiable_id', $user->id)->count());
    }

    public function test_self_service_account_deletion_cleans_up_photos_and_notifications(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['password' => 'password']);
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id]);
        $photo = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => "property-photos/{$listing->id}/a.jpg",
        ]);
        Storage::disk('public')->put($photo->path, 'content');
        $user->notify(new ListingStatusChanged($listing));

        $this->actingAs($user);
        \Livewire\Volt\Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $this->assertNull(User::find($user->id));
        $this->assertNull(PropertyPhoto::find($photo->id));
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_admin_delete_user_action_cleans_up_photos_and_notifications(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id]);
        $photo = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => "property-photos/{$listing->id}/a.jpg",
        ]);
        Storage::disk('public')->put($photo->path, 'content');
        $user->notify(new ListingStatusChanged($listing));

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('deleteUser', $user);

        $this->assertNull(User::find($user->id));
        $this->assertNull(PropertyPhoto::find($photo->id));
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_deleting_one_user_does_not_touch_another_users_photos_or_notifications(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $untouched = User::factory()->create();
        $untouchedListing = ResidentialProperty::factory()->create(['user_id' => $untouched->id]);
        $untouchedPhoto = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $untouchedListing->id,
            'path' => "property-photos/{$untouchedListing->id}/a.jpg",
        ]);
        Storage::disk('public')->put($untouchedPhoto->path, 'content');
        $untouched->notify(new ListingStatusChanged($untouchedListing));

        $user->deleteAccount();

        $this->assertNotNull(User::find($untouched->id));
        $this->assertNotNull(PropertyPhoto::find($untouchedPhoto->id));
        Storage::disk('public')->assertExists($untouchedPhoto->path);
        $this->assertSame(1, $untouched->notifications()->count());
    }

    // ---------- StorageCleanupService ----------

    public function test_cleanup_service_removes_orphaned_photo_records_and_files(): void
    {
        Storage::fake('public');

        // Осиротевшее фото: photoable_id не соответствует ни одному
        // существующему объявлению (например, объявление удалили каскадом
        // до фикса, а запись о фото осталась).
        $orphan = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => 999999,
            'path' => 'property-photos/999999/orphan.jpg',
        ]);
        Storage::disk('public')->put($orphan->path, 'content');

        $listing = ResidentialProperty::factory()->create();
        $valid = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => $listing->id,
            'path' => "property-photos/{$listing->id}/valid.jpg",
        ]);
        Storage::disk('public')->put($valid->path, 'content');

        $result = app(StorageCleanupService::class)->run();

        $this->assertSame(1, $result['orphaned_photos']);
        $this->assertNull(PropertyPhoto::find($orphan->id));
        Storage::disk('public')->assertMissing($orphan->path);
        $this->assertNotNull(PropertyPhoto::find($valid->id));
        Storage::disk('public')->assertExists($valid->path);
    }

    public function test_cleanup_service_removes_files_without_any_db_record(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('property-photos/orphan-file-no-record.jpg', 'content');

        $result = app(StorageCleanupService::class)->run();

        $this->assertSame(1, $result['orphaned_files']);
        Storage::disk('public')->assertMissing('property-photos/orphan-file-no-record.jpg');
    }

    public function test_cleanup_service_removes_orphaned_notifications(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id]);
        $user->notify(new ListingStatusChanged($listing));
        $notificationId = $user->notifications()->first()->id;

        // Пользователь удалён напрямую (мимо deleteAccount()) — симулирует
        // "исторические" осиротевшие уведомления, накопившиеся до фикса.
        $user->delete();

        $result = app(StorageCleanupService::class)->run();

        $this->assertSame(1, $result['orphaned_notifications']);
        $this->assertNull(\Illuminate\Notifications\DatabaseNotification::find($notificationId));
    }

    public function test_cleanup_service_reports_freed_bytes(): void
    {
        Storage::fake('public');
        $orphan = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => 999999,
            'path' => 'property-photos/999999/orphan.jpg',
        ]);
        Storage::disk('public')->put($orphan->path, str_repeat('x', 2048));

        $result = app(StorageCleanupService::class)->run();

        $this->assertGreaterThanOrEqual(2048, $result['freed_bytes']);
        $this->assertNotEmpty($result['freed_human']);
    }

    public function test_cleanup_artisan_command_runs_successfully(): void
    {
        Storage::fake('public');
        $orphan = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => 999999,
            'path' => 'property-photos/999999/orphan.jpg',
        ]);
        Storage::disk('public')->put($orphan->path, 'content');

        $this->artisan(CleanupOrphanedData::class)
            ->expectsOutputToContain('Осиротевших фото')
            ->assertSuccessful();

        $this->assertNull(PropertyPhoto::find($orphan->id));
    }

    // ---------- Админ-страница очистки ----------

    public function test_admin_can_access_data_cleanup_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(DataCleanup::class)
            ->assertOk()
            ->assertSee('Запустить очистку');
    }

    public function test_regular_user_cannot_reach_data_cleanup_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/data-cleanup')->assertForbidden();
    }

    public function test_admin_can_run_cleanup_from_the_page_and_see_results(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $orphan = PropertyPhoto::factory()->create([
            'photoable_type' => ResidentialProperty::class,
            'photoable_id' => 999999,
            'path' => 'property-photos/999999/orphan.jpg',
        ]);
        Storage::disk('public')->put($orphan->path, 'content');

        Livewire::actingAs($admin)
            ->test(DataCleanup::class)
            ->call('runCleanup')
            ->assertSet('lastResult.orphaned_photos', 1);

        $this->assertNull(PropertyPhoto::find($orphan->id));
    }
}
