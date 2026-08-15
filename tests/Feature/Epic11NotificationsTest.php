<?php

namespace Tests\Feature;

use App\Filament\Resources\ResidentialPropertyResource\Pages\ListResidentialProperties;
use App\Models\Chat;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic11NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_listing_notifies_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $owner->id]);

        Livewire::actingAs($admin)
            ->test(ListResidentialProperties::class)
            ->callTableAction('approve', $listing);

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());
        $notification = $owner->fresh()->notifications()->first();
        $this->assertSame('active', $notification->data['status']);
    }

    public function test_rejecting_listing_notifies_owner_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $owner->id]);

        Livewire::actingAs($admin)
            ->test(ListResidentialProperties::class)
            ->callTableAction('reject', $listing, data: ['rejection_reason' => 'Плохие фото']);

        $notification = $owner->fresh()->notifications()->first();
        $this->assertSame('rejected', $notification->data['status']);
        $this->assertSame('Плохие фото', $notification->data['rejection_reason']);
    }

    public function test_new_message_notifies_other_participant_only(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);
        $chat = Chat::findOrCreateFor($buyer, $listing);

        Livewire::actingAs($buyer)
            ->test(\App\Livewire\Chat\Thread::class, ['chat' => $chat])
            ->set('text', 'Здравствуйте!')
            ->call('send');

        $this->assertSame(1, $seller->fresh()->unreadNotifications()->count());
        $this->assertSame(0, $buyer->fresh()->unreadNotifications()->count());
    }

    public function test_bell_shows_unread_count(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);
        $chat = Chat::findOrCreateFor($buyer, $listing);

        Livewire::actingAs($buyer)->test(\App\Livewire\Chat\Thread::class, ['chat' => $chat])
            ->set('text', 'Привет')->call('send');

        Livewire::actingAs($seller)
            ->test(\App\Livewire\Notifications\Bell::class)
            ->assertViewHas('unreadCount', 1)
            ->assertSee('Привет', false);
    }

    public function test_mark_as_read_clears_unread_and_redirects_to_chat(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);
        $chat = Chat::findOrCreateFor($buyer, $listing);

        Livewire::actingAs($buyer)->test(\App\Livewire\Chat\Thread::class, ['chat' => $chat])
            ->set('text', 'Привет')->call('send');

        $notification = $seller->fresh()->notifications()->first();

        Livewire::actingAs($seller)
            ->test(\App\Livewire\Notifications\Bell::class)
            ->call('markAsRead', $notification->id)
            ->assertRedirect(route('chat.show', $chat));

        $this->assertSame(0, $seller->fresh()->unreadNotifications()->count());
    }

    public function test_mark_all_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $listingA = ResidentialProperty::factory()->moderation()->create(['user_id' => $owner->id]);
        $listingB = ResidentialProperty::factory()->moderation()->create(['user_id' => $owner->id]);

        Livewire::actingAs($admin)->test(ListResidentialProperties::class)->callTableAction('approve', $listingA);
        Livewire::actingAs($admin)->test(ListResidentialProperties::class)->callTableAction('approve', $listingB);

        $this->assertSame(2, $owner->fresh()->unreadNotifications()->count());

        Livewire::actingAs($owner)
            ->test(\App\Livewire\Notifications\Bell::class)
            ->call('markAllAsRead');

        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }
}
