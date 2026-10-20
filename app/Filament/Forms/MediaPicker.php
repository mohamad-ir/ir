<?php

namespace App\Filament\Forms;

use App\Models\Media;
use App\Services\Media\MediaService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Media-library picker: searchable list with thumbnails, plus "Upload new"
 * which stores the file through MediaService (EXIF stripped, WebP variants).
 */
class MediaPicker extends Select
{
    protected bool $imagesOnly = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()
            ->allowHtml()
            ->native(false)
            ->placeholder('Choose from the media library or upload')
            ->options(fn () => $this->baseQuery()->latest('id')->limit(30)->get()->mapWithKeys(fn (Media $m) => [$m->id => $m->pickerLabel()])->all())
            ->getSearchResultsUsing(fn (string $search) => $this->baseQuery()
                ->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('original_name', 'like', "%{$search}%")->orWhere('alt', 'like', "%{$search}%"))
                ->latest('id')->limit(40)->get()->mapWithKeys(fn (Media $m) => [$m->id => $m->pickerLabel()])->all())
            ->getOptionLabelUsing(fn ($value) => Media::find($value)?->pickerLabel())
            ->createOptionForm(fn () => [
                FileUpload::make('file')
                    ->label('File')
                    ->required()
                    ->storeFiles(false)
                    ->acceptedFileTypes($this->imagesOnly ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif'] : MediaService::acceptedMimeTypes())
                    ->maxSize(51200),
                Grid::make(2)->schema([
                    TextInput::make('title'),
                    TextInput::make('alt')->label('Alternative text')->helperText('Describe the image for screen readers and Google Images.'),
                ]),
                Textarea::make('caption')->rows(2),
                Toggle::make('is_deidentified')->label('I confirm this image contains no patient-identifiable information')->accepted(fn () => $this->imagesOnly)->default(false),
            ])
            ->createOptionUsing(function (array $data) {
                $file = $data['file'];
                if (is_array($file)) {
                    $file = reset($file);
                }
                /** @var TemporaryUploadedFile $file */
                $media = app(MediaService::class)->store($file, collect($data)->only(['title', 'alt', 'caption', 'is_deidentified'])->filter(fn ($v) => $v !== null && $v !== '')->all());

                return $media->id;
            })
            ->createOptionModalHeading('Upload to media library');
    }

    public function anyFile(bool $condition = true): static
    {
        $this->imagesOnly = ! $condition;

        return $this;
    }

    public function videosOnly(): static
    {
        $this->imagesOnly = false;
        $this->mediaQueryModifier = fn ($q) => $q->where('mime_type', 'like', 'video/%');

        return $this;
    }

    protected ?\Closure $mediaQueryModifier = null;

    protected function baseQuery()
    {
        $query = Media::query();
        if ($this->mediaQueryModifier) {
            ($this->mediaQueryModifier)($query);
        } elseif ($this->imagesOnly) {
            $query->where('mime_type', 'like', 'image/%');
        }

        return $query;
    }
}
