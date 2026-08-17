<?php

namespace Tests\Feature;

use App\Livewire\Comparison\Button as ComparisonButton;
use App\Livewire\Comparison\Index as ComparisonIndex;
use App\Livewire\Favorites\Button as FavoritesButton;
use App\Livewire\Favorites\Index as FavoritesIndex;
use App\Models\ComparisonItem;
use App\Models\ComparisonList;
use App\Models\Favorite;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Эпик 28 (Веха 3) — расширение избранного и сравнения на рабочие
 * пространства через уже существующие полиморфные связи favoritable/comparable
 * (эпики 17-18, Веха 2). Проверяем именно расширение, а не заново весь
 * функционал избранного/сравнения — тот уже покрыт Epic17/Epic18 тестами.
 */
class Epic28WorkspaceFavoritesComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_favorite_a_workspace(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(FavoritesButton::class, ['favoritable' => $listing])
            ->assertSet('isFavorited', false)
            ->call('toggle')
            ->assertSet('isFavorited', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Workspace::class,
            'favoritable_id' => $listing->id,
        ]);
    }

    public function test_favorites_page_has_a_workspace_tab(): void
    {
        $user = User::factory()->create();

        $residential = \App\Models\ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Жилая, 5']);
        $workspace = Workspace::factory()->create(['status' => 'active', 'address' => 'ул. Коворкинг, 8']);

        Favorite::factory()->create(['user_id' => $user->id, 'favoritable_type' => \App\Models\ResidentialProperty::class, 'favoritable_id' => $residential->id]);
        Favorite::factory()->create(['user_id' => $user->id, 'favoritable_type' => Workspace::class, 'favoritable_id' => $workspace->id]);

        Livewire::actingAs($user)
            ->test(FavoritesIndex::class)
            ->assertSee('ул. Жилая, 5')
            ->assertDontSee('ул. Коворкинг, 8')
            ->call('setTab', 'workspace')
            ->assertSee('ул. Коворкинг, 8')
            ->assertDontSee('ул. Жилая, 5');
    }

    public function test_authenticated_user_can_add_workspace_to_comparison(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(ComparisonButton::class, ['comparable' => $listing])
            ->assertSet('isAdded', false)
            ->call('toggle')
            ->assertSet('isAdded', true)
            ->assertSet('limitMessage', null);

        $this->assertDatabaseHas('comparison_lists', [
            'user_id' => $user->id,
            'list_type' => 'workspace',
        ]);
        $this->assertDatabaseHas('comparison_items', [
            'comparable_type' => Workspace::class,
            'comparable_id' => $listing->id,
        ]);
    }

    public function test_workspace_comparison_limit_is_independent_from_other_types(): void
    {
        $user = User::factory()->create();
        $workspaceList = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'workspace']);

        collect(range(1, 3))->each(function () use ($workspaceList) {
            $listing = Workspace::factory()->create(['status' => 'active']);
            ComparisonItem::factory()->create([
                'comparison_list_id' => $workspaceList->id,
                'comparable_type' => Workspace::class,
                'comparable_id' => $listing->id,
            ]);
        });

        $fourth = Workspace::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(ComparisonButton::class, ['comparable' => $fourth])
            ->call('toggle')
            ->assertSet('isAdded', false)
            ->assertSet('limitMessage', 'Можно сравнивать не более 3 объектов одновременно. Уберите один из списка сравнения, чтобы добавить другой.');

        $this->assertSame(3, $workspaceList->items()->count());
    }

    public function test_comparison_page_shows_workspace_tab_with_price_and_type(): void
    {
        $user = User::factory()->create();
        $list = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'workspace']);
        $listing = Workspace::factory()->create(['status' => 'active', 'address' => 'ул. Сравниваемая, 3', 'workspace_type' => 'office']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 3500]);
        ComparisonItem::factory()->create(['comparison_list_id' => $list->id, 'comparable_type' => Workspace::class, 'comparable_id' => $listing->id]);

        Livewire::actingAs($user)
            ->test(ComparisonIndex::class)
            ->call('setTab', 'workspace')
            ->assertSee('ул. Сравниваемая, 3')
            ->assertSee('3 500')
            ->assertSee('Офис');
    }

    public function test_favorite_and_comparison_buttons_visible_on_workspace_catalog_and_detail_pages(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->actingAs($user)->get(route('workspace.search'))->assertOk()->assertSeeLivewire('favorites.button')->assertSeeLivewire('comparison.button');
        $this->actingAs($user)->get(route('workspace.show', $listing))->assertOk()->assertSeeLivewire('favorites.button')->assertSeeLivewire('comparison.button');
    }
}
