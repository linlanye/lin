<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-01-03 17:43:41
 * @Modified time:      2018-10-23 13:16:48
 * @Depends on Linker:  Config
 * @Description:        KV服务器，通过Redis类实现，采用hash方式，跟据键名散列到不同的服务器
 */
namespace lin\basement\server\kv;

use Linker;
use lin\basement\server\kv\structure\Debug;
use lin\basement\server\structure\HashServer;
use Redis;
use Throwable;

class KVRedis extends HashServer
{
    /*****basement*****/
    use \basement\ServerKv;
    public function get(string $key)
    {
        $key  = $this->prefix . $key;
        $time = microtime(true);
        $this->chooseDriver($key);

        $value = $this->Driver->get($key);
        $r     = $value !== false;

        if ($this->Debug) {
            $this->Debug->handleGet($key, $value, microtime(true) - $time, $this->index, $r);
        }
        if ($r) {
            return unserialize($value);
        }
        return null;
    }
    public function set(string $key, $value, int $expire = 0): bool
    {
        if ($expire < 0) {
            return $this->delete($key);
        }
        if (!$expire) {
            $expire = $this->maxLife;
        }
        $key  = $this->prefix . $key;
        $time = microtime(true);
        $this->chooseDriver($key);
        $value = serialize($value);
        $r     = $expire > 0 ? $this->Driver->setEx($key, $expire, $value) : $this->Driver->set($key, $value);

        if ($this->Debug) {
            $this->Debug->handleSet($key, $value, $expire, microtime(true) - $time, $this->index, $r);
        }
        return $r;
    }
    //释放缓存
    public function delete(string $key): bool
    {
        $key  = $this->prefix . $key;
        $time = microtime(true);
        $this->chooseDriver($key);
        $r = $this->Driver->delete($key);

        if ($this->Debug) {
            $this->Debug->handleDelete($key, microtime(true) - $time, $this->index, $r);
        }
        return $r;
    }
    //缓存是否存在
    public function exists(string $key): bool
    {
        $key = $this->prefix . $key;
        $this->chooseDriver($key);
        $r = $this->Driver->get($key) !== false;

        return $r;
    }

    //只对当前服务器进行清空
    public function flush(): bool
    {
        $time = microtime(true);

        if ($this->Driver) {
            $r     = $this->Driver->flushDb();
            $index = $this->index;
        } else {
            $r     = false;
            $index = 'null';
        }

        if ($this->Debug) {
            $this->Debug->handleFlush(microtime(true) - $time, $index, $r);
        }
        return $r;
    }
    /*****************/

    private $prefix;
    private $maxLife;
    public function __construct($DriverOrIndex = null)
    {
        $config        = Linker::Config()::get('lin')['server']['kv']; //kv组件配置
        $this->maxLife = $config['default']['life'];
        $this->Debug   = $config['debug'] ? new Debug : null;
        $this->prefix  = $config['prefix']; //前缀
        $this->init('redis', $DriverOrIndex, $config['driver']['redis']['use']);
    }

    //根据使用的服务器索引获得驱动
    protected function newConnection(array $config, $index):  ? object
    {
        $Redis = new Redis();
        try {
            $r = $Redis->connect($config['host'], $config['port'], $config['timeout']); //不同客户端可能会抛异常错误
        } catch (Throwable $e) {
            $r = false;
        }
        if ($r && $config['pwd']) {
            $r = $Redis->auth($config['pwd']);
        }
        if (!$r) {
            return null; //kv属不可靠数据服务，连接失败无需中断
        }
        return $Redis;
    }

}
