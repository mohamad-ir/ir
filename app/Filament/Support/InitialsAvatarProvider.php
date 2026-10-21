<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/** Generates initials avatars locally (no third-party request, no data leaves the site). */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = Filament::getNameForDefaultAvatar($record);
        $initials = method_exists($record, 'initials') ? $record->initials() : mb_strtoupper(mb_substr($name, 0, 2));
        $palette = ['#12324f', '#0f7f81', '#6b4fa0', '#b4413b', '#d9822b', '#3a6b35'];
        $bg = $palette[crc32($name) % count($palette)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="'.$bg.'"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="26" font-weight="600" fill="#fff">'.e($initials).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
