<?php
return [
    'sql'       => [
        'r'  => [
            'dsn'    => 'sqlite:' . __TMP__ . '/test.db',
            'user'   => null,
            'pwd'    => null,
            'weight' => 100,
            'mode'   => 'r',
        ],
        'w'  => [
            'dsn'    => 'sqlite:' . __TMP__ . '/test.db',
            'user'   => null,
            'pwd'    => null,
            'weight' => 100,
            'mode'   => 'w',
        ],
        'rw' => [
            'dsn'    => 'sqlite:' . __TMP__ . '/test.db',
            'user'   => null,
            'pwd'    => null,
            'weight' => 50,
            'mode'   => 'rw',
        ],

    ],
    'memcached' => [
        'valid0'   => ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
        'valid1'   => ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
        'invalid0' => ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 0], //测试无用
        'invalid1' => ['host' => '127.0.0.1', 'port' => 11211, 'weight' => -1], //测试无用
    ],
    'redis'     => [
        'kv0'    => ['host' => '127.0.0.1', 'port' => '6379', 'pwd' => '', 'weight' => 100, 'timeout' => 1],
        'kv1'    => ['host' => '127.0.0.1', 'port' => '6379', 'pwd' => '', 'weight' => 100, 'timeout' => 1],
        'queue0' => ['host' => '127.0.0.1', 'port' => '6379', 'pwd' => '', 'weight' => 100, 'timeout' => 1],
        'queue1' => ['host' => '127.0.0.1', 'port' => '6379', 'pwd' => '', 'weight' => 100, 'timeout' => 1],
    ],

];
