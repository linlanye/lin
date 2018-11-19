<?php
/**************************************
author:    林澜叶(linlanye)
email:     linlanye@sina.cn
license:   apache2.0
website:   www.lin-php.com
notice:    1. 遵循psr-4标准
2. 文件夹一律小写
3. 路径分隔符一律使用'/'
4. php7.2以下不支持
 **************************************/

$root = dirname(__DIR__);

//注文件加载顺序不可变
require $root . '/app/register.php'; //1.注册框架组件
require $root . '/vendor/autoload.php'; //2.自动加载
require $root . '/app/boot.php'; //3.应用启动，加载用户自定义的流程
