<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Console\Command;
use Throwable;

class OptimizeMediaCommand extends Command
{
    protected $signature = 'media:optimize
        {--all : Re-optimise every image, not only those made by an older version}
        {--id=* : Only these media IDs}';

    protected $description = 'Regenerate the optimised WebP versions of media library images';

    public function handle(MediaService $service): int
    {
        $query = Media::query()->where('mime_type', 'like', 'image/%')->orderBy('id');
        if ($ids = $this->option('id')) {
            $query->whereIn('id', $ids);
        } elseif (! $this->option('all')) {
            $query->where('variants_version', '<', Media::VARIANTS_VERSION);
        }

        $bar = $this->output->createProgressBar($query->count());
        $failed = 0;
        $query->lazyById(50)->each(function (Media $media) use ($service, $bar, &$failed) {
            try {
                $service->regenerate($media);
            } catch (Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("#{$media->id} {$media->original_name}: {$e->getMessage()}");
            }
            $bar->advance();
        });
        $bar->finish();
        $this->newLine();
        $this->info($failed ? "Done with {$failed} problem(s)." : 'All images are optimised.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
