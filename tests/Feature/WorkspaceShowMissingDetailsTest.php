<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Доработка по просьбе пользователя: на странице объявления рабочего
 * пространства не отображались владелец/способ связи (хотя данные уже
 * собираются в мастере создания — App\Livewire\Workspace\CreateWizard),
 * станция метро и время до неё пешком, тип места (закреплённое/hot desk) и
 * "особенности помещения" (floor_features). См.
 * resources/views/livewire/workspace/show.blade.php.
 *
 * Заодно по просьбе пользователя убрана особенность "Отдельный вход с
 * улицы" — она дублирует характеристику "Вход" (entrance_type). Убрана из
 * App\Models\Workspace::floorFeatureLabels() совсем, но данные ранее
 * созданных объявлений, где это значение уже сохранено в БД, не должны
 * ломать страницу — просто не отображаются.
 */
class WorkspaceShowMissingDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_owner_name_and_how_to_contact(): void
    {
        $owner = User::factory()->create(['first_name' => 'Игорь', 'last_name' => 'Петров']);
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'user_id' => $owner->id,
            'owner_type' => 'owner',
            'contact_type' => 'messages_only',
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertSee('Владелец')
            ->assertSee('Игорь Петров')
            ->assertSee('Только сообщения');
    }

    public function test_show_page_labels_agent_and_calls_and_messages_contact_type(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'owner_type' => 'agent',
            'contact_type' => 'calls_and_messages',
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertSee('Агент')
            ->assertSee('Звонки и сообщения');
    }

    public function test_show_page_displays_metro_station_and_distance(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'metro_station' => 'Тверская',
            'metro_distance_min' => 7,
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertSee('Тверская')
            ->assertSee('7 мин пешком');
    }

    public function test_show_page_hides_metro_block_when_not_set(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'metro_station' => null,
            'metro_distance_min' => null,
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertDontSee('мин пешком');
    }

    public function test_show_page_displays_workspace_subtype_for_workspace_type(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'workspace_type' => 'workspace',
            'workspace_subtype' => 'flexible',
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertSee('Тип места')
            ->assertSee('Свободное место (hot desk)');
    }

    public function test_show_page_hides_workspace_subtype_for_non_workspace_types(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'workspace_type' => 'office',
            'workspace_subtype' => null,
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertDontSee('Тип места');
    }

    public function test_show_page_displays_remaining_three_floor_features(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'floor_features' => ['parking', 'security', 'reception'],
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertSee('Особенности помещения')
            ->assertSee('Парковка')
            ->assertSee('Охрана/видеонаблюдение')
            ->assertSee('Ресепшн');
    }

    /**
     * Ранее созданные объявления могут ещё хранить 'separate_entrance' в
     * floor_features (значение убрано из Workspace::floorFeatureLabels(),
     * но никакой миграции данных для этого не делалось) — страница не
     * должна падать и не должна показывать эту особенность.
     */
    public function test_show_page_does_not_display_removed_separate_entrance_feature_and_does_not_break(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'floor_features' => ['separate_entrance'],
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertDontSee('Отдельный вход с улицы')
            ->assertDontSee('Особенности помещения');
    }

    public function test_show_page_hides_floor_features_block_when_empty(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'floor_features' => [],
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertOk()
            ->assertDontSee('Особенности помещения');
    }
}
