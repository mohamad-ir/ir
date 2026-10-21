<?php

namespace App\Filament\Resources\Webinars\Pages;

use App\Filament\Resources\Webinars\WebinarResource;
use App\Filament\Support\ContentEditRecord;
use App\Mail\WebinarMessage;
use App\Mail\WebinarRecordingAvailable;
use App\Models\WebinarRegistration;
use App\Services\Learning\LearningService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class EditWebinar extends ContentEditRecord
{
    protected static string $resource = WebinarResource::class;

    protected static function previewType(): string { return 'webinar'; }

    protected static function permissionPrefix(): string { return 'webinars'; }

    public function getContentTabLabel(): ?string
    {
        return 'Webinar details';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('message')->label('Email all registrants')->icon('heroicon-o-envelope')
                    ->schema([
                        TextInput::make('subject')->required()->default(fn () => 'Update: '.$this->record->title),
                        RichEditor::make('body')->required()->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList']]),
                    ])
                    ->modalDescription(fn () => 'Sent to '.$this->record->activeRegistrations()->count().' registrants (queued, delivered within a few minutes).')
                    ->action(function (array $data) {
                        $count = 0;
                        foreach ($this->record->activeRegistrations()->with('user')->get() as $registration) {
                            if ($registration->user) {
                                app(LearningService::class)->mail($registration->user, new WebinarMessage($this->record, $data['subject'], $data['body']));
                                $count++;
                            }
                        }
                        Notification::make()->success()->title("Message queued for {$count} registrants")->send();
                    }),
                Action::make('recording')->label('Email recording link')->icon('heroicon-o-film')
                    ->visible(fn () => filled($this->record->recording_url))
                    ->requiresConfirmation()
                    ->modalDescription('Emails every registrant who has not yet been told about the recording.')
                    ->action(function () {
                        $pending = WebinarRegistration::where('webinar_id', $this->record->id)->whereNull('canceled_at')->whereNull('recording_sent_at')->with('user')->get();
                        foreach ($pending as $registration) {
                            if ($registration->user) {
                                $registration->update(['recording_sent_at' => now()]);
                                app(LearningService::class)->mail($registration->user, new WebinarRecordingAvailable($registration));
                            }
                        }
                        Notification::make()->success()->title('Recording email queued for '.$pending->count().' registrants')->send();
                    }),
            ])->label('Registrants')->icon('heroicon-m-users')->button()->color('gray'),
            ...parent::getHeaderActions(),
        ];
    }
}
