<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-12-19 22:10:50
 * @Modified time:      2018-11-05 16:03:04
 * @Depends on Linker:  Config Exception
 * @Description:        事件监听处理
 */
namespace lin\basement\event;

use Linker;
use lin\basement\event\structure\Debug;

class Event
{
    private static $config;

    /*****basement*****/
    use \basement\Event;
    //触发事件
    public static function trigger(string $event, ...$params)
    {
        if (!isset(self::$data[$event])) {
            return null;
        }
        $t = microtime(true);
        $r = call_user_func_array(self::$data[$event]['C'], $params);
        self::$data[$event]['t']--; //减少事件执行次数,不影响t=0的无限执行

        if (self::$data[$event]['t'] === 0) {
            unset(self::$data[$event]);
        }

        if (self::$config['debug']) {
            Debug::trigger($event, $params, microtime(true) - $t);
        }
        return $r;
    }
    /**
     * 绑定事件
     * @param  string   $event    事件名
     * @param  callable $callable 事件回调
     * @param  int      $times    事件可执行次数
     */
    public static function on(string $event, callable $callable, int $times = -1): bool
    {
        $times == 0 and $times = -1;
        self::$data[$event]    = ['C' => $callable, 't' => $times];
        return true;
    }
    //解绑事件
    public static function off(string $event): bool
    {
        unset(self::$data[$event]);
        return true;
    }
    public static function exists(string $event): bool
    {
        return array_key_exists($event, self::$data);
    }
    /******************/

    protected static $data = []; //注册的事件列表
    private static $status = 0; //初始化标记

    //清除所有事件
    public static function clean(string $event = ''): bool
    {
        if ($event) {
            unset(self::$data[$event]);
        } else {
            self::$data = [];
        }
        return true;
    }
    public static function reset(): bool
    {
        self::$data   = [];
        self::$status = 0;
        return true;
    }

    //绑定一次性事件
    public static function one(string $event, callable $callable): bool
    {
        self::on($event, $callable, 1);
        return true;
    }

    //启动事件
    public static function run( ? string $files = '*') : bool
    {
        if (self::$status || $files === null) {
            return false; //初始化启动
        }
        self::$status = 1;

        $t            = microtime(true);
        self::$config = Linker::Config()::get('lin')['event'];
        $path         = rtrim(self::$config['path'], '/') . '/';
        $files        = explode(',', $files);
        foreach ($files as &$file) {
            $file = trim($file);
            $end  = substr($file, -1);
            if ($end == '/') {
                $file .= '*'; //末尾为目录符号，加入通配符
            } else if ($end != '*') {
                $file .= '.php'; //末尾非通配符，加入后缀
            }
            $file = $path . ltrim($file, '/'); //生成完整的文件名
        }
        //获得目标规则文件
        $tmp_files = $files; //debug用
        $files     = self::scanDir($files);
        if (!$files) {
            Linker::Exception()::throw ('文件不存在', 1, 'Event', implode(', ', $tmp_files));
        }

        //加载规则文件
        $time  = [];
        $debug = self::$config['debug'];
        foreach ($files as $file) {
            include $file;
            if ($debug) {
                $t1     = microtime(true);
                $time[] = $t1 - $t;
                $t      = $t1;
            }
        }

        if ($debug) {
            Debug::run($files, count(self::$data), $time);
        }

        return true;
    }

    private static function scanDir($files)
    {
        $final_files = [];
        do {
            $current = glob(array_pop($files));
            foreach ($current as $file) {
                if (is_dir($file)) {
                    array_push($files, rtrim($file, '/') . '/*'); //属于目录则压栈递归
                } else {
                    $final_files[] = $file;
                }
            }
        } while ($files);
        return array_unique($final_files); //去重复
    }
}
