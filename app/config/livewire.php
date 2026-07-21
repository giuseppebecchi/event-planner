<?php

return [
    'payload' => [
        'max_nesting_depth' => (int) env('LIVEWIRE_PAYLOAD_MAX_NESTING_DEPTH', 30),
    ],

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'local'),
        'rules' => ['required', 'file', 'max:' . (int) env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_KB', 51200)],
        'directory' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DIRECTORY', 'livewire-tmp'),
        'middleware' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MIDDLEWARE', 'throttle:60,1'),
        'max_upload_time' => (int) env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_TIME', 5),
        'cleanup' => true,
    ],
];
