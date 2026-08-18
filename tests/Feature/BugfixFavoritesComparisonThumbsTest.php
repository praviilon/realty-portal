<?php

namespace Tests\Feature;

use App\Models\ComparisonItem;
use App\Models\ComparisonList;
use App\Models\Favorite;
use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Доработки после Вехи 3, п.2 и п.3: мини-фото объявлений в разделах
 * «Избранное» и «Сравнение» — по аналогии с главной и каталогами
 * (см. компонент x-listing-thumb и связь mainPhoto из BugfixHomeGroupsThumbsTest).
 */
class BugfixFavoritesComparisonThumbsTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorites_card_shows_thumbnail_when_photo_exists(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => ResidentialProperty::class,
            'path' => 'property-photos/favorites-thumb-test.webp',
            'is_main' => true,
        ]);
        Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => ResidentialProperty::class,
            'favoritable_id' => $listing->id,
            'added_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test('favorites.index')
            ->assertSee('property-photos/favorites-thumb-test.webp', false);
    }

    public function test_favorites_card_has_no_photo_slot_when_listing_has_no_photo(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Избранное Без Фото, 1']);
        Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => ResidentialProperty::class,
            'favoritable_id' => $listing->id,
            'added_at' => now(),
        ]);

        $component = Livewire::actingAs($user)->test('favorites.index');

        $component->assertSee('ул. Избранное Без Фото, 1');
        $this->assertStringNotContainsString('property-photos/', $component->html());
    }

    public function test_comparison_table_shows_thumbnail_when_photo_exists(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => ResidentialProperty::class,
            'path' => 'property-photos/comparison-thumb-test.webp',
            'is_main' => true,
        ]);

        $list = ComparisonList::create(['user_id' => $user->id, 'list_type' => 'residential_sale']);
        ComparisonItem::create([
            'comparison_list_id' => $list->id,
            'comparable_type' => ResidentialProperty::class,
            'comparable_id' => $listing->id,
            'added_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test('comparison.index')
            ->assertSee('property-photos/comparison-thumb-test.webp', false)
            ->assertSee('Фото');
    }
}
