<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Epic9ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_avatar_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertStatus(200)
            ->assertSee('Аватар');
    }

    public function test_avatar_upload_resizes_to_256_square_webp(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('photo.jpg', 800, 600);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Profile\AvatarUpload::class)
            ->set('avatar', $image)
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertStringEndsWith('.webp', $user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $saved = $manager->decodePath(Storage::disk('public')->path($user->avatar_path));
        $this->assertSame(256, $saved->width());
        $this->assertSame(256, $saved->height());
    }

    public function test_uploading_new_avatar_deletes_old_one(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Profile\AvatarUpload::class)
            ->set('avatar', UploadedFile::fake()->image('first.jpg'));

        $firstPath = $user->refresh()->avatar_path;
        Storage::disk('public')->assertExists($firstPath);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Profile\AvatarUpload::class)
            ->set('avatar', UploadedFile::fake()->image('second.jpg'));

        $secondPath = $user->refresh()->avatar_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_remove_avatar_deletes_file_and_clears_path(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Profile\AvatarUpload::class)
            ->set('avatar', UploadedFile::fake()->image('photo.jpg'));

        $path = $user->refresh()->avatar_path;

        Livewire::actingAs($user)
            ->test(\App\Livewire\Profile\AvatarUpload::class)
            ->call('removeAvatar');

        $this->assertNull($user->refresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Profile\AvatarUpload::class)
            ->set('avatar', UploadedFile::fake()->create('document.pdf', 100))
            ->assertHasErrors(['avatar']);

        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('first_name', 'мария')
            ->set('last_name', 'кузнецова')
            ->set('email', 'maria@example.com')
            ->set('phone', '+7 (902) 111-22-33')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Мария', $user->first_name);
        $this->assertSame('Кузнецова', $user->last_name);
        $this->assertSame('maria@example.com', $user->email);
        $this->assertSame('+7 (902) 111-22-33', $user->phone);
    }
}
