<?php
/*
 *  助手函数库：用于提供函数化操作简化某些lin组建类的使用。注：无额外功能，不建议使用，只适用于应用层（即app文件夹内）
 */

use lin\basement\config\Config;
use lin\basement\debug\Debug;
use lin\basement\event\Event;
use lin\basement\log\Log;
use lin\basement\request\Request;
use lin\url\URL;
use lin\view\View;

//触发事件
function event(string $event)
{
    return Event::trigger($event);
}
//记录信息在默认日志文件中
function record(string $msg, string $type = 'info')
{
    $Log = new Log;
    return $Log->record($msg, $type);
}
//显示视图
function view(string $view, array $data = [])
{
    $View = new View;
    return $View->withData($data)->show($view);
}
//设置调试标识，用于收集两个相同标识之间代码的运行信息
function flag(string $flag)
{
    Debug::flag($flag);
}
//打印
function dump($var, ...$vars)
{
    if (empty($vars)) {
        return Debug::dump($var);
    }
    $var = [$var];
    return call_user_func_array(['\lin\basement\debug\Debug', 'dump'], array_merge($var, $vars));

}
//生成动态url
function url(string $url, array $params = [])
{
    return URL::get($url, $params);
}
//生成静态资源的url
function resource(string $url)
{
    return URL::getStatic($url);
}

//导入自定义文件
function import(string $filename)
{
    $filename = __DIR__ . '/' . preg_replace('/\./', '/', $filename) . '.php';
    if (file_exists($filename)) {
        return include $filename;
    }
    Linker::Exception()::throw ('文件不存在', 1, 'Import', $filename);
}

/**
 * 快速读取配置
 * @param  string|array $strOrArr 字符串的时读取配置，数组时设置配置。支持点语法
 * @param  string $confName       配置文件名
 * @return mixed
 */
function conf($strOrArr, string $configName = 'lin')
{
    return Config::$configName($strOrArr);
}

/**
 * 获得请求参数或设置请求参数
 * @param  string|array $strOrArr 字符串的时读取请求，数组时设置请求。支持点语法，
 *                                如get.id为获取$_GET['id']，['get.id'=>anything]为$_GET['id']=anything
 * @return mixed
 */
function req($strOrArr)
{
    if (is_array($strOrArr)) {
        foreach ($strOrArr as $keys => $value) {
            $keys   = explode('.', $keys);
            $method = $keys[0];
            $params = Request::get($method) ?: [];
            unset($keys[0]);
            $ref = &$params;
            foreach ($keys as $key) {
                if (!isset($ref[$key])) {
                    if (is_array($ref)) {
                        $ref[$key] = [];
                    } else {
                        $ref = [$key => []]; //非数组情况下用数组替代覆写原数据
                    }
                }
                $ref = &$ref[$key];
            }
            $ref = $value;
            Request::set($method, $params);
        }
    } else {
        $keys   = explode('.', $strOrArr);
        $params = Request::get($keys[0]);
        unset($keys[0]);
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                $params = $params[$key];
            } else {
                return null;
            }
        }
        return $params;
    }
}
