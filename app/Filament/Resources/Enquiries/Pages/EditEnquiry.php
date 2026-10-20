<?php

namespace App\Filament\Resources\Enquiries\Pages;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Mail\EnquiryReply;
use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EditEnquiry extends EditRecord
{
    protected static string $resource = EnquiryResource::class;

    public function getTitle(): string
    {
        return 'Enquiry from '.$this->record->name;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        if ($this->record->status === EnquiryStatus::New) {
            $this->record->update(['status' => EnquiryStatus::Read]);
            $this->refreshFormData(['status']);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')->label('Reply')->icon('heroicon-o-arrow-uturn-left')
                ->modalWidth('2xl')
                ->schema([
                    TextInput::make('subject')->required()->default(fn () => 'Re: '.$this->record->subject),
                    Textarea::make('body')->label('Message')->required()->rows(10),
                ])
                ->action(function (array $data) {
                    try {
                        Mail::to($this->record->email, $this->record->name)->send(new EnquiryReply($this->record, $data['body'], $data['subject']));
                        $this->record->update(['status' => EnquiryStatus::Replied, 'replied_at' => now(), 'admin_notes' => trim(($this->record->admin_notes ? $this->record->admin_notes."\n\n" : '').'Reply sent '.now()->format('j M Y H:i').":\n".$data['body'])]);
                        ActivityLog::record('enquiry_replied', 'Replied to enquiry from '.$this->record->email);
                        $this->refreshFormData(['status', 'admin_notes']);
                        Notification::make()->success()->title('Reply sent')->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('The reply could not be sent')->body($e->getMessage().' — check Settings › Email.')->persistent()->send();
                    }
                }),
            Action::make('mail')->label('Open in mail app')->icon('heroicon-o-envelope')->color('gray')
                ->url(fn () => 'mailto:'.$this->record->email.'?subject='.rawurlencode('Re: '.$this->record->subject)),
            DeleteAction::make(),
        ];
    }
}
