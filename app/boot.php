<?php
/*
 *开发环境应用启动流程，可按需求在此自定义整个流程
 */
use lin\basement\debug\Debug;
use lin\exception\Exception;
use lin\route\Route;
use lin\session\Session;

//1.全局设置
date_default_timezone_set('PRC');
error_reporting(E_ALL);
include __DIR__ . '/lib/helper.php'; //引入全局助手函数(不建议使用)

//2.修改Lin的配置(注: 此句应置于流程之首)
Linker::Config()::set('lin', include __DIR__ . '/config/lin.php');
Linker::Config()::set('servers', include __DIR__ . '/config/lin-servers.php');

Exception::run(); //3.开启自定义错误异常处理
Session::run(); //4.开启自定义Session处理
Route::run(); //6.读取所有路由文件并运行(主流程)
Debug::run(); //7.输出调试收集的信息
