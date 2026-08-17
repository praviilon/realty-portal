<?php

namespace Tests\Feature;

use App\Livewire\Comparison\Button;
use App\Livewire\Comparison\Index as ComparisonIndex;
use App\Models\ComparisonItem;
use App\Models\ComparisonList;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic18ComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('comparison.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_add_listing_to_comparison(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        Livewire::actingAs($user)
            ->test(Button::class, ['comparable' => $listing])
            ->assertSet('isAdded', false)
            ->call('toggle')
            ->assertSet('isAdded', true)
            ->assertSet('limitMessage', null);

        $this->assertDatabaseHas('comparison_lists', [
            'user_id' => $user->id,
            'list_type' => 'residential_sale',
        ]);
        $this->assertDatabaseHas('comparison_items', [
            'comparable_type' => ResidentialProperty::class,
            'comparable_id' => $listing->id,
        ]);
    }

    public function test_toggling_again_removes_the_item(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        $component = Livewire::actingAs($user)->test(Button::class, ['comparable' => $listing]);

        $component->call('toggle')->assertSet('isAdded', true);
        $this->assertSame(1, ComparisonItem::query()->count());

        $component->call('toggle')->assertSet('isAdded', false);
        $this->assertSame(0, ComparisonItem::query()->count());
    }

    public function test_guest_clicking_toggle_is_redirected_without_creating_anything(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        Livewire::test(Button::class, ['comparable' => $listing])
            ->call('toggle')
            ->assertRedirect(route('login'));

        $this->assertSame(0, ComparisonList::query()->count());
        $this->assertSame(0, ComparisonItem::query()->count());
    }

    public function test_limit_of_three_items_per_list_is_enforced(): void
    {
        $user = User::factory()->create();
        $list = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_sale']);

        collect(range(1, 3))->each(function () use ($list) {
            $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
            ComparisonItem::factory()->create([
                'comparison_list_id' => $list->id,
                'comparable_type' => ResidentialProperty::class,
                'comparable_id' => $listing->id,
            ]);
        });

        $fourth = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        Livewire::actingAs($user)
            ->test(Button::class, ['comparable' => $fourth])
            ->assertSet('isAdded', false)
            ->call('toggle')
            ->assertSet('isAdded', false)
            ->assertSet('limitMessage', 'Можно сравнивать не более 3 объектов одновременно. Уберите один из списка сравнения, чтобы добавить другой.');

        $this->assertSame(3, $list->items()->count());
    }

    public function test_different_list_types_have_independent_limits(): void
    {
        $user = User::factory()->create();
        $saleList = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_sale']);

        collect(range(1, 3))->each(function () use ($saleList) {
            $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
            ComparisonItem::factory()->create([
                'comparison_list_id' => $saleList->id,
                'comparable_type' => ResidentialProperty::class,
                'comparable_id' => $listing->id,
            ]);
        });

        $rentListing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'rent']);

        Livewire::actingAs($user)
            ->test(Button::class, ['comparable' => $rentListing])
            ->call('toggle')
            ->assertSet('isAdded', true)
            ->assertSet('limitMessage', null);

        $this->assertDatabaseHas('comparison_lists', ['user_id' => $user->id, 'list_type' => 'residential_rent']);
    }

    public function test_comparison_page_shows_only_current_tab_items(): void
    {
        $user = User::factory()->create();
        $saleList = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_sale']);
        $rentList = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_rent']);

        $saleListing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale', 'address' => 'ул. Продажная, 1']);
        $rentListing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'rent', 'address' => 'ул. Арендная, 2']);

        ComparisonItem::factory()->create(['comparison_list_id' => $saleList->id, 'comparable_type' => ResidentialProperty::class, 'comparable_id' => $saleListing->id]);
        ComparisonItem::factory()->create(['comparison_list_id' => $rentList->id, 'comparable_type' => ResidentialProperty::class, 'comparable_id' => $rentListing->id]);

        Livewire::actingAs($user)
            ->test(ComparisonIndex::class)
            ->assertSet('tab', 'residential_sale')
            ->assertSee('ул. Продажная, 1')
            ->assertDontSee('ул. Арендная, 2')
            ->call('setTab', 'residential_rent')
            ->assertSee('ул. Арендная, 2')
            ->assertDontSee('ул. Продажная, 1');
    }

    public function test_user_can_remove_item_from_comparison_page(): void
    {
        $user = User::factory()->create();
        $list = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_sale']);
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        $item = ComparisonItem::factory()->create(['comparison_list_id' => $list->id, 'comparable_type' => ResidentialProperty::class, 'comparable_id' => $listing->id]);

        Livewire::actingAs($user)
            ->test(ComparisonIndex::class)
            ->call('removeItem', $item->id);

        $this->assertDatabaseMissing('comparison_items', ['id' => $item->id]);
    }

    public function test_user_cannot_remove_another_users_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $list = ComparisonList::factory()->create(['user_id' => $owner->id, 'list_type' => 'residential_sale']);
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        $item = ComparisonItem::factory()->create(['comparison_list_id' => $list->id, 'comparable_type' => ResidentialProperty::class, 'comparable_id' => $listing->id]);

        Livewire::actingAs($intruder)
            ->test(ComparisonIndex::class)
            ->call('removeItem', $item->id);

        $this->assertDatabaseHas('comparison_items', ['id' => $item->id]);
    }

    public function test_clear_list_removes_all_items_in_current_tab_only(): void
    {
        $user = User::factory()->create();
        $saleList = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_sale']);
        $rentList = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_rent']);

        $saleListing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        $rentListing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'rent']);

        ComparisonItem::factory()->create(['comparison_list_id' => $saleList->id, 'comparable_type' => ResidentialProperty::class, 'comparable_id' => $saleListing->id]);
        ComparisonItem::factory()->create(['comparison_list_id' => $rentList->id, 'comparable_type' => ResidentialProperty::class, 'comparable_id' => $rentListing->id]);

        Livewire::actingAs($user)
            ->test(ComparisonIndex::class)
            ->set('tab', 'residential_sale')
            ->call('clearList');

        $this->assertSame(0, $saleList->items()->count());
        $this->assertSame(1, $rentList->items()->count());
    }

    public function test_comparison_page_opens_on_first_non_empty_tab(): void
    {
        $user = User::factory()->create();
        $rentList = ComparisonList::factory()->create(['user_id' => $user->id, 'list_type' => 'residential_rent']);
        $rentListing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'rent']);
        ComparisonItem::factory()->create(['comparison_list_id' => $rentList->id, 'comparable_type' => ResidentialProperty::class, 'comparable_id' => $rentListing->id]);

        Livewire::actingAs($user)
            ->test(ComparisonIndex::class)
            ->assertSet('tab', 'residential_rent');
    }

    public function test_comparison_button_is_visible_on_catalog_and_detail_pages(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);

        $this->actingAs($user)->get(route('residential.search'))->assertOk()->assertSeeLivewire('comparison.button');
        $this->actingAs($user)->get(route('residential.show', $listing))->assertOk()->assertSeeLivewire('comparison.button');
    }

    public function test_comparison_page_is_reachable_via_real_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/account/comparison')
            ->assertOk();
    }
}
