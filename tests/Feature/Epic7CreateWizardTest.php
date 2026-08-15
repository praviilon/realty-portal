<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Epic7CreateWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('residential.create'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_listing_through_all_steps(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('area', 45)
            ->set('floor', 3)
            ->set('totalFloors', 9)
            ->set('price', 6500000)
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('residential_properties', [
            'user_id' => $user->id,
            'address' => 'г. Москва, ул. Тестовая, д. 5',
            'price' => 6500000,
            'status' => 'moderation',
        ]);
    }

    public function test_step1_validation_blocks_progress(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('propertyType', 'invalid-type')
            ->call('nextStep')
            ->assertHasErrors(['propertyType'])
            ->assertSet('step', 1);
    }

    public function test_step2_validation_blocks_progress(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->call('nextStep') // step 1 -> 2 (defaults are valid)
            ->set('address', '')
            ->call('nextStep')
            ->assertHasErrors(['address'])
            ->assertSet('step', 2);
    }

    public function test_cannot_skip_ahead_via_go_to_step(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->call('goToStep', 4)
            ->assertSet('step', 1);
    }

    public function test_photo_upload_creates_property_photo_with_main_flag(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('flat.jpg');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
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
            ->set('price', 6500000)
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->set('newPhotos', [$photo])
            ->call('submit');

        $listing = ResidentialProperty::first();
        $this->assertNotNull($listing);
        $this->assertSame(1, $listing->photos()->count());
        $this->assertTrue($listing->photos()->first()->is_main);
        Storage::disk('public')->assertExists($listing->photos()->first()->path);
    }

    public function test_owner_can_edit_own_listing_and_it_returns_to_moderation(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'price' => 1000000,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class, ['residentialProperty' => $listing])
            ->assertSet('address', $listing->address)
            ->set('price', 2000000)
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame(2000000, $listing->price);
        $this->assertSame('moderation', $listing->status);
    }

    public function test_user_cannot_edit_someone_elses_listing(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('residential.edit', $listing))
            ->assertForbidden();
    }
}
