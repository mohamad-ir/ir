<?php

namespace App\Services\PageBuilder\Blocks;

use App\Enums\EnquiryType;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ContactFormBlock extends PageBlock
{
    public static function key(): string { return 'contact_form'; }

    public static function label(): string { return 'Contact form'; }

    public static function icon(): string { return 'heroicon-o-chat-bubble-left-ellipsis'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->default('Get in touch')->maxLength(160),
            Textarea::make('text')->rows(3),
            Select::make('default_type')->label('Pre-selected enquiry type')->options(EnquiryType::options())->placeholder('None'),
        ];
    }
}
