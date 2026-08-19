<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Доработка админ-панели по просьбе пользователя: список всех пользователей
 * сервиса (имя, дата регистрации, e-mail, телефон, кол-во объявлений,
 * последний вход) + удаление пользователя (кроме админов) + сброс пароля
 * на временный дефолтный. См. App\Filament\Resources\UserResource.
 */
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_users_list_shows_key_info_including_listings_count(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan.petrov@example.com',
            'phone' => '+7 (900) 123-45-67',
        ]);
        ResidentialProperty::factory()->count(2)->create(['user_id' => $user->id]);
        $commercial = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $commercial->id]);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$user, $this->admin])
            ->assertTableColumnStateSet('listings_count', 3, record: $user)
            ->assertTableColumnFormattedStateSet('role', 'Пользователь', record: $user)
            ->assertTableColumnFormattedStateSet('role', 'Админ', record: $this->admin)
            ->assertTableColumnStateSet('last_login_at', null, record: $user);
    }

    public function test_users_list_shows_last_login_after_real_login(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->last_login_at);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->last_login_at);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertTableColumnStateSet('last_login_at', $user->last_login_at, record: $user);
    }

    public function test_delete_action_hidden_for_admin_rows(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('deleteUser', $otherAdmin)
            ->assertTableActionHidden('deleteUser', $this->admin);
    }

    public function test_delete_action_visible_for_regular_user_rows(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertTableActionVisible('deleteUser', $user);
    }

    public function test_admin_can_delete_user_and_all_their_listings(): void
    {
        $user = User::factory()->create();
        $residential = ResidentialProperty::factory()->create(['user_id' => $user->id]);
        $commercial = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $commercial->id]);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('deleteUser', $user);

        $this->assertNull(User::find($user->id));
        $this->assertNull(ResidentialProperty::find($residential->id));
        $this->assertNull(CommercialProperty::find($commercial->id));
    }

    public function test_deleting_user_does_not_affect_other_users(): void
    {
        $user = User::factory()->create();
        $untouched = User::factory()->create();
        $untouchedListing = ResidentialProperty::factory()->create(['user_id' => $untouched->id]);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('deleteUser', $user);

        $this->assertNotNull(User::find($untouched->id));
        $this->assertNotNull(ResidentialProperty::find($untouchedListing->id));
    }

    public function test_admin_can_reset_users_password_to_default(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('resetPassword', $user);

        $this->assertTrue(Hash::check(User::DEFAULT_RESET_PASSWORD, $user->fresh()->password));
    }

    public function test_reset_password_action_available_for_admin_rows_too(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertTableActionVisible('resetPassword', $otherAdmin);
    }

    public function test_reset_default_password_satisfies_password_policy_and_can_log_in(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('resetPassword', $user);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', User::DEFAULT_RESET_PASSWORD)
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_regular_user_cannot_reach_users_admin_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_guest_cannot_reach_users_admin_panel(): void
    {
        $this->get('/admin/users')->assertRedirect('/admin/login');
    }

    /**
     * Регресс-тест: резолвится через настоящий HTTP-маршрут панели (а не
     * через Livewire::test() напрямую по классу компонента) — см. пояснение
     * в Epic8ModerationTest::test_admin_can_see_pending_listing_via_real_admin_route().
     */
    public function test_admin_can_see_users_list_via_real_admin_route(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Реальный',
            'last_name' => 'Маршрут',
            'email' => 'real.route.user@example.com',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('real.route.user@example.com')
            ->assertSee('—'); // плейсхолдер для пользователей без last_login_at
    }
}
