<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\ActivityLog;
use App\Services\Newsletter\CampaignSender;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    public function isSending(): bool
    {
        return $this->record->status === CampaignStatus::Sending;
    }

    /** Polled from the status panel while the dashboard is open, so sending progresses even without cron. */
    public function processBatch(): void
    {
        if (! $this->isSending()) {
            return;
        }
        app(CampaignSender::class)->sendBatch($this->record);
        $this->record->refresh();
        if ($this->record->status === CampaignStatus::Sent) {
            Notification::make()->success()->title('Campaign sent to '.number_format($this->record->sent_count).' subscribers')->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $editable = fn () => in_array($this->record->status, [CampaignStatus::Draft, CampaignStatus::Scheduled, CampaignStatus::Cancelled], true);

        return [
            Action::make('test')->label('Send test')->icon('heroicon-o-beaker')->color('gray')
                ->schema([TextInput::make('email')->email()->required()->default(fn () => auth()->user()->email)])
                ->action(function (array $data) {
                    $this->save(false);
                    try {
                        app(CampaignSender::class)->sendTest($this->record, $data['email']);
                        Notification::make()->success()->title('Test email sent to '.$data['email'])->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Could not send the test')->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('schedule')->label('Schedule')->icon('heroicon-o-calendar')->color('gray')->visible($editable)
                ->schema([DateTimePicker::make('scheduled_at')->required()->native(false)->minDate(now())->seconds(false)])
                ->action(function (array $data) {
                    $this->save(false);
                    $this->record->update(['status' => CampaignStatus::Scheduled, 'scheduled_at' => $data['scheduled_at']]);
                    Notification::make()->success()->title('Scheduled for '.$this->record->scheduled_at->format('j M Y H:i'))->body('Sending requires the cron job described in the installation guide.')->send();
                }),
            Action::make('send')->label('Send now')->icon('heroicon-o-paper-airplane')->visible($editable)
                ->requiresConfirmation()
                ->modalHeading('Send this campaign now?')
                ->modalDescription(fn () => 'It will be sent to '.number_format(app(CampaignSender::class)->audienceQuery($this->record)->count()).' confirmed subscribers in batches of '.setting('newsletter.send_rate_per_minute', 20).' per minute.')
                ->action(function () {
                    $this->save(false);
                    $count = app(CampaignSender::class)->start($this->record->fresh());
                    $this->record->refresh();
                    ActivityLog::record('campaign_sent', 'Campaign “'.$this->record->subject.'” started ('.$count.' recipients)', $this->record);
                    Notification::make()->success()->title('Sending started')->body('Keep this page open or rely on the cron job to finish sending.')->send();
                }),
            Action::make('cancel')->label('Stop sending')->color('danger')->visible(fn () => $this->isSending() || $this->record->status === CampaignStatus::Scheduled)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => CampaignStatus::Cancelled]);
                    $this->record->recipients()->where('status', 'pending')->delete();
                }),
            DeleteAction::make()->visible($editable),
        ];
    }
}
