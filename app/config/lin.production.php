<?php
$root = realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../');
$lin  = [
    'data'      => $root . '/data', //数据目录
    'cache'     => $root . '/cache', //缓存目录
    'config'    => $root . '/config', //配置目录
    'lang'      => $root . '/app/affix/lang', //语言包目录
    'route'     => $root . '/app/affix/route', //路由目录
    'event'     => $root . '/app/affix/event', //事件目录
    'view'      => $root . '/app/affix/response/view', //视图目录
    'jsonxml'   => $root . '/app/affix/response/jsonxml', //json, xml模板目录
    'framework' => $root . '/vendor/lin/src', //框架目录
];

return [
/*****basement组件*****/
    /*配置组件*/
    'config'    => [
        'path'  => $lin['config'],
        'debug' => false,
    ],
    /*调试组件*/
    'debug'     => [
        'panel'   => [
            'display' => 'none',
            'name'    => [
                'prior'  => 'SYSTEM',
                'hidden' => [],
            ],
        ],
        'lang'    => 'none',
        'default' => [
            'name' => 'LIN',
        ],
    ],
    /*事件组件*/
    'event'     => [
        'path'  => $lin['event'],
        'debug' => false,
    ],
    /*语言映射组件*/
    'lang'      => [
        'default' => [
            'label'    => 'lin',
            'map'      => '',
            'i18n'     => 'en',
            'autoload' => function ($label, $i18n) use ($lin) {
                $lang = $lin['lang'] . "/$label.$i18n.php";
                if (file_exists($lang)) {
                    return include $lang;
                }
                return [];
            },
        ],
    ],
    /*日志组件*/
    'log'       => [
        'use'     => 'local',
        'server'  => [
            'local' => [
                'path'      => $lin['data'] . '/log',
                'size'      => 100 * 1024 * 1024,
                'frequency' => 720,
                'format'    => function ($type, $content, $time) {
                    return "[$type]; " . date('Y-m-d H:i:s', $time) . "; $content";
                },
            ],
            'sql'   => [
                'table'  => 'logs',
                'fields' => [
                    'name'    => 'name',
                    'type'    => 'type',
                    'content' => 'content',
                    'time'    => 'created_time',
                ],
            ],
            'queue' => [
                'format' => function ($type, $content, $time) {
                    return ['type' => $type, 'content' => $content, 'time' => $time];
                },
                'prefix' => '_linlog_',
            ],
        ],
        'default' => [
            'name' => 'linlog',
        ],
        'debug'   => false,
    ],

    /*请求组件*/
    'request'   => [
        'type'    => [
            'tag'        => '__method',
            'substitute' => 'POST',
        ],
        'uploads' => [
            'path'   => $lin['data'] . '/upload',
            'rename' => '',
            'filter' => function ($fileType, $fileSize) {
                return true;
            },
        ],
    ],

    /*访问服务器的客户端组件*/
    'server'    => [
        'file'  => [
            'debug' => false,
        ],
        'local' => [
            'path'  => $lin['data'],
            'debug' => false,
        ],
        'kv'    => [
            'driver'  => [
                'memcached' => [
                    'use' => '*',
                ],
                'redis'     => [
                    'use' => 'kv*',
                ],
                'local'     => [
                    'path' => $lin['data'] . '/kv',
                ],
            ],
            'prefix'  => '',
            'default' => [
                'life' => 3600 * 24 * 7,
            ],
            'debug'   => false,
        ],
        'queue' => [
            'driver'  => [
                'redis' => [
                    'use' => 'queue*',
                ],
                'local' => [
                    'path' => $lin['data'] . '/queue',
                ],
            ],
            'default' => [
                'name' => 'lin',
            ],
            'debug'   => false,
        ],
        'sql'   => [
            'use'   => '*',
            'debug' => false,
        ],
    ],

/**********************/

/*****一般组件*****/
    /*异常错误处理组件*/
    'exception' => [
        'exception' => [
            'lang'     => 'none',
            'default'  => [
                'type' => 'General',
            ],
            'log'      => [
                'on'   => true,
                'name' => 'exception',
            ],
            'show'     => true,
            'callback' => '',
        ],
        'error'     => [
            'lang'     => 'none',
            'log'      => [
                'on'   => true,
                'name' => 'error',
            ],
            'show'     => true,
            'callback' => '',
        ],
    ],

    /*层组件*/
    'layer'     => [
        'namespace' => [
            'layer' => 'app\\layer',
            'block' => 'app\\block',
        ],
        'debug'     => false,
    ],

    /*对象-关系映射组件*/
    'orm'       => [
        'model' => [
            'default'  => [
                'table' => function ($className, $namespace) {
                    return strtolower($className);
                },
                'pk'    => function ($className, $namespace) {
                    return rtrim(strtolower($className), 's') . '_id';
                },
            ],
            'relation' => [
                'namespace' => 'app\\block\\model',
            ],
        ],
        'page'  => [
            'number' => 20,
        ],
        'debug' => false,
    ],

    /*数据处理组件*/
    'processor' => [
        'formatter' => [ //格式化器
            'default' => [
                'value' => function ($field) {return null;},
                'type'  => 'should',
            ],
        ],
        'mapper'    => [ //映射器
            'default' => [
                'value' => function ($field) {return null;},
                'type'  => 'should',
            ],
        ],
        'debug'     => false,
    ],

    /*响应组件*/
    'response'  => [
        'view'    => [
            'error'        => $lin['framework'] . '/response/structure/error.html',
            'success'      => $lin['framework'] . '/response/structure/success.html',
            'countdown_id' => 'lin-jump-countdown',
            'method'       => function ($template, $data) {
                $View = new lin\view\View;
                return $View->withData($data)->show($template);
            },
        ],
        'jsonxml' => [
            'charset'      => 'utf8',
            'path'         => $lin['jsonxml'],
            'cross_origin' => [
                'on'     => false,
                'domain' => '*',
            ],
        ],
        'file'    => [
            'path' => $lin['data'] . '/files',
        ],
        'default' => [
            'error'   => [
                'countdown' => 3,
                'info'      => 'Error!',
            ],
            'success' => [
                'countdown' => 3,
                'info'      => 'Success!',
            ],
            'json'    => [
                'opt'      => JSON_UNESCAPED_UNICODE,
                'depth'    => 512,
                'template' => null,
            ],
            'xml'     => [
                'header'   => '<?xml version="1.0" encoding="utf8"?>',
                'template' => null,
            ],
        ],
    ],

    /*路由组件*/
    'route'     => [
        'ci'        => false,
        'suffix'    => ['html', 'htm'],
        'path'      => $lin['route'],
        'terminal'  => [false],
        'namespace' => [
            'pre'  => 'app\\layer\\middleware',
            'main' => 'app\\layer',
            'post' => 'app\\layer\\middleware',
        ],
        'general'   => function ($url, $method) {
            $Class = 'app\\layer\\Error';
            (new $Class)->status404();
        },
        'cache'     => [
            'on'   => true,
            'path' => $lin['cache'] . '/route',
        ],
        'debug'     => false,
    ],

    /*安全组件*/
    'security'  => [
        'use'     => 'local',
        'server'  => [
            'local' => [
                'path' => $lin['data'] . '/security',
            ],
            'sql'   => [
                'table'  => 'security',
                'fields' => [
                    'id'      => 'client_id',
                    'type'    => 'type',
                    'content' => 'content',
                    'time'    => 'created_time',
                ],
            ],
        ],
        'gc'      => [
            'probability' => 0.0001,
            'max_life'    => 1800,
        ],
        'default' => [
            'cost' => 4,
            'life' => 1200,
        ],
        'image'   => [
            'resolution' => [130, 50],
            'length'     => 5,
            'level'      => 2,
            'seed'       => '23456789ABCDEFGHJKLMNPQRSTUWXYZ',
            'background' => [],
            'ttf'        => [
                $lin['framework'] . '/security/structure/captcha/stencil-four.ttf',
            ],
        ],
        'debug'   => false,
    ],

    /*session组件*/
    'session'   => [
        'life'   => 1200,
        'use'    => 'local',
        'server' => [
            'local'  => [
                'path' => $lin['data'] . '/session',
                'deep' => 0,
            ],
            'kv'     => [
                'prefix' => '_ssid_',
            ],
            'sql'    => [
                'table'  => 'sessions',
                'fields' => [
                    'id'      => 'session_id',
                    'content' => 'content',
                    'time'    => 'created_time',
                ],
            ],
            'custom' => 'SessionHandlerClass',
        ],
    ],

    /*url组件*/
    'url'       => [
        'dynamic' => [
            'path'  => function ($domin, $path, $script) {
                if ($path == '/') {
                    return "http://$domin";
                }
                $path = trim($path, '/');
                return "http://$domin/$path.html";
            },
            'query' => function ($params) {
                return '?' . http_build_query($params);
            },
        ],
        'static'  => [
            'path' => function ($domin, $path, $script) {
                $path = trim($path, '/');
                return "http://$domin/resource/$path";
            },
        ],
    ],

    /*验证组件*/
    'validator' => [
        'default' => [
            'info' => function ($fields) {return "${fields}字段验证失败";},
            'type' => 'may',
        ],
        'debug'   => false,
    ],

    /*视图组件*/
    'view'      => [
        'charset'  => "utf8",
        'path'     => $lin['view'],
        'tag'      => [
            'left'         => '{',
            'right'        => '}',
            'begin_static' => 'STATIC',
            'end_static'   => '/STATIC',
            'escape'       => '\\',
            'location'     => 'LOCATION',
            'extends'      => 'EXTENDS',
            'include'      => 'INCLUDE',
        ],
        'cache'    => [
            'life' => 0,
            'path' => $lin['cache'] . '/view',
        ],
        'security' => 'htmlspecialchars',
        'debug'    => false,
    ],

];
