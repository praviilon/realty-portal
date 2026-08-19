<?php

namespace App\Http\Controllers;

use App\Models\CommercialProperty;
use App\Models\ResidentialProperty;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Отзыв собственного объявления, ожидающего модерации ("в архив"), из
 * личного кабинета — по повторному запросу пользователя, тот же принцип,
 * что и у "Снять с публикации" (см. ListingUnpublishController), но для
 * объявлений в статусе 'moderation' вместо 'active': пользователь передумал
 * публиковать объявление ещё до решения модератора и хочет забрать его из
 * очереди, не дожидаясь рассмотрения.
 *
 * Разрешён переход ТОЛЬКО из 'moderation' — если объявление уже активно,
 * отклонено или уже в архиве, кнопка "Отозвать" на дашборде не показывается
 * вовсе (см. resources/views/dashboard.blade.php), но проверка статуса
 * здесь дублируется и на бэкенде по тем же причинам, что и в
 * ListingUnpublishController: скрытие кнопки в разметке — не единственная
 * защита, запрос можно отправить и напрямую.
 *
 * Владение объявлением проверяется тем же способом (abort_unless), что и
 * в ListingUnpublishController и в мастерах редактирования — единый паттерн
 * для всего проекта, отдельных Policy-классов нет.
 */
class ListingWithdrawController extends Controller
{
    public function residential(ResidentialProperty $residentialProperty): RedirectResponse
    {
        abort_unless(Auth::id() === $residentialProperty->user_id, 403);
        abort_unless($residentialProperty->status === 'moderation', 403);

        $residentialProperty->update(['status' => 'archived']);

        return back()->with('status', 'Объявление отозвано с модерации и перемещено в архив.');
    }

    public function commercial(CommercialProperty $commercialProperty): RedirectResponse
    {
        abort_unless(Auth::id() === $commercialProperty->user_id, 403);
        abort_unless($commercialProperty->status === 'moderation', 403);

        $commercialProperty->update(['status' => 'archived']);

        return back()->with('status', 'Объявление отозвано с модерации и перемещено в архив.');
    }

    public function workspace(Workspace $workspace): RedirectResponse
    {
        abort_unless(Auth::id() === $workspace->user_id, 403);
        abort_unless($workspace->status === 'moderation', 403);

        $workspace->update(['status' => 'archived']);

        return back()->with('status', 'Объявление отозвано с модерации и перемещено в архив.');
    }
}
