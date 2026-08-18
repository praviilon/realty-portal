<?php

namespace App\Http\Controllers;

use App\Models\CommercialProperty;
use App\Models\ResidentialProperty;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Снятие собственного объявления с публикации ("в архив") из личного
 * кабинета — по запросу пользователя: раньше это мог сделать только админ
 * через Filament (смена статуса вручную). Здесь — тот же самый переход
 * статуса ('active' -> 'archived'), но доступный владельцу объявления
 * прямо из /dashboard, без похода в поддержку.
 *
 * Разрешён переход ТОЛЬКО из 'active' — если объявление уже отклонено,
 * на модерации или уже в архиве, кнопка на дашборде не показывается вовсе
 * (см. resources/views/dashboard.blade.php), но проверка статуса здесь
 * дублируется и на бэкенде: нельзя полагаться только на скрытие кнопки в
 * разметке, запрос можно отправить и напрямую.
 *
 * Владение объявлением проверяется тем же способом (abort_unless), что и
 * в мастерах редактирования (App\Livewire\Property\CreateWizard и др.) —
 * никаких отдельных Policy-классов в проекте нет, везде используется этот
 * простой паттерн.
 */
class ListingUnpublishController extends Controller
{
    public function residential(ResidentialProperty $residentialProperty): RedirectResponse
    {
        abort_unless(Auth::id() === $residentialProperty->user_id, 403);
        abort_unless($residentialProperty->status === 'active', 403);

        $residentialProperty->update(['status' => 'archived']);

        return back()->with('status', 'Объявление снято с публикации и перемещено в архив.');
    }

    public function commercial(CommercialProperty $commercialProperty): RedirectResponse
    {
        abort_unless(Auth::id() === $commercialProperty->user_id, 403);
        abort_unless($commercialProperty->status === 'active', 403);

        $commercialProperty->update(['status' => 'archived']);

        return back()->with('status', 'Объявление снято с публикации и перемещено в архив.');
    }

    public function workspace(Workspace $workspace): RedirectResponse
    {
        abort_unless(Auth::id() === $workspace->user_id, 403);
        abort_unless($workspace->status === 'active', 403);

        $workspace->update(['status' => 'archived']);

        return back()->with('status', 'Объявление снято с публикации и перемещено в архив.');
    }
}
