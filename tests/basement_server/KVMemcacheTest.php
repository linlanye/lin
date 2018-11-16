<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-09-03 15:13:18
 * @Depends on Linker:  Config
 * @Description:        windows下memcache测试
 */
namespace lin\tests\basement_server;

use Linker;
use lin\basement\server\kv\KVMemcache;
use lin\tests\basement_server\TestForMemcached;

/**
 * @requires extension memcache
 */
class KVMemcacheTest extends TestForMemcached
{
    protected function getDriver($DriverOrIndex = null)
    {
        return new KVMemcache($DriverOrIndex);
    }

}
