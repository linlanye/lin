<?php
/**
 * 注册basement组件
 */
require basename(__DIR__) . '/vendor/basement/basement/boot.php'; //引入basement启动文件

//核心组件,生命期内不可更改
Linker::register([
    'Config'  => '\\lin\\basement\\config\\Config',
    'Request' => '\\lin\\basement\\request\\Request',
    'Event'   => '\\lin\\basement\\event\\Event',
], true);

//插件，生命期内可更改
Linker::register([
    'ServerSQL'   => '\\lin\\basement\\server\\sql\\SQLPDO',
    'ServerKV'    => '\\lin\\basement\\server\\kv\\KVLocal',
    'ServerLocal' => '\\lin\\basement\\server\\local\\Local',
    'ServerQueue' => '\\lin\\basement\\server\\queue\\QueueLocal',
    'Exception'   => '\\lin\\basement\\exception\\GeneralException',
    'Log'         => '\\lin\\basement\\log\\Log',
    'Debug'       => '\\lin\\basement\\debug\\Debug',
    'Lang'        => '\\lin\\basement\\lang\\Lang',
]);
