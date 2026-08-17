<?php

namespace Tests\Feature;

use App\Livewire\Favorites\Button;
use App\Livewire\Favorites\Index as FavoritesIndex;
use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\Favorite;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic17FavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_add_listing_to_favorites(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Button::class, ['favoritable' => $listing])
            ->assertSet('isFavorited', false)
            ->call('toggle')
            ->assertSet('isFavorited', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => ResidentialProperty::class,
            'favoritable_id' => $listing->id,
        ]);
    }

    public function test_toggling_again_removes_the_favorite(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)->test(Button::class, ['favoritable' => $listing]);

        $component->call('toggle')->assertSet('isFavorited', true);
        $this->assertSame(1, Favorite::query()->count());

        $component->call('toggle')->assertSet('isFavorited', false);
        $this->assertSame(0, Favorite::query()->count());
    }

    public function test_guest_clicking_toggle_is_redirected_to_login_without_creating_a_favorite(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        Livewire::test(Button::class, ['favoritable' => $listing])
            ->call('toggle')
            ->assertRedirect(route('login'));

        $this->assertSame(0, Favorite::query()->count());
    }

    public function test_button_mounted_with_existing_favorite_shows_as_favorited(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => ResidentialProperty::class,
            'favoritable_id' => $listing->id,
        ]);

        Livewire::actingAs($user)
            ->test(Button::class, ['favoritable' => $listing])
            ->assertSet('isFavorited', true);
    }

    public function test_favorites_page_lists_only_current_users_favorites(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Моя, 1']);
        $theirs = ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Чужая, 2']);

        Favorite::factory()->create(['user_id' => $user->id, 'favoritable_type' => ResidentialProperty::class, 'favoritable_id' => $mine->id]);
        Favorite::factory()->create(['user_id' => $other->id, 'favoritable_type' => ResidentialProperty::class, 'favoritable_id' => $theirs->id]);

        Livewire::actingAs($user)
            ->test(FavoritesIndex::class)
            ->assertSee('ул. Моя, 1')
            ->assertDontSee('ул. Чужая, 2');
    }

    public function test_favorites_page_tabs_filter_by_listing_type(): void
    {
        $user = User::factory()->create();

        $residential = ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Жилая, 5']);
        $commercial = CommercialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale', 'address' => 'ул. Офисная, 9']);
        CommercialSaleDetail::factory()->create(['property_id' => $commercial->id]);

        Favorite::factory()->create(['user_id' => $user->id, 'favoritable_type' => ResidentialProperty::class, 'favoritable_id' => $residential->id]);
        Favorite::factory()->create(['user_id' => $user->id, 'favoritable_type' => CommercialProperty::class, 'favoritable_id' => $commercial->id]);

        Livewire::actingAs($user)
            ->test(FavoritesIndex::class)
            ->assertSet('tab', 'residential')
            ->assertSee('ул. Жилая, 5')
            ->assertDontSee('ул. Офисная, 9')
            ->call('setTab', 'commercial')
            ->assertSee('ул. Офисная, 9')
            ->assertDontSee('ул. Жилая, 5');
    }

    public function test_user_can_remove_favorite_from_favorites_page(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);
        $favorite = Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => ResidentialProperty::class,
            'favoritable_id' => $listing->id,
        ]);

        Livewire::actingAs($user)
            ->test(FavoritesIndex::class)
            ->call('removeFavorite', $favorite->id)
            ->assertDontSee($listing->address);

        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
    }

    public function test_user_cannot_remove_another_users_favorite(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);
        $favorite = Favorite::factory()->create([
            'user_id' => $owner->id,
            'favoritable_type' => ResidentialProperty::class,
            'favoritable_id' => $listing->id,
        ]);

        Livewire::actingAs($intruder)
            ->test(FavoritesIndex::class)
            ->call('removeFavorite', $favorite->id);

        $this->assertDatabaseHas('favorites', ['id' => $favorite->id]);
    }

    public function test_favorite_button_is_visible_on_catalog_and_detail_pages(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        $this->actingAs($user)->get(route('residential.search'))->assertOk()->assertSeeLivewire('favorites.button');
        $this->actingAs($user)->get(route('residential.show', $listing))->assertOk()->assertSeeLivewire('favorites.button');
    }

    public function test_favorites_page_is_reachable_via_real_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/account/favorites')
            ->assertOk();
    }
}
