<?php

namespace App\Filament\Resources\Campaigns;

use App\Enums\CampaignStatus;
use App\Filament\Support\Fields;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\Services\Newsletter\CampaignSender;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationLabel = 'Email campaigns';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Email')->schema([
                    TextInput::make('subject')->required()->maxLength(180),
                    TextInput::make('preheader')->maxLength(200)->helperText('Short preview text shown after the subject in most inboxes.'),
                    Fields::richEditor('body', 'Message')->required()->extraInputAttributes(['style' => 'min-height: 24rem'])
                        ->helperText('Every email automatically includes an unsubscribe link and your medical disclaimer.'),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Audience')->schema([
                    CheckboxList::make('audience.interests')->label('Send to subscribers interested in')->options(Subscriber::INTERESTS)
                        ->helperText('Leave all unticked to send to every confirmed subscriber.')->live(),
                    Placeholder::make('recipients')->label('Recipients')->content(function (Get $get) {
                        $campaign = new Campaign(['audience' => ['interests' => $get('audience.interests') ?? []]]);

                        return new HtmlString('<span class="text-2xl font-semibold">'.number_format(app(CampaignSender::class)->audienceQuery($campaign)->count()).'</span> confirmed subscribers');
                    }),
                ]),
                Section::make('Status')->visible(fn (?Model $record) => (bool) $record)->schema([
                    Placeholder::make('state')->hiddenLabel()->content(fn (?Campaign $record) => $record ? new HtmlString(view('filament.campaign-status', ['campaign' => $record])->render()) : null),
                ]),
            ]),
        ])->disabled(fn (?Model $record) => $record && in_array($record->status, [CampaignStatus::Sending, CampaignStatus::Sent], true));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')->searchable()->weight('medium')->wrap(),
                TextColumn::make('status')->badge(),
                TextColumn::make('when')->label('Sent / scheduled')->getStateUsing(fn (Campaign $record) => ($record->finished_at ?? $record->started_at ?? $record->scheduled_at)?->format('j M Y H:i'))->placeholder('—'),
                TextColumn::make('sent_count')->label('Sent')->numeric(),
                TextColumn::make('open_rate')->label('Opens')->getStateUsing(fn (Campaign $record) => $record->openRate() !== null ? $record->openRate().'%' : '—'),
                TextColumn::make('click_rate')->label('Clicks')->getStateUsing(fn (Campaign $record) => $record->clickRate() !== null ? $record->clickRate().'%' : '—'),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (Campaign $record) => in_array($record->status, [CampaignStatus::Draft, CampaignStatus::Scheduled, CampaignStatus::Cancelled])),
                EditAction::make('view')->label('Report')->icon('heroicon-o-chart-bar')->visible(fn (Campaign $record) => in_array($record->status, [CampaignStatus::Sending, CampaignStatus::Sent])),
                ReplicateAction::make()->label('Duplicate')->excludeAttributes(['status', 'scheduled_at', 'started_at', 'finished_at', 'recipients_count', 'sent_count', 'failed_count', 'opens_count', 'clicks_count'])
                    ->mutateRecordDataUsing(fn (array $data) => [...$data, 'subject' => $data['subject'].' (copy)', 'status' => CampaignStatus::Draft->value, 'created_by' => auth()->id()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
