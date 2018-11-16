<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-12-26 14:50:55
 * @Modified time:      2018-08-29 16:58:27
 * @Depends on Linker:  Config
 * @Description:        memcached基类，采用php扩展的memcache和memcached类内置的多服务器映射方式
 */
namespace lin\basement\server\kv\structure;

use Linker;
use lin\basement\server\kv\structure\Debug;

abstract class BaseMemcached
{
    use \lin\basement\server\structure\CommonTrait;

    protected $prefix;
    protected $maxLife;

    public function __construct($DriverOrIndex = null)
    {
        $config        = Linker::Config()::get('lin')['server']['kv']; //kv组件配置
        $this->maxLife = $config['default']['life'];
        $this->Debug   = $config['debug'] ? new Debug : null;
        $this->prefix  = $config['prefix']; //前缀
        $this->init($DriverOrIndex, $config['driver']['memcached']['use']);
    }

    public function setPrefix($prefix): bool
    {
        $this->prefix = $prefix;
        return true;
    }

    private function init($DriverOrIndex, $configIndex)
    {
        $this->serverName = 'memcached';

        //1.外部指定实例
        if (is_object($DriverOrIndex)) {
            $this->Driver = $DriverOrIndex;
            $this->index  = 'user'; //标记为用户指定的驱动
            return;
        }

        //2.获得指定服务器索引
        if (is_string($DriverOrIndex) || is_int($DriverOrIndex)) {
            $configIndex = $DriverOrIndex; //优先级更高
        }
        if (!isset(self::$servers[$configIndex])) {
            $this->parseServerIndex($configIndex); //解析可使用的服务器索引
        }

        $this->Driver = self::$servers[$configIndex]['driver'];
        $this->index  = self::$servers[$configIndex]['index'];

    }

    private function parseServerIndex($rawIndex)
    {
        //解析可使用的服务器索引
        $servers    = Linker::Config()::get('servers')[$this->serverName];
        $indexArray = $this->parseRawIndex($servers, $rawIndex);

        $configs                  = array_intersect_key($servers, $indexArray);
        $index                    = implode(',', array_keys($indexArray)); //连接在一起作为使用的索引
        self::$servers[$rawIndex] = ['driver' => $this->newConnection($configs, $index), 'index' => $index];
    }
}
