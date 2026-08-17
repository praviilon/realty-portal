<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Личный кабинет') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Мои объявления</h3>
                    <a href="{{ route('residential.create') }}" wire:navigate>
                        <x-primary-button type="button">Разместить объявление</x-primary-button>
                    </a>
                </div>

                @php($listings = auth()->user()->residentialProperties()->latest()->get())

                @if ($listings->isEmpty())
                    <p class="text-gray-500 text-sm">У вас пока нет объявлений.</p>
                @else
                    <div class="divide-y">
                        @foreach ($listings as $listing)
                            <div class="py-4 flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-medium">{{ number_format($listing->price, 0, '', ' ') }} ₽ · {{ $listing->address }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ $listing->property_type_label }} · {{ $listing->deal_type_label }}
                                        @if ($listing->status === 'rejected' && $listing->rejection_reason)
                                            · <span class="text-red-600">Причина отклонения: {{ $listing->rejection_reason }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span @class([
                                        'text-xs font-medium px-2 py-1 rounded-full',
                                        'bg-green-100 text-green-800' => $listing->status === 'active',
                                        'bg-yellow-100 text-yellow-800' => $listing->status === 'moderation',
                                        'bg-red-100 text-red-800' => $listing->status === 'rejected',
                                        'bg-gray-100 text-gray-600' => $listing->status === 'archived',
                                    ])>
                                        {{ [
                                            'moderation' => 'На модерации',
                                            'active' => 'Активно',
                                            'rejected' => 'Отклонено',
                                            'archived' => 'В архиве',
                                        ][$listing->status] ?? $listing->status }}
                                    </span>
                                    <a href="{{ route('residential.edit', $listing) }}" wire:navigate class="text-sm text-primary-600 hover:underline">Изменить</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Мои объявления (коммерческая недвижимость)</h3>
                    <a href="{{ route('commercial.create') }}" wire:navigate>
                        <x-primary-button type="button">Разместить объявление</x-primary-button>
                    </a>
                </div>

                @php($commercialListings = auth()->user()->commercialProperties()->latest()->get())

                @if ($commercialListings->isEmpty())
                    <p class="text-gray-500 text-sm">У вас пока нет коммерческих объявлений.</p>
                @else
                    <div class="divide-y">
                        @foreach ($commercialListings as $listing)
                            <div class="py-4 flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-medium">{{ number_format($listing->display_price ?? 0, 0, '', ' ') }} ₽ · {{ $listing->address }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ $listing->purpose_type_label }} · {{ $listing->deal_type_label }}
                                        @if ($listing->status === 'rejected' && $listing->rejection_reason)
                                            · <span class="text-red-600">Причина отклонения: {{ $listing->rejection_reason }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span @class([
                                        'text-xs font-medium px-2 py-1 rounded-full',
                                        'bg-green-100 text-green-800' => $listing->status === 'active',
                                        'bg-yellow-100 text-yellow-800' => $listing->status === 'moderation',
                                        'bg-red-100 text-red-800' => $listing->status === 'rejected',
                                        'bg-gray-100 text-gray-600' => $listing->status === 'archived',
                                    ])>
                                        {{ [
                                            'moderation' => 'На модерации',
                                            'active' => 'Активно',
                                            'rejected' => 'Отклонено',
                                            'archived' => 'В архиве',
                                        ][$listing->status] ?? $listing->status }}
                                    </span>
                                    <a href="{{ route('commercial.edit', $listing) }}" wire:navigate class="text-sm text-primary-600 hover:underline">Изменить</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Мои объявления (рабочие пространства)</h3>
                    <a href="{{ route('workspace.create') }}" wire:navigate>
                        <x-primary-button type="button">Разместить объявление</x-primary-button>
                    </a>
                </div>

                @php($workspaceListings = auth()->user()->workspaces()->latest()->get())

                @if ($workspaceListings->isEmpty())
                    <p class="text-gray-500 text-sm">У вас пока нет объявлений о рабочих пространствах.</p>
                @else
                    <div class="divide-y">
                        @foreach ($workspaceListings as $listing)
                            <div class="py-4 flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-medium">
                                        {{ $listing->display_price ? number_format($listing->display_price, 0, '', ' ') . ' ₽/' . $listing->cheapestPricing->period_label : 'Цена не указана' }}
                                        · {{ $listing->address }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $listing->workspace_type_label }}
                                        @if ($listing->status === 'rejected' && $listing->rejection_reason)
                                            · <span class="text-red-600">Причина отклонения: {{ $listing->rejection_reason }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span @class([
                                        'text-xs font-medium px-2 py-1 rounded-full',
                                        'bg-green-100 text-green-800' => $listing->status === 'active',
                                        'bg-yellow-100 text-yellow-800' => $listing->status === 'moderation',
                                        'bg-red-100 text-red-800' => $listing->status === 'rejected',
                                        'bg-gray-100 text-gray-600' => $listing->status === 'archived',
                                    ])>
                                        {{ [
                                            'moderation' => 'На модерации',
                                            'active' => 'Активно',
                                            'rejected' => 'Отклонено',
                                            'archived' => 'В архиве',
                                        ][$listing->status] ?? $listing->status }}
                                    </span>
                                    <a href="{{ route('workspace.edit', $listing) }}" wire:navigate class="text-sm text-primary-600 hover:underline">Изменить</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
