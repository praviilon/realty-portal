<?php

namespace App\Livewire\Faq;

use App\Models\Faq;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Отдельная страница FAQ — эпик 30 дорожной карты (Веха 3). До этого эпика
 * FAQ существовал только в виде аккордеона на главной странице (эпик 12);
 * здесь та же модель App\Models\Faq, но с фильтром по категориям и своим
 * постоянным адресом, на который можно ссылаться из футера и т.п.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $category = ''; // '' = все категории

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function render()
    {
        $allCategories = Faq::query()
            ->orderBy('sort_order')
            ->pluck('category')
            ->unique()
            ->values();

        $faqsByCategory = Faq::query()
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('livewire.faq.index', [
            'categories' => $allCategories,
            'faqsByCategory' => $faqsByCategory,
        ]);
    }
}
