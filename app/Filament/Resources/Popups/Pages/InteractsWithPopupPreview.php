<?php

namespace App\Filament\Resources\Popups\Pages;

use App\Models\Article;
use App\Services\Popups\PopupService;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Renderless;

/** Live preview: the unsaved form is stored under a random token and shown on a real page of the site in an iframe. */
trait InteractsWithPopupPreview
{
    public string $previewToken = '';

    public string $previewPath = '/';

    public function mountInteractsWithPopupPreview(): void
    {
        $this->previewToken = PopupService::newPreviewToken();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Renderless]
    public function refreshPreview(): void
    {
        PopupService::storePreview($this->previewToken, $this->data ?? []);
        $this->dispatch('popup-preview-updated');
    }

    public function previewPaths(): array
    {
        return array_filter([
            '/' => __('Homepage'),
            '/cases' => __('Case library'),
            Article::published()->latest('published_at')->first()?->urlPath() => __('Article'),
            '/courses' => __('Courses'),
        ], fn ($v, $k) => filled($k), ARRAY_FILTER_USE_BOTH);
    }
}
