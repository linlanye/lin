<?php

error_reporting(E_ALL);

//引入composer和basement
define('__VENDOR__', realpath(dirname(dirname(dirname(__FILE__)))));
define('__ROOT__', __VENDOR__ . '/lin');
define('__TMP__', __ROOT__ . '/tests/tmp');
define('__DB__', __ROOT__ . '/tests/datasets');
define('__TEST__', __ROOT__ . '/tests');
require __VENDOR__ . '/autoload.php';
require __VENDOR__ . '/linker.php';

//注册组件
Linker::register([
    'Config'  => '\\lin\\basement\\config\\Config',
    'Request' => '\\lin\\basement\\request\\Request',
], true);

Linker::register([
    'ServerSQL'   => '\\lin\\basement\\server\\sql\\SQLPDO',
    'ServerKV'    => '\\lin\\basement\\server\\kv\\KVLocal',
    'ServerLocal' => '\\lin\\basement\\server\\local\\Local',
    'ServerQueue' => '\\lin\\basement\\server\\queue\\LocalQueue',
    'Log'         => '\\lin\\basement\\log\\log',

    'Exception'   => '\\lin\\basement\\exception\\GeneralException',
    'Debug'       => '\\lin\\basement\\debug\\Debug',
    'Event'       => '\\lin\\basement\\event\\Event',
    'Lang'        => '\\lin\\basement\\lang\\Lang',

]);

//读取配置

Linker::Config()::set('lin', include __VENDOR__ . '/lin/config/test-lin.php');
Linker::Config()::set('servers', include __VENDOR__ . '/lin/config/test-servers.php');

if (!file_exists(__TMP__)) {
    mkdir(__TMP__, 0750, true);
}
