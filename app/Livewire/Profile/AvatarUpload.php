<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Аватар — эпик 9 дорожной карты. Раздел 1 технического плана: серверный
 * ресайз в квадрат 256×256 WebP через Intervention Image (сознательное
 * отступление от клиентского Canvas-кроппера из исходного SRS, см. раздел 5).
 */
class AvatarUpload extends Component
{
    use WithFileUploads;

    public $avatar = null;

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['image', 'max:5120'],
        ]);

        $manager = new ImageManager(new Driver());
        $image = $manager->decodePath($this->avatar->getRealPath());
        $image->cover(256, 256);
        $encoded = $image->encode(new WebpEncoder(quality: 82));

        $user = Auth::user();
        $oldPath = $user->avatar_path;
        $path = 'avatars/' . $user->id . '-' . uniqid() . '.webp';

        Storage::disk('public')->put($path, (string) $encoded);

        $user->update(['avatar_path' => $path]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->reset('avatar');
        $this->dispatch('avatar-updated');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->dispatch('avatar-updated');
    }

    public function render()
    {
        return view('livewire.profile.avatar-upload');
    }
}
