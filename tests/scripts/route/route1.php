<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-30 13:52:48
 * @Modified time:      2018-08-30 19:57:29
 * @Depends on Linker:  None
 * @Description:        用于测试的路由
 */

use lin\route\Route;

$Route = Route::getCreator();

//三种格式
$Route->create([
    '/string'  => 'SomeClass.string',
    '/array'   => ['SomeClass.array1', 'SomeClass.array2'],
    '/Closure' => function () {
        echo 'Closure';
    },
], 'get, post');

//测试动态路由
$Route->create([
    '/{a}-{b}' => function ($a, $b) {
        echo $a;
        echo $b;
    },
]);

//前置和后置
$Route->withPre('SomeClass.pre')->withPost('SomeClass.post')->create([
    '/pre' => 'SomeClass.pre',
]);

//测试终止
$Route->withPre('SomeClass.terminal')->withPost('SomeClass.post')->create([
    '/terminal' => function () {
        echo mt_rand();
    },
]);

//测试绑定在指定方法上
$Route->create([
    '/get' => function () {
        echo 'get';
    },
], 'get');
