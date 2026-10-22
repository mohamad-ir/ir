<?php

return [
    'version' => '1.3.0',

    // URL prefix of the administration dashboard. Changing it (e.g. to a
    // less predictable value) reduces automated login attempts.
    'admin_path' => env('ADMIN_PATH', 'admin'),

    // Token that authorises the /cron/{token} web-cron fallback.
    'cron_token' => env('CRON_TOKEN'),
];
