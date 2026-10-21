<?php

use App\Support\EnvWriter;
use App\Support\Installer;

it('redirects everything to the installer until installed, then disables it', function () {
    putenv('APP_INSTALLED=false');
    $_ENV['APP_INSTALLED'] = 'false';
    $_SERVER['APP_INSTALLED'] = 'false';
    @unlink(Installer::lockFile());

    $this->get('/cases')->assertRedirect('/install');
    $this->get('/install')->assertOk()->assertSee('Welcome');

    Installer::markInstalled();
    $this->get('/install')->assertNotFound();

    @unlink(Installer::lockFile());
    putenv('APP_INSTALLED=true');
    $_ENV['APP_INSTALLED'] = 'true';
    $_SERVER['APP_INSTALLED'] = 'true';
});

it('writes and quotes .env values safely', function () {
    $path = sys_get_temp_dir().'/env-'.uniqid();
    file_put_contents($path, "APP_NAME=Old\nDB_PASSWORD=\n");

    EnvWriter::set(['APP_NAME' => 'My Site', 'DB_PASSWORD' => 'p@ss#word$1', 'NEW_KEY' => true], $path);
    $env = file_get_contents($path);

    expect($env)->toContain('APP_NAME="My Site"')->toContain('DB_PASSWORD="p@ss#word\\$1"')->toContain('NEW_KEY=true');
    unlink($path);
});
