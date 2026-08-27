<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ClamAV scanning for file uploads
    |--------------------------------------------------------------------------
    |
    | When enabled, every upload is streamed to clamd for a malware scan before
    | it is stored. Scanning is fail-closed: if clamd is unreachable, uploads
    | are rejected rather than stored unscanned.
    |
    | 'host' may be a TCP host or an absolute path to the clamd Unix socket.
    |
    */

    'clamav' => [
        'enabled' => (bool) env('CLAMAV_SCAN', false),
        'host' => env('CLAMAV_HOST', '/run/clamav/clamd.ctl'),
        'port' => (int) env('CLAMAV_PORT', 3310),
        'timeout' => (int) env('CLAMAV_TIMEOUT', 30),
    ],
];
