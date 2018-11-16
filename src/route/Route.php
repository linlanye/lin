<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-01-13 23:30:47
 * @Modified time:      2018-09-29 13:37:32
 * @Depends on Linker:  Config Request
 * @Description:        路由运行和获得路由规则构建器
 */
namespace lin\route;

use Closure;
use Linker;
use lin\route\structure\Creator;
use lin\route\structure\Debug;
use lin\route\structure\Parser;

class Route
{
    private static $status = 0;

    /**
     * 运行路由器
     * @param  string $file 加载的路由规则文件，多个文件用‘,’隔开，支持目录等，类似于一般shells所用的规则
     * @return bool         是否执行成功
     */
    public static function run( ? string $files = '*') : bool
    {
        if (self::$status) {
            return false;
        }
        self::$status = 1;
        $config       = Linker::Config()::get('lin')['route'];
        $url          = Linker::Request()::getURL(); //当前url
        $url          = '/' . ltrim($url, '/'); //确保含有根路径
        $method       = Linker::Request()::getMethod(); //当期请求方法

        //url去掉伪后缀名
        foreach ($config['suffix'] as $pattern) {
            $n   = 0;
            $url = preg_replace("/\.$pattern$/", '', $url, 1, $n); //只踢除一次后缀名
            if ($n) {
                break;
            }
        }

        //运行解析器获得执行流程
        if ($files === null) {
            $rules = null;
        } else {
            $Parser = new Parser($config);
            $rules  = $Parser->execute($files, $url, strtoupper($method)); //获得执行规则
        }

        //执行通用规则
        if (!$rules) {
            if (is_callable($config['general'])) {
                call_user_func_array($config['general'], [$url, $method]);
            }
            if ($config['debug']) {
                (new Debug)->type('general');
            }
            return true;
        }

        //执行用户规则
        $params = $rules['params'];
        $rules  = $rules['rules'];
        //存在动态参数，合并入当期请求的参数里
        if ($params) {
            $old_params = Linker::Request()::getCurrent();
            Linker::Request()::setCurrent(array_merge($old_params, $params));
        }

        //执行流程
        $return = true;
        $times  = [];
        $t      = microtime(true);
        foreach ($rules as $index => $rule) {
            if ($rule[0] instanceof Closure) {
                $r = call_user_func_array($rule[0], $params); //只有闭包才使用动态参数
            } else {
                $r = call_user_func([new $rule[0], $rule[1]]);
            }
            $times[$index] = microtime(true) - $t;
            if (in_array($r, $config['terminal'], true)) {
                $return = false; //遭遇终止符
                break;
            }
        }
        if ($config['debug']) {
            (new Debug)->process($rules, $params, $index, $times); //记录实际执行流程
        }
        return $return;
    }

    //关闭路由器并执行回调
    public static function off(Closure $Closure = null)
    {
        self::$status = 1;
        if ($Closure) {
            return $Closure();
        }
        return true;
    }
    //获得路由规则创建器
    public static function getCreator(): object
    {
        return new Creator;
    }
    public static function reset(): bool
    {
        self::$status = 0;
        Creator::reset();
        return true;
    }

    public static function clearCache(string $file): bool
    {

    }
}
