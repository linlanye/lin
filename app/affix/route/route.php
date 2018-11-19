<?php
/*
自定义路由解析规则规则
注意：规则解析优先级为静态 > 动态 > 通用
 */

$Route = lin\route\Route::getCreator();

$Route->create([
    '/' => 'Index.index',
]);
