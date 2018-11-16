<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-30 13:52:48
 * @Modified time:      2018-08-30 20:03:43
 * @Depends on Linker:  None
 * @Description:        用于测试冲突的路由，和route1规则冲突
 */

use lin\route\Route;

$Route = Route::getCreator();

//三种格式
$Route->create([
    '/Closure' => function () {
        echo 'Closure';
    },
    '/string'  => 'SomeClass.main',
    '/array'   => ['SomeClass.main', 'SomeClass.main'],
], 'get, post');
