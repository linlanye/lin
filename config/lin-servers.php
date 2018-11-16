<?php
/**
 * 开发环境
 * 此处配置为外部应用服务器的配置，不建议动态改变。可自行定义添加不同的服务器配置信息。
 * 所有服务器皆可填入多个配置，从而实现集群、主从、读写分离等。
 * 注：lin的服务器访问组件皆不提供多服务器的数据同步，请从服务器层面确保数据同步。权重小于0的服务器将不会被使用。
 *
 */

return [
    'sql'       => [ //使用pdo作为sql配置的代表
        'r' => ['dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8', //不同sql产品的dsn参考pdo说明
            'user'        => 'root', //连接账号
            'pwd'         => 'root', //连接密码
            'weight'      => 99, //权重，多个数据服务器存在时有用,不同模式下权重分开计算
            'mode'        => 'r', //访问模式,r为只读,w为只写,其余为读写,分别设置两个便可实现读写分离
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
