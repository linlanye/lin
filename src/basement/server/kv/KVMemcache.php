<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-01-03 17:45:11
 * @Modified time:      2018-08-29 16:58:26
 * @Depends on Linker:  None
 * @Description:        kv服务器，通过Memcache类实现
 */
namespace lin\basement\server\kv;

use lin\basement\server\kv\structure\BaseMemcached;
use Memcache;

class KVMemcache extends BaseMemcached
{
    /*****basement*****/
    use \basement\ServerKv;
    public function get(string $key)
    {
        $key     = $this->prefix . $key;
        $time    = microtime(true);
        $flag    = false;
        $value   = $this->Driver->get($key, $flag);
        $success = $flag !== false;
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
        $r    = $this->Driver->set($key, $value, MEMCACHE_COMPRESSED, $expire);
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
        $key    = $this->prefix . $key;
        $exists = false;
        $this->Driver->get($key, $exists);
        return $exists !== false;
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

    protected function newConnection(array $configs, $index):  ? object
    {
        $Driver = new Memcache;
        foreach ($configs as $config) {
            $Driver->addServer($config['host'], $config['port'], true, $config['weight']);
        }
        return $Driver; //memecached扩展未提供连接测试，所以不返回null
    }
}
