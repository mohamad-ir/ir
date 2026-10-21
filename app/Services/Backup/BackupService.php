<?php

namespace App\Services\Backup;

use App\Models\ActivityLog;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Backups that work on shared hosting without mysqldump or shell access:
 * the database is exported with PDO and zipped together with the media library.
 * Files are stored privately in storage/app/backups.
 */
class BackupService
{
    public function directory(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0750);
        if (! is_file($dir.'/.htaccess')) {
            file_put_contents($dir.'/.htaccess', "Require all denied\n");
        }

        return $dir;
    }

    /** @return array<int, array{name: string, size: int, date: int}> */
    public function list(): array
    {
        return collect(glob($this->directory().'/backup-*.zip') ?: [])
            ->map(fn ($f) => ['name' => basename($f), 'size' => filesize($f), 'date' => filemtime($f)])
            ->sortByDesc('date')->values()->all();
    }

    public function path(string $name): string
    {
        if (! preg_match('/^backup-[0-9a-z_-]+\.zip$/i', $name) || ! is_file($this->directory().'/'.$name)) {
            throw new RuntimeException('Backup not found.');
        }

        return $this->directory().'/'.$name;
    }

    public function create(?bool $includeMedia = null, string $label = 'manual'): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required for backups. Enable it in DirectAdmin › PHP settings.');
        }

        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        $includeMedia ??= (bool) Settings::get('backup.include_media', true);

        $name = 'backup-'.now()->format('Y-m-d_His').'-'.preg_replace('/[^a-z0-9]/', '', strtolower($label)).'.zip';
        $zipPath = $this->directory().'/'.$name;
        $sqlPath = $this->directory().'/dump-'.uniqid().'.sql';

        $this->dumpDatabase($sqlPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the backup archive.');
        }
        $zip->addFile($sqlPath, 'database.sql');
        $zip->addFromString('README.txt', "Backup of ".config('app.url')." created ".now()->toDateTimeString()."\n\n"
            ."database.sql — import with phpMyAdmin (DirectAdmin › MySQL Management) or System › Backups › Restore.\n"
            ."media/ — copy into public/media (or public_html/media).\n"
            ."app-key.txt — the APP_KEY needed to read encrypted settings; keep this archive private.\n");
        $zip->addFromString('app-key.txt', (string) config('app.key'));

        if ($includeMedia && is_dir($base = config('filesystems.disks.public.root'))) {
            foreach (File::allFiles($base) as $file) {
                if (in_array($file->getFilename(), ['.htaccess', '.gitignore'], true)) {
                    continue;
                }
                $zip->addFile($file->getRealPath(), 'media/'.str_replace('\\', '/', $file->getRelativePathname()));
            }
        }
        $zip->close();
        @unlink($sqlPath);

        ActivityLog::record('backup', "Backup created ({$name})");
        $this->prune();

        return $name;
    }

    public function dumpDatabase(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $out = fopen($path, 'w');
        fwrite($out, "-- ".config('app.name')." database backup\n-- ".now()->toDateTimeString()."\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = collect(DB::select('SHOW FULL TABLES'))->map(fn ($r) => array_values((array) $r))->filter(fn ($r) => ($r[1] ?? 'BASE TABLE') === 'BASE TABLE')->map(fn ($r) => $r[0]);

        foreach ($tables as $table) {
            $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n".array_values($create)[1].";\n\n");

            if (in_array($table, ['sessions', 'cache', 'cache_locks', 'jobs'], true)) {
                continue; // transient data
            }

            DB::table($table)->orderByRaw('1')->chunk(300, function ($rows) use ($out, $table, $pdo) {
                $values = $rows->map(fn ($row) => '('.collect((array) $row)->map(fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? $v : $pdo->quote((string) $v)))->implode(',').')');
                $columns = '`'.implode('`,`', array_keys((array) $rows->first())).'`';
                fwrite($out, "INSERT INTO `{$table}` ({$columns}) VALUES\n".$values->implode(",\n").";\n");
            });
            fwrite($out, "\n");
        }

        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($out);
    }

    /** Restores database (and optionally media) from one of this site's own backups. */
    public function restore(string $name, bool $restoreMedia = true): void
    {
        $zipPath = $this->path($name);
        @set_time_limit(900);

        $this->create(true, 'before-restore');

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('The backup archive could not be opened.');
        }
        $sql = $zip->getFromName('database.sql');
        if ($sql === false) {
            throw new RuntimeException('This archive does not contain database.sql.');
        }

        $pdo = DB::connection()->getPdo();
        $statement = '';
        foreach (preg_split("/\r?\n/", $sql) as $line) {
            if ($statement === '' && (str_starts_with($line, '--') || trim($line) === '')) {
                continue;
            }
            $statement .= $line."\n";
            if (str_ends_with(rtrim($line), ';')) {
                $pdo->exec($statement);
                $statement = '';
            }
        }

        if ($restoreMedia) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (str_starts_with($entry, 'media/') && ! str_contains($entry, '..') && ! str_ends_with($entry, '/')) {
                    $target = config('filesystems.disks.public.root').'/'.substr($entry, 6);
                    File::ensureDirectoryExists(dirname($target));
                    file_put_contents($target, $zip->getFromIndex($i));
                }
            }
        }
        $zip->close();

        Settings::flush();
        ActivityLog::record('restore', "Site restored from {$name}");
    }

    public function delete(string $name): void
    {
        @unlink($this->path($name));
    }

    public function prune(): void
    {
        $keep = max(1, (int) Settings::get('backup.keep', 6));
        foreach (array_slice($this->list(), $keep) as $old) {
            @unlink($this->directory().'/'.$old['name']);
        }
    }
}
