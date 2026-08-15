<?php

namespace Tests\Feature;

use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Epic6PropertyShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_full_listing_details(): void
    {
        $seller = User::factory()->create(['first_name' => 'Пётр', 'last_name' => 'Иванов']);
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'user_id' => $seller->id,
            'address' => 'ул. Примерная, 10',
            'price' => 7500000,
            'area' => 54,
        ]);

        $response = $this->get(route('residential.show', $listing));

        $response->assertStatus(200)
            ->assertSee('ул. Примерная, 10')
            ->assertSee('7 500 000')
            ->assertSee('54')
            ->assertSee('Пётр Иванов')
            ->assertSee('Написать продавцу');
    }

    public function test_show_page_displays_photos_when_present(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => ResidentialProperty::class,
            'path' => 'property-photos/test-photo.webp',
        ]);

        $this->get(route('residential.show', $listing))
            ->assertSee('property-photos/test-photo.webp', false);
    }

    public function test_show_page_placeholder_when_no_photos(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        $this->get(route('residential.show', $listing))
            ->assertSee('Фотографии ещё не добавлены');
    }

    public function test_views_count_increments_once_per_session(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'views_count' => 0]);

        $this->get(route('residential.show', $listing));
        $this->get(route('residential.show', $listing));

        $this->assertSame(1, $listing->fresh()->views_count);
    }

    public function test_map_with_single_pin_is_rendered(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        $this->get(route('residential.show', $listing))
            ->assertSee('Расположение на карте');
    }
}
