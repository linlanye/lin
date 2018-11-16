<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-21 20:26:45
 * @Modified time:      2018-11-06 22:58:49
 * @Depends on Linker:  Config
 * @Description:        高效日志类，提供三种服务器写入，并且只在脚本运行完后批量写入。
 *                      注：（在析构函数或register_shutdown_function里调用本类可能出现无法写入）
 */
namespace lin\basement\log;

use Linker;
use lin\basement\log\structure\Debug;
use lin\basement\log\structure\LocalHandler;
use lin\basement\log\structure\QueueHandler;
use lin\basement\log\structure\SQLHandler;

class Log
{
    /*****basement*****/
    use \basement\Log;
    public function record(string $data, string $type): bool
    {
        return $this->handleRecord($data, $type);
    }
    public function debug(string $data): bool
    {
        return $this->record($data, 'debug');
    }
    public function info(string $data): bool
    {
        return $this->record($data, 'info');
    }
    public function notice(string $data): bool
    {
        return $this->record($data, 'notice');
    }
    public function warning(string $data): bool
    {
        return $this->record($data, 'warning');
    }
    public function error(string $data): bool
    {
        return $this->record($data, 'error');
    }
    public function critical(string $data): bool
    {
        return $this->record($data, 'critical');
    }
    public function alert(string $data): bool
    {
        return $this->record($data, 'alert');
    }
    public function emergency(string $data): bool
    {
        return $this->record($data, 'emergency');
    }
    /******************/

    private $Driver;
    private $debug;
    private static $init;
    private static $data = [];

    public function __construct($logName = null)
    {
        $config       = Linker::Config()::get('lin')['log'];
        $this->__name = $logName ?: $config['default']['name'];
        $this->debug  = $config['debug'];

        if (!self::$init) {
            self::$init = 1;
            register_shutdown_function(['\\lin\\basement\\log\\Log', '_shutdown_write_H7alziM1dsOz0d84'], $config); //注册日志批量写
        }
    }
    //脚步运行完后用，乱码为防止用户调用
    public static function _shutdown_write_H7alziM1dsOz0d84($config)
    {
        if (empty(self::$data)) {
            return true;
        }
        switch ($config['use']) {
            case 'sql':
                $Driver = new SQLHandler;
                $config = $config['server']['sql'];
                break;
            case 'queue':
                $Driver = new QueueHandler;
                $config = $config['server']['queue'];
                break;
            default:
                $Driver = new LocalHandler;
                $config = $config['server']['local'];
                break;
        }
        return $Driver->write(self::$data, $config);
    }
    private function handleRecord($data, $type)
    {
        if ($this->debug) {
            Debug::handle($this->__name, $type);
        }
        self::$data[] = [$this->__name, $type, $data, time()];
        return true;
    }
}
