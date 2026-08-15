<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic10ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_prompt_instead_of_write_button(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        $this->get(route('residential.show', $listing))
            ->assertSee('Войти, чтобы написать')
            ->assertDontSee('Написать продавцу');
    }

    public function test_owner_does_not_see_write_button_on_own_listing(): void
    {
        $owner = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('residential.show', $listing))
            ->assertDontSee('Написать продавцу');
    }

    public function test_start_chat_creates_thread_and_redirects(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);

        Livewire::actingAs($buyer)
            ->test(\App\Livewire\Property\Show::class, ['residentialProperty' => $listing])
            ->call('startChat')
            ->assertRedirect();

        $this->assertDatabaseHas('chats', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'listable_type' => ResidentialProperty::class,
            'listable_id' => $listing->id,
        ]);
    }

    public function test_start_chat_reuses_existing_thread(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);

        $first = Chat::findOrCreateFor($buyer, $listing);
        $second = Chat::findOrCreateFor($buyer, $listing);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Chat::count());
    }

    public function test_cannot_start_chat_with_yourself(): void
    {
        $seller = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);

        $this->expectException(\RuntimeException::class);

        Chat::findOrCreateFor($seller, $listing);
    }

    public function test_participant_can_send_and_see_messages(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);
        $chat = Chat::findOrCreateFor($buyer, $listing);

        Livewire::actingAs($buyer)
            ->test(\App\Livewire\Chat\Thread::class, ['chat' => $chat])
            ->set('text', 'Здравствуйте, актуально?')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'sender_id' => $buyer->id,
            'text' => 'Здравствуйте, актуально?',
        ]);
    }

    public function test_non_participant_cannot_open_chat(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);
        $chat = Chat::findOrCreateFor($buyer, $listing);

        $this->actingAs($stranger)
            ->get(route('chat.show', $chat))
            ->assertForbidden();
    }

    public function test_opening_thread_marks_incoming_messages_as_read(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id]);
        $chat = Chat::findOrCreateFor($buyer, $listing);

        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'sender_id' => $seller->id,
            'is_read' => false,
        ]);

        Livewire::actingAs($buyer)->test(\App\Livewire\Chat\Thread::class, ['chat' => $chat]);

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_inbox_shows_unread_count_and_last_message(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'user_id' => $seller->id, 'address' => 'ул. Диалоговая, 3']);
        $chat = Chat::findOrCreateFor($buyer, $listing);

        Message::factory()->create(['chat_id' => $chat->id, 'sender_id' => $seller->id, 'text' => 'Добрый день!', 'is_read' => false]);

        Livewire::actingAs($buyer)
            ->test(\App\Livewire\Chat\Inbox::class)
            ->assertSee('ул. Диалоговая, 3')
            ->assertSee('Добрый день!')
            ->assertSee('1'); // счётчик непрочитанных
    }

    public function test_inbox_empty_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Chat\Inbox::class)
            ->assertSee('У вас пока нет диалогов');
    }
}
