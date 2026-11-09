<?php

namespace App\Filament\Pages\Settings;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class CommentSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/comments';

    protected static ?string $navigationLabel = 'Comment settings';

    protected static ?string $title = 'Comments & discussion';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static bool $shouldRegisterNavigation = false;

    protected function groups(): array
    {
        return ['comments'];
    }

    protected function permission(): string
    {
        return 'settings.manage';
    }

    protected function components(): array
    {
        return [
            Section::make('Where members can comment')->heading(__('Where members can comment'))->columns(2)->schema([
                Toggle::make('comments.enabled')->label(__('Enable comments on the website'))->columnSpanFull()
                    ->helperText(__('Switching off hides every discussion. Comments are kept and return when you switch back on.')),
                CheckboxList::make('comments.types')->label(__('Content types'))->options(\App\Support\Ui::options(['case' => 'OCT cases', 'article' => 'Articles', 'video' => 'Videos']))
                    ->helperText(__('You can also close comments on a single case, article or video from its edit page.')),
                Toggle::make('comments.require_verified')->label(__('Require a confirmed email address'))->inline(false)
                    ->helperText(__('Strongly recommended — stops throw-away accounts from posting.')),
            ]),
            Section::make('Moderation')->heading(__('Moderation'))->columns(2)->schema([
                Radio::make('comments.moderation')->label(__('Approval'))->columnSpanFull()->options(\App\Support\Ui::options([
                    'first' => 'Approve each member’s first comment — after that their comments publish immediately (recommended)',
                    'all' => 'Approve every comment before it appears',
                    'none' => 'Publish immediately (spam checks below still hold suspicious comments)',
                ])),
                TextInput::make('comments.max_links')->label(__('Hold comments with more than … links'))->numeric()->minValue(0)->maxValue(20),
                TextInput::make('comments.edit_minutes')->label(__('Members can edit their comment for … minutes'))->numeric()->minValue(0)->maxValue(1440),
                Textarea::make('comments.blocked_words')->label(__('Blocked words'))->rows(3)->columnSpanFull()
                    ->helperText(__('One per line or separated by commas. Comments containing them are held for approval.')),
                Toggle::make('comments.notify_staff')->label(__('Email the team when a comment needs approval'))->columnSpanFull()
                    ->helperText(__('Sent to the notification address in Settings › General. Three reports from different members also hide a comment and send this email.')),
            ]),
            Section::make('Community guidelines')->heading(__('Community guidelines'))->schema([
                Textarea::make('comments.guidelines')->hiddenLabel()->rows(2)->maxLength(400)
                    ->helperText(__('Shown under the comment box.')),
            ]),
        ];
    }
}
