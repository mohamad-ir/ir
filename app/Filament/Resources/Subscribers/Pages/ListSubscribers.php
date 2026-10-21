<?php

namespace App\Filament\Resources\Subscribers\Pages;

use App\Enums\SubscriberStatus;
use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\Subscriber;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Export CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => response()->streamDownload(function () {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['email', 'name', 'status', 'interests', 'source', 'confirmed_at', 'created_at']);
                    Subscriber::orderBy('id')->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $s) {
                            fputcsv($out, [$s->email, $s->name, $s->status->value, implode('|', $s->interests ?? []), $s->source, $s->confirmed_at?->toDateTimeString(), $s->created_at?->toDateTimeString()]);
                        }
                    });
                    fclose($out);
                }, 'subscribers-'.now()->format('Y-m-d').'.csv')),
            Action::make('import')->label('Import CSV')->icon('heroicon-o-arrow-up-tray')->color('gray')
                ->modalDescription('CSV with an "email" column and optionally "name". Existing addresses are updated, never duplicated.')
                ->schema([
                    FileUpload::make('file')->required()->storeFiles(false)->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel']),
                    CheckboxList::make('interests')->options(Subscriber::INTERESTS)->default(['weekly_case'])->columns(2),
                    Toggle::make('consent')->label('I confirm these people consented to receive emails')->accepted(),
                ])
                ->action(function (array $data) {
                    $handle = fopen($data['file']->getRealPath(), 'r');
                    $header = array_map(fn ($h) => strtolower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF")), fgetcsv($handle) ?: []);
                    $emailIdx = array_search('email', $header);
                    $nameIdx = array_search('name', $header);
                    $added = $skipped = 0;
                    while (($row = fgetcsv($handle)) !== false) {
                        $email = strtolower(trim($row[$emailIdx === false ? 0 : $emailIdx] ?? ''));
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $skipped++;

                            continue;
                        }
                        $s = Subscriber::firstOrNew(['email' => $email]);
                        if ($s->exists && $s->status === SubscriberStatus::Unsubscribed) {
                            $skipped++;

                            continue; // never re-subscribe someone who opted out
                        }
                        $s->fill([
                            'name' => $s->name ?: ($nameIdx !== false ? ($row[$nameIdx] ?? null) : null),
                            'status' => SubscriberStatus::Subscribed,
                            'confirmed_at' => $s->confirmed_at ?? now(),
                            'source' => $s->source ?: 'import',
                            'interests' => array_values(array_unique([...($s->interests ?? []), ...($data['interests'] ?? [])])),
                        ])->save();
                        $added++;
                    }
                    fclose($handle);
                    Notification::make()->success()->title("Imported {$added} subscribers".($skipped ? ", skipped {$skipped}" : ''))->send();
                }),
            CreateAction::make()->label('Add subscriber')->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'subscribed' => Tab::make('Subscribed')->badge(Subscriber::active()->count())->modifyQueryUsing(fn ($query) => $query->active()),
            'pending' => Tab::make('Awaiting confirmation')->modifyQueryUsing(fn ($query) => $query->where('status', 'pending')),
            'unsubscribed' => Tab::make('Unsubscribed')->modifyQueryUsing(fn ($query) => $query->where('status', 'unsubscribed')),
        ];
    }
}
