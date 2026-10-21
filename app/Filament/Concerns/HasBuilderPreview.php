<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

/**
 * Live preview for the page builder: the current (unsaved) form state is
 * stored under a private token and rendered by /_preview/builder/{token}
 * inside the dashboard's responsive preview frame.
 */
trait HasBuilderPreview
{
    public string $previewToken = '';

    public function mountHasBuilderPreview(): void
    {
        $this->previewToken = Str::random(40);
    }

    #[Renderless]
    public function refreshPreview(): void
    {
        $data = $this->data ?? [];

        Cache::put('builder-preview:'.$this->previewToken, [
            'user_id' => auth()->id(),
            'title' => $data['title'] ?? $data['name'] ?? 'Preview',
            'show_title' => $data['show_title'] ?? false,
            'template' => $data['template'] ?? 'default',
            'blocks' => $data['blocks'] ?? [],
            'is_home' => isset($this->record) && method_exists($this->record, 'isHomepage') && $this->record->isHomepage(),
        ], now()->addHours(3));

        $this->dispatch('builder-preview-updated');
    }

    public function previewUrl(): string
    {
        return route('preview.builder', $this->previewToken);
    }
}
