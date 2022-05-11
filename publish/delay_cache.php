<?php

declare(strict_types=1);

return [
    'default' => [
        'fields' => ['app_id', 'channel_id'], # some field for build cache key
        'driver' => 'default',  # async_queue driver
        'delay' => 600,  # delay time 600s
        'expire' => 86400,  # expire time 86400s
        'block_timeout' => 30, # block timeout 30s
        /**
         * [className::class, 'method']
         * "className@method"
         * global function
         * closure function
         */
        'exit_callable' => ['className', 'method'], # callable for exit dispatch loop
    ]
];