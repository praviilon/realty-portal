<?php

use App\Rules\PasswordPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     *
     * Эпик 22 (Веха 2): смена пароля в ЛК — та же политика пароля
     * (App\Rules\PasswordPolicy), что и при регистрации (эпик 2),
     * а не встроенный Password::defaults(), чтобы правила совпадали.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', new PasswordPolicy(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Смена пароля') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Используйте длинный, уникальный пароль, чтобы аккаунт оставался защищённым.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div>
            <x-input-label for="update_password_current_password" :value="__('Текущий пароль')" />
            <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('Новый пароль')" />
            <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">{{ __('От 6 до 60 символов латиницей: заглавные и строчные буквы, а также цифры.') }}</p>
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Повторите новый пароль')" />
            <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Сохранить') }}</x-primary-button>

            <x-action-message class="me-3" on="password-updated">
                {{ __('Сохранено.') }}
            </x-action-message>
        </div>
    </form>
</section>
