<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Forms\MediaPicker;
use App\Models\Certificate;
use App\Services\Learning\CertificateService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class CertificateSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/certificates';

    protected static ?string $navigationLabel = 'Certificate design';

    protected static ?string $title = 'Certificates of completion';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected function groups(): array
    {
        return ['certificates'];
    }

    protected function permission(): string
    {
        return 'courses.update';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('Preview sample PDF')->icon('heroicon-o-eye')->color('gray')
                ->action(function (CertificateService $service) {
                    $sample = new Certificate([
                        'code' => 'XX-SAMP-LE42', 'recipient_name' => auth()->user()->displayName(),
                        'course_title' => 'Systematic OCT Interpretation', 'hours' => 6.5, 'issued_at' => now(),
                    ]);

                    return response()->streamDownload(fn () => print ($service->pdf($sample)), 'certificate-sample.pdf', ['Content-Type' => 'application/pdf']);
                }),
        ];
    }

    protected function components(): array
    {
        return [
            Section::make('Certificates')->description('Issued automatically when a learner completes every lesson of a course (and passes any lesson quizzes). Each certificate has an ID that anyone can verify on your website.')
                ->columns(2)->schema([
                    Toggle::make('certificates.enabled')->label('Issue certificates')->columnSpanFull()
                        ->helperText('Can also be switched off per course.'),
                    TextInput::make('certificates.signatory_name')->label('Signed by')->placeholder(setting('doctor.name')),
                    TextInput::make('certificates.signatory_title')->label('Signatory title')->placeholder(setting('doctor.credentials')),
                    MediaPicker::make('certificates.signature_id')->label('Signature image (PNG with transparent background)')
                        ->helperText('Optional. PNG or JPEG.'),
                    TextInput::make('certificates.statement')->label('Wording')->maxLength(160)
                        ->helperText('Appears between the name and the course title.'),
                ]),
        ];
    }
}
