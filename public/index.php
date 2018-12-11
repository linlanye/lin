<?php
/**************************************
author:    林澜叶(linlanye)
email:     linlanye@sina.cn
license:   apache2.0
website:   www.lin-php.com
version:   1.0
 **************************************/

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php'; //1.自动加载
require $root . '/app/register.php'; //2.注册框架组件
require $root . '/app/boot.php'; //3.应用启动，加载用户自定义的流程
