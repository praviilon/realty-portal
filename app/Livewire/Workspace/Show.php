<?php

namespace App\Livewire\Workspace;

use App\Models\Chat;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Детальная карточка объявления (рабочее пространство) — эпик 27 дорожной
 * карты (Веха 3). По аналогии с App\Livewire\CommercialProperty\Show (эпик 16,
 * Веха 2), но с ценой из workspace_pricing (несколько строк по периодам вместо
 * одной связи 1:1) и списком времени доступа (access_time).
 */
#[Layout('layouts.app')]
class Show extends Component
{
    public Workspace $listing;

    public function mount(Workspace $workspace): void
    {
        $this->listing = $workspace->load([
            'user',
            'pricing',
            'photos' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        // Публично видны только активные объявления — модерация/отклонённые/архив скрыты
        // по прямой ссылке даже от автора объявления (та же логика, что и у
        // жилой/коммерческой; ранее владелец мог просматривать своё
        // неактивное объявление напрямую — это было сообщено как баг и
        // намеренно убрано).
        abort_unless($this->listing->status === 'active', 404);

        $this->registerView();
    }

    protected function registerView(): void
    {
        $sessionKey = "viewed_workspace_{$this->listing->id}";

        if (session()->has($sessionKey)) {
            return;
        }

        session()->put($sessionKey, true);
        $this->listing->increment('views_count');
    }

    public function startChat()
    {
        $chat = Chat::findOrCreateFor(Auth::user(), $this->listing);

        return $this->redirect(route('chat.show', $chat), navigate: true);
    }

    public function render()
    {
        $pin = [[
            'id' => $this->listing->id,
            'lat' => (float) $this->listing->lat,
            'lng' => (float) $this->listing->lng,
            'price' => $this->listing->display_price,
            'address' => $this->listing->address,
        ]];

        return view('livewire.workspace.show', [
            'pin' => $pin,
            'pricingSorted' => $this->listing->pricing->sortBy(fn ($p) => array_search($p->period, ['hour', 'day', 'week', 'month'])),
            'floorFeatureLabels' => Workspace::floorFeatureLabels(),
            'amenityLabels' => Workspace::amenityLabels(),
            'extraOptionLabels' => Workspace::extraOptionLabels(),
            'accessTimeTypeLabels' => Workspace::accessTimeTypeLabels(),
            'buildingTypeLabels' => Workspace::buildingTypeLabels(),
            'entranceTypeLabels' => Workspace::entranceTypeLabels(),
        ]);
    }
}
