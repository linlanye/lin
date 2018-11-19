<?php
/**
 * 生产环境
 */
return [
    'sql'       => [
        'r' => ['dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8',
            'user'        => 'root',
            'pwd'         => 'root',
            'weight'      => 99,
            'mode'        => 'r',
        ],
        'w' => ['dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8',
            'user'        => 'root',
            'pwd'         => 'root',
            'weight'      => 99,
            'mode'        => 'w',
        ],
    ],
    'memcached' => [
        ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 99],
        ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 99],
    ],
    'redis'     => [
        'kv0'    => ['host' => '127.0.0.1', 'port' => '6379', 'pwd' => '', 'weight' => 99, 'timeout' => 1],
        'queue0' => ['host' => '127.0.0.1', 'port' => '6379', 'pwd' => '', 'weight' => 99, 'timeout' => 1],
    ],
];
