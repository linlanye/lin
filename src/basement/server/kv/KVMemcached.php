<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-01-03 17:45:36
 * @Modified time:      2018-08-29 16:58:27
 * @Depends on Linker:  Config
 * @Description:        kv服务器，通过memcached类实现
 */
namespace lin\basement\server\kv;

use lin\basement\server\kv\structure\BaseMemcached;
use Memcached;

class KVMemcached extends BaseMemcached
{
    /*****basement*****/
    use \basement\ServerKv;
    public function get($key)
    {
        $key     = $this->prefix . $key;
        $time    = microtime(true);
        $value   = $this->Driver->get($key);
        $success = $this->Driver->getResultCode() !== Memcached::RES_NOTFOUND;
        if ($this->Debug) {
            $this->Debug->handleGet($key, $value, microtime(true) - $time, $this->index, $success);
        }
        if ($success) {
            return $value;
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
        $r    = $this->Driver->set($key, $value, $expire);
        if ($this->Debug) {
            $this->Debug->handleSet($key, $value, $expire, microtime(true) - $time, $this->index, $r);
        }
        return $r;
    }
    //删除键值
    public function delete(string $key): bool
    {
        $key  = $this->prefix . $key;
        $time = microtime(true);
        $r    = $this->Driver->delete($key);
        if ($this->Debug) {
            $this->Debug->handleDelete($key, microtime(true) - $time, $this->index, $r);
        }
        return $r;
    }
    //键值是否存在
    public function exists(string $key): bool
    {
        $key = $this->prefix . $key;
        $this->Driver->get($key);
        return $this->Driver->getResultCode() !== Memcached::RES_NOTFOUND;
    }
    public function flush(): bool
    {
        $time = microtime(true);
        $r    = $this->Driver->flush();
        if ($this->Debug) {
            $this->Debug->handleFlush(microtime(true) - $time, $this->index, $r);
        }
        return $r;
    }
    /*****************/

    protected function newConnection(array $configs, $index = null):  ? object
    {
        $Driver  = new Memcached;
        $servers = [];
        foreach ($configs as $config) {
            $servers[] = [$config['host'], $config['port'], $config['weight']];
        }
        $Driver->addServers($servers);
        $Driver->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT); //设置使用一致性hash
        $Driver->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $Driver->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS, true); //移除故障服务器
        return $Driver;
    }

}
