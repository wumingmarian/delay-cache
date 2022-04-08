<?php

declare(strict_types=1);

return [
    'default' => [
        'fields' => ['app_id', 'channel_id'], # some field for build cache key
        'driver' => 'ranking',  # async_queue driver
        'delay' => 600,  # delay time
    ]
];