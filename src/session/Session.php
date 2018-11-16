<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-19 09:06:46
 * @Modified time:      2018-09-03 14:04:02
 * @Depends on Linker:  Config
 * @Description:        session处理
 */
namespace lin\session;

use Linker;
use lin\session\structure\KVHandler;
use lin\session\structure\SQLHandler;

class Session
{
    private static $status = 0;
    public static function run(): void
    {
        if (self::$status) {
            return; //已运行
        }

        $config  = Linker::Config()::get('lin')['session']; //一旦运行后，配置则不可再更改
        $use     = $config['use'];
        $current = $config['server'][$use]; //当前处理器配置
        $life    = (int) $config['life'];

        switch ($use) {
            case 'local':
                $path = rtrim($current['path'], '/') . '/'; //存放路径
                if (!file_exists($path) && !mkdir($path, 0750, true)) {
                    Linker::Exception()::throw ('目录创建失败', 1, 'Session', $path);
                }
                session_save_path($current['deep'] . ';' . $path);
                ini_set('session.gc_maxlifetime', $config['life']);
                break;
            default:
                switch ($use) {
                    case 'kv':
                        $Handler = new KVHandler($current, $config['life']);
                        break;
                    case 'sql':
                        $Handler = new SQLHandler($current, $config['life']);
                        break;
                    case 'custom':
                        $Handler = new $current;
                        break;
                }
                session_set_save_handler($Handler);
                break;
        }
        session_start();
        self::$status = 1;
    }
}
