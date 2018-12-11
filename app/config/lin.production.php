<?php

return [
/*****basement组件*****/
    /*配置组件*/
    'config'    => [
        'debug' => false,
    ],
    /*调试组件*/
    'debug'     => [
        'panel' => [
            'display' => 'none',
        ],
    ],
    /*事件组件*/
    'event'     => [
        'debug' => false,
    ],
    /*日志组件*/
    'log'       => [
        'debug' => false,
    ],

    /*访问服务器的客户端组件*/
    'server'    => [
        'file'  => [
            'debug' => false,
        ],
        'local' => [
            'debug' => false,
        ],
        'kv'    => [
            'debug' => false,
        ],
        'queue' => [
            'debug' => false,
        ],
        'sql'   => [
            'debug' => false,
        ],
    ],

/**********************/

/*****一般组件*****/
    /*异常错误处理组件*/
    'exception' => [
        'exception' => [
            'show' => false,
        ],
        'error'     => [
            'show' => false,
        ],
    ],

    /*层组件*/
    'layer'     => [
        'debug' => false,
    ],

    /*对象-关系映射组件*/
    'orm'       => [
        'debug' => false,
    ],

    /*数据处理组件*/
    'processor' => [
        'debug' => false,
    ],

    /*路由组件*/
    'route'     => [
        'cache' => [
            'on' => true,
        ],
        'debug' => false,
    ],

    /*安全组件*/
    'security'  => [
        'debug' => false,
    ],

    /*验证组件*/
    'validator' => [
        'debug' => false,
    ],

    /*视图组件*/
    'view'      => [
        'cache' => [
            'life' => 0,
        ],
        'debug' => false,
    ],

];
