<?php

return [
    'default' => [ //Preset name
        //All parameters are optional
        'queues' => [// Queues names
            'default',
        ],
        'delay' => 1, //Delay in seconds
        'failed-jobs' => false, //Show count failed jobs
        'full-info' => false, //Show more information on queue size (only redis)
        'min' => false //Minimal display view
    ]
];
