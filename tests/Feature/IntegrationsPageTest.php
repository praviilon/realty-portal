<?php

namespace Tests\Feature;

use App\Filament\Pages\Integrations;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Реестр внешних интеграций в админ-панели — по просьбе пользователя
 * ("собрать swagger или что-то аналогичное со всеми API которые подключены
 * к порталу... отдельной страницей в админ панели"). Список — в
 * config/integrations.php, логика подсчёта статуса — в
 * App\Filament\Pages\Integrations::getIntegrations().
 */
class IntegrationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_integrations_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->assertOk()
            ->assertSee('Yandex Maps JS API')
            ->assertSee('Yandex SmartCaptcha');
    }

    public function test_regular_user_cannot_reach_integrations_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/integrations')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/integrations')->assertRedirect('/admin/login');
    }

    public function test_integration_with_configured_keys_is_marked_configured(): void
    {
        config(['services.yandex_maps.api_key' => 'test-key-123']);

        $admin = User::factory()->create(['role' => 'admin']);
        $integrations = Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->instance()
            ->getIntegrations();

        $yandexMaps = $integrations->firstWhere('key', 'yandex_maps');

        $this->assertTrue($yandexMaps['configured']);
    }

    public function test_integration_without_keys_is_marked_not_configured(): void
    {
        config(['services.yandex_maps.api_key' => null]);

        $admin = User::factory()->create(['role' => 'admin']);
        $integrations = Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->instance()
            ->getIntegrations();

        $yandexMaps = $integrations->firstWhere('key', 'yandex_maps');

        $this->assertFalse($yandexMaps['configured']);
    }

    public function test_integration_requiring_multiple_keys_needs_all_of_them(): void
    {
        config([
            'services.smartcaptcha.site_key' => 'site-key',
            'services.smartcaptcha.server_key' => null,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $integrations = Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->instance()
            ->getIntegrations();

        $smartCaptcha = $integrations->firstWhere('key', 'smartcaptcha');

        $this->assertFalse($smartCaptcha['configured']);

        config(['services.smartcaptcha.server_key' => 'server-key']);

        $integrations = Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->instance()
            ->getIntegrations();

        $smartCaptcha = $integrations->firstWhere('key', 'smartcaptcha');

        $this->assertTrue($smartCaptcha['configured']);
    }

    public function test_slack_is_flagged_as_not_wired_into_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $integrations = Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->instance()
            ->getIntegrations();

        $slack = $integrations->firstWhere('key', 'slack');

        $this->assertFalse($slack['wired_in_code']);
    }

    public function test_only_the_active_mail_driver_is_marked_active(): void
    {
        config(['mail.default' => 'postmark']);

        $admin = User::factory()->create(['role' => 'admin']);
        $integrations = Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->instance()
            ->getIntegrations();

        $postmark = $integrations->firstWhere('key', 'mail_postmark');
        $resend = $integrations->firstWhere('key', 'mail_resend');
        $yandexMaps = $integrations->firstWhere('key', 'yandex_maps');

        $this->assertTrue($postmark['is_active_mail_driver']);
        $this->assertFalse($resend['is_active_mail_driver']);
        $this->assertNull($yandexMaps['is_active_mail_driver']);
    }

    public function test_page_shows_the_current_mail_driver(): void
    {
        config(['mail.default' => 'log']);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Integrations::class)
            ->assertSee('log');
    }
}
