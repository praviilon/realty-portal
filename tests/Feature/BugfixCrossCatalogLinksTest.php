<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регресс-тесты на баги, найденные пользователем после деплоя Вехи 3:
 * 1) в каталогах жилой/коммерческой недвижимости не хватало перекрёстных
 *    ссылок на другие типы объявлений (только у рабочих пространств уже
 *    были ссылки на оба других каталога);
 * 2) ссылка «Смотреть часто задаваемые вопросы» на /help вела на старый
 *    якорь '/#faq' вместо новой отдельной страницы /faq (эпик 30).
 */
class BugfixCrossCatalogLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_residential_catalog_links_to_commercial_and_workspace(): void
    {
        $this->get(route('residential.search'))
            ->assertOk()
            ->assertSee(route('commercial.search'), false)
            ->assertSee(route('workspace.search'), false);
    }

    public function test_commercial_catalog_links_to_residential_and_workspace(): void
    {
        $this->get(route('commercial.search'))
            ->assertOk()
            ->assertSee(route('residential.search'), false)
            ->assertSee(route('workspace.search'), false);
    }

    public function test_workspace_catalog_links_to_residential_and_commercial(): void
    {
        $this->get(route('workspace.search'))
            ->assertOk()
            ->assertSee(route('residential.search'), false)
            ->assertSee(route('commercial.search'), false);
    }

    public function test_help_page_links_to_dedicated_faq_page(): void
    {
        $this->get(route('help'))
            ->assertOk()
            ->assertSee(route('faq.index'), false);
    }
}
