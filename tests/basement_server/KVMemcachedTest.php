<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-09-03 15:13:14
 * @Depends on Linker:  Config
 * @Description:        unix下memcached测试
 */
namespace lin\tests\basement_server;

use Linker;
use lin\basement\server\kv\KVMemcached;
use lin\tests\basement_server\TestForMemcached;

/**
 * @requires extension memcached
 */
class KVMemcachedTest extends TestForMemcached
{
    protected function getDriver($DriverOrIndex = null)
    {
        return new KVMemcached($DriverOrIndex);
    }

}
