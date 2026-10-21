<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoContent;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    protected $signature = 'cms:demo {--remove : Remove the sample content instead}';

    protected $description = 'Install or remove the sample content';

    public function handle(): int
    {
        if ($this->option('remove')) {
            $this->info('Removed '.DemoContent::remove().' sample items.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DemoContentSeeder::class, '--force' => true]);
        $this->call('cms:reindex');

        return self::SUCCESS;
    }
}
