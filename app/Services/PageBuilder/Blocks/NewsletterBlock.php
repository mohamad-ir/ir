<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Subscriber;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

class NewsletterBlock extends PageBlock
{
    public static function key(): string { return 'newsletter'; }

    public static function label(): string { return 'Newsletter signup'; }

    public static function icon(): string { return 'heroicon-o-envelope'; }

    public static function defaultTone(): string { return 'accent'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->placeholder('Uses the newsletter setting: “Receive a new OCT case every week”')->maxLength(160),
            Textarea::make('text')->rows(2),
            CheckboxList::make('interests')->label('Subscribe people to')->options(Subscriber::INTERESTS)->default(['weekly_case'])->columns(2),
            ToggleButtons::make('style')->inline()->options(['split' => 'Text beside form', 'centered' => 'Centred'])->default('split'),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['heading'] = ($data['heading'] ?? null) ?: setting('newsletter.signup_heading');
        $data['text'] = ($data['text'] ?? null) ?: setting('newsletter.signup_text');

        return $data;
    }
}
