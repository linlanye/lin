<?php
//测试专用配置，与测试紧密耦合，不可更改
$lin = [
    'data'      => __TMP__,
    'cache'     => __TMP__,
    'config'    => __TMP__,
    'lang'      => __ROOT__ . '/lang',
    'route'     => __TEST__ . '/scripts/route',
    'event'     => __TEST__ . '/scripts/event',
    'view'      => __TEST__ . '/scripts/view',
    'jsonxml'   => __TMP__,
    'framework' => __ROOT__ . '/src',
];

return [
/*****basement组件*****/

    /*配置组件*/
    'config'    => [
        'path'  => $lin['config'],
        'debug' => true,
    ],
    /*调试组件*/
    'debug'     => [
        'panel'   => [ //调试面板
            'display' => 'all', //显示模式，空或none不显示，both显示全部,only显示小图标
            'name'    => [
                'prior'  => 'SYSTEM', //优先选中标识名
                'hidden' => [], //隐藏的标识名
            ],
        ],
        'lang'    => 'none', //多语言显示，none为关闭，only为多语言仅用于显示，但数据依然为原文，both为数据和显示都为多语言
        'default' => [
            'name' => 'LIN', //默认调试标识名
        ],
    ],
    /*事件组件*/
    'event'     => [
        'path'  => $lin['event'],
        'debug' => true,
    ],
    /*语言映射组件*/
    'lang'      => [
        'default' => [
            'label'    => 'test-lin',
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
        'use'     => 'local', //使用的服务器
        'server'  => [
            'local' => [
                'path'      => $lin['data'] . '/log',
                'size'      => 10, //单个日志文件大小最大值，
                'frequency' => 0, //使用文件夹对日志汇总的频率,24倍数则按天记，720倍数，则按月计，0则不汇总
                'format'    => function ($type, $content, $time) {
                    return $type . $content . $time;
                },
            ],
            'sql'   => [
                'table'  => 'logs',
                'fields' => [ //表字段
                    'name'    => 'name', //日志名，建议varchar类型
                    'type'    => 'type', //日志类型，建议varchar类型
                    'content' => 'content', //内容，text类型
                    'time'    => 'created_time', //创建时间,int类型
                ],
            ],
            'queue' => [ //使用队列进行异步操作,入参为日志类型，日志内容，日志创建时间
                'format' => function ($type, $content, $time) {
                    return $type . $content . $time;
                },
                'prefix' => '_linlog_', //队列名前缀
            ],
        ],
        'default' => [
            'name' => 'linlog', //默认日志名
        ],
        'debug'   => true,
    ],

    /*请求组件*/
    'request'   => [
        'type'    => [
            'tag'        => '__method',
            'substitute' => 'POST',
        ],
        'uploads' => [
            'path'   => $lin['data'] . '/upload',
            'format' => function ($fileName) {
                return $fileName;
            },
            'filter' => function ($fileType, $fileSize) {
                return true;
            },
        ],
    ],

    /*访问服务器的客户端组件*/
    'server'    => [
        'file'  => [
            'debug' => true,
        ],
        'local' => [
            'path'  => $lin['data'] . '/local',
            'debug' => true,
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
            'prefix'  => '', //键前缀
            'default' => [
                'life' => 2,
            ],
            'debug'   => true,
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
                'name' => '_lin_test_Xh02xh', //默认的队列名，为使队列为空
            ],
            'debug'   => true,
        ],
        'sql'   => [
            'use'   => '*', //使用所有服务器
            'debug' => true,
        ],
    ],

/**********************/

/*****一般组件*****/
    /*异常错误处理组件*/
    'exception' => [
        'exception' => [
            'lang'     => 'none', //多语言显示，none为关闭，only为仅用于显示，但日志记录仍为原文，both为都用于显示和日志记录
            'default'  => [
                'type' => 'General', //默认的异常类型
            ],
            'log'      => [
                'on'   => true, //是否日志记录异常
                'name' => 'exception', //日志名
            ],
            'show'     => true, //是否显示异常
            'callback' => '', //异常发生时回调函数，空则不回调，入参为异常类$Exception
        ],
        'error'     => [ //错误
            'lang'     => 'none',
            'log'      => [
                'on'   => true, //是否日志记录错误
                'name' => 'error', //日志名
            ],
            'show'     => true, //是否显示错误
            'callback' => '', //错误发生时回调函数，空则不回调，入参为$errNo, $errStr, $errFile, $errLine
        ],
    ],

    /*层组件*/
    'layer'     => [
        'namespace' => [
            'layer' => 'lin\\tests\\components\\layer', //层所处命名空间
            'block' => 'lin\\tests\\components\\layer', //块所处命名空间
        ],
        'debug'     => true,
    ],

    /*对象-关系映射组件*/
    'orm'       => [
        'model' => [
            'default'  => [
                'table' => function ($className, $namespace) {
                    return strtolower($className); //默认表名，可使用任何回调，入参为类名(不含命名空间)和所在命名空间
                },
                'pk'    => function ($className, $namespace) {
                    return rtrim(strtolower($className), 's') . '_id'; //默认主键名。如模型为Users，则pk为user_id
                },
            ],
            'relation' => [
                'namespace' => 'lin\\tests\\components\\orm', //用户模型所处的命名空间
            ],
        ],
        'page'  => [
            'number' => 20, //分页时一次查询的条数
        ],
        'debug' => true,
    ],

    /*数据处理组件*/
    'processor' => [
        'formatter' => [ //格式化器
            'default' => [
                'value' => function ($field) {
                    return $field; //must模式下，缺少的字段给予的默认值，可使用回调，入参为字段名
                },
                'type'  => 'should', //默认格式化模式，可为must, should, may分别代表，必须格式化，存在才格式化，存在且非空格式化(trim后长度大于0)
            ],
        ],
        'mapper'    => [ //映射器
            'default' => [
                'value' => function ($field) {
                    return $field; //must模式下，映射的字段不存在时，默认的赋值，可使用回调，入参为源字段名和目标字段名
                },
                'type'  => 'should',
            ],
        ],
        'debug'     => true,
    ],

    /*响应组件*/
    'response'  => [
        'view'    => [
            'error'        => $lin['framework'] . '/response/structure/error.html', //错误页面
            'success'      => $lin['framework'] . '/response/structure/success.html', //成功页面
            'countdown_id' => 'lin-jump-countdown', //跳转倒计时所在的html节点id
            'method'       => function ($template, $data) {
                $View = new lin\view\View; //嵌入的视图响应方法，入参为视图模板名、视图数据
                return $View->withData($data)->show($template);
            },
        ],
        'jsonxml' => [
            'charset'      => 'utf8', //编码
            'path'         => $lin['jsonxml'], //模板目录
            'cross_origin' => [
                'on'     => false, // 是否允许跨域
                'domain' => '*', //允许的域名列表
            ],
        ],
        'file'    => [
            'path' => $lin['data'] . '/files', //默认响应文件的存放路径
        ],
        'default' => [
            'error'   => [
                'countdown' => 3, //跳转倒计时（单位：s），0为不跳转
                'info'      => 'Error!', //默认错误信息
            ],
            'success' => [
                'countdown' => 3,
                'info'      => 'Success!',
            ],
            'json'    => [
                'opt'      => JSON_UNESCAPED_UNICODE, //json选项设置，见json_encode第二个参数
                'depth'    => 512, //编码深度，见json_encode第三个参数
                'template' => null, //默认模板名，为空则不使用
            ],
            'xml'     => [
                'header'   => '<?xml version="1.0" encoding="utf8"?>', //xml头
                'template' => null,
            ],
        ],
    ],

    /*路由组件*/
    'route'     => [
        'ci'        => false, //路由是否大小写不敏感
        'suffix'    => ['html', 'htm'], //支持的伪静态后缀名，数组索引为优先级顺序
        'path'      => $lin['route'], //存放路由规则的文件夹
        'terminal'  => [false], //终止符，返回该数组中任一符号则终止执行
        'namespace' => [ //执行类的命名空间前缀，在执行规则包含根'\'空间时不起作用，如'\Class'
            'pre'  => 'lin\\tests\\scripts\\route', //前置执行类的命名空间
            'main' => 'lin\\tests\\scripts\\route', //主执行行类的命名空间
            'post' => 'lin\\tests\\scripts\\route', //后置执行类的命名空间
        ],
        'general'   => function ($url, $method) {
            echo $url . $method;
        },
        'cache'     => [ //缓存相关
            'on'   => false,
            'path' => $lin['cache'] . '/route', //路由缓存路径
        ],
        'debug'     => true,
    ],

    /*安全组件*/
    'security'  => [
        'use'     => 'sql',
        'server'  => [
            'local' => [ //使用本地存储
                'path' => $lin['data'] . '/security', //存放路径
            ],
            'sql'   => [ //使用sql服务器
                'table'  => 'security', //表名
                'fields' => [ //键值为数据库字段名,
                    'id'      => 'client_id', //最少char(70),主键
                    'type'    => 'type', //客户端类型，临时或正式，最少1字节int
                    'content' => 'content', //可空text类型，数据增量无上限，有效场景越多越大，
                    'time'    => 'created_time', //客户端创建时间，最少4字节int
                ],
            ],
        ],
        'gc'      => [ //对临时客户端执行垃圾回收
            'probability' => 1, //触发概率
            'max_life'    => 1, //临时客户端的静止生命期(s)，同session机制
        ],
        'default' => [
            'cost' => 3, //加密token的消耗等级，越高越安全，见password_hash函数
            'life' => 1, //默认场景时效，单位s
        ],
        'image'   => [ //图片专用(即图片验证码)
            'resolution' => [130, 50], //生成的图片分辨率，宽和高
            'length'     => 5, //验证码长度，最短1位，最长20位
            'level'      => 2, //复杂等级，最高级6，最低级0，等级越高越耗资源
            'seed'       => '23456789ABCDEFGHJKLMNPQRSTUWXYZ', //生成验证码的种子
            'background' => [], //背景图片(完整路径名)，若有则随机选择
            'ttf'        => [ //随机使用的ttf字体库，
                $lin['framework'] . '/security/structure/captcha/stencil-four.ttf',
            ],
        ],
        'debug'   => true,
    ],

    /*session组件*/
    'session'   => [
        'life'   => 1, //过期时长，单位秒，0则永不过期
        'use'    => 'local', //当前使用的服务器
        'server' => [ //可选的服务器列表
            'local'  => [ //使用本地存储
                'path' => $lin['data'] . '/session', //存放路径
                'deep' => 0, //子目录深度
            ],
            'kv'     => [ //使用kv服务器
                'prefix' => '_ssid_', //使用的key前缀
            ],
            'sql'    => [ //使用sql服务器
                'table'  => 'sessions', //表名
                'fields' => [ //键值为数据库字段名,
                    'id'      => 'session_id', //字段类型根据session_id长度来定，主键，建议char(32)
                    'content' => 'content', //内容，text类型，允许null，
                    'time'    => 'created_time', //创建日期，int类型
                ],
            ],
            'custom' => 'SessionHandlerClass', //使用自定义的处理类，需实现SessionHandlerInterface
        ],
    ],

    /*url组件*/
    'url'       => [ //构建url
        'dynamic' => [ //使用回调生成完整path，入参为输入的当前有效域，输入的路径值，当前入口脚本名
            'path'  => function ($domin, $path, $script) {
                if ($path == '/') {
                    return "http://$domin";
                }
                $path = trim($path, '/'); //动态url规则
                return "http://$domin/$script/$path.html";
            },
            'query' => function ($params) {
                return '?' . http_build_query($params); //使用回调生产查询(GET)参数
            },
        ],
        'static'  => [ //构建静态资源专用的path，位于public目录下
            'path' => function ($domin, $path, $script) {
                $path = trim($path, '/');
                return "http://$domin/$path";
            },
        ],
    ],

    /*验证组件*/
    'validator' => [
        'default' => [ //默认
            'info' => function ($fields) {return $fields;}, //默认字段验证失败后的信息回调，入参为字段名
            'type' => 'may', //默认验证模式。可为must, should, may分别代表，必须验证，存在才验证，存在且不为空验证(trim后长度大于0)
        ],
        'debug'   => true,
    ],

    'view'      => [
        'charset'  => "utf8", //输出编码
        'path'     => $lin['view'], //模板文件路径
        'tag'      => [ //模板标签
            'left'         => '{', //左界定符，用于包裹模版语句
            'right'        => '}', //右界定符
            'begin_static' => 'STATIC', //静态化开始关键字，将其中内容输出为静态内容，而不是php代码
            'end_static'   => '/STATIC', //静态化结束关键字
            'escape'       => '\\', //转义标签，
            'location'     => 'LOCATION', //继承位置所在关键字
            'extends'      => 'EXTENDS', //界定符内继承视图关键字, 如{extends parent_view}
            'include'      => 'INCLUDE', //引入视图文件关键字，如{include some_view}
        ],
        'cache'    => [ //缓存相关
            'life' => 1, //有效期，单位s，0则不过期,小于0立即过期
            'path' => $lin['cache'] . '/view', //缓存路径
        ],
        'security' => 'htmlspecialchars', //对分配变量中的每一个标量做安全处理的回调，入参为每一个标量值
        'debug'    => true,
    ],

];
