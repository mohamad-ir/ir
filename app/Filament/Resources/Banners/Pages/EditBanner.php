<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Models\Banner;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    public function getContentTabLabel(): ?string
    {
        return 'Banner';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        $urls = ['home' => '/', 'cases' => '/cases', 'videos' => '/videos', 'articles' => '/articles', 'courses' => '/courses', 'webinars' => '/webinars', 'galleries' => '/galleries', 'membership' => '/membership'];

        return [
            Action::make('view')->label('View on website')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->visible(fn () => isset($urls[$this->record->placement]))
                ->url(fn () => url($urls[$this->record->placement] ?? '/'), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
