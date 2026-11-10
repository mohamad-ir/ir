<?php

return [
    'version' => '1.7.0',

    // URL prefix of the administration dashboard. Changing it (e.g. to a
    // less predictable value) reduces automated login attempts.
    'admin_path' => env('ADMIN_PATH', 'admin'),

    // Image library used for the media library: auto (Imagick when installed, otherwise GD), imagick or gd.
    'image_driver' => env('IMAGE_DRIVER', 'auto'),

    // Token that authorises the /cron/{token} web-cron fallback.
    'cron_token' => env('CRON_TOKEN'),
];
