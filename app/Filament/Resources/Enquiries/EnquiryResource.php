<?php

namespace App\Filament\Resources\Enquiries;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use App\Models\Enquiry;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static ?string $navigationLabel = 'Enquiries';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $new = Enquiry::where('status', 'new')->count();

        return $new ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make(fn (?Enquiry $record) => $record?->subject)->description(fn (?Enquiry $record) => $record?->type->getLabel())->schema([
                    Placeholder::make('message')->hiddenLabel()->content(fn (?Enquiry $record) => new HtmlString('<div class="whitespace-pre-line text-[0.95rem] leading-relaxed">'.e($record?->message).'</div>')),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('From')->schema([
                    Placeholder::make('from')->hiddenLabel()->content(fn (?Enquiry $record) => new HtmlString('<div class="space-y-1 text-sm"><div class="font-semibold">'.e($record?->name).'</div><div><a class="text-primary-600" href="mailto:'.e($record?->email).'">'.e($record?->email).'</a></div><div class="text-gray-500">'.e($record?->organisation).'</div><div class="pt-2 text-xs text-gray-500">Received '.e($record?->created_at?->format('j M Y H:i')).'</div>'.($record?->replied_at ? '<div class="text-xs text-success-600">Replied '.e($record->replied_at->format('j M Y H:i')).'</div>' : '').'</div>')),
                ]),
                Section::make('Follow-up')->schema([
                    Select::make('status')->options(EnquiryStatus::options())->required(),
                    Textarea::make('admin_notes')->label('Private notes')->rows(4),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (Enquiry $record) => $record->status === EnquiryStatus::New ? 'font-semibold' : null)
            ->columns([
                TextColumn::make('subject')->searchable(['subject', 'name', 'email', 'message'])->wrap()->description(fn (Enquiry $record) => $record->name.' · '.$record->email),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([SelectFilter::make('type')->options(EnquiryType::options()), SelectFilter::make('status')->options(EnquiryStatus::options())])
            ->recordActions([EditAction::make()->label('Open')->icon('heroicon-o-envelope-open')])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('archive')->label('Archive')->icon('heroicon-o-archive-box')->action(fn (Collection $records) => $records->each->update(['status' => EnquiryStatus::Archived])),
                DeleteBulkAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnquiries::route('/'),
            'edit' => Pages\EditEnquiry::route('/{record}'),
        ];
    }
}
