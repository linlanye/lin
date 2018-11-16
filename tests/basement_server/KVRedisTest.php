<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-08-29 16:59:01
 * @Depends on Linker:  Config
 * @Description:        默认本地redis服务可用，该测试才有效，键值生命期默认2s，见配置文件
 */
namespace lin\tests\basement_server;

use Linker;
use lin\basement\server\kv\KVRedis;
use PHPUnit\Framework\TestCase;
use stdclass;

/**
 * @requires extension redis
 */
class KVRedisTest extends TestCase
{

    private $Driver;

    protected function setUp()
    {
        $this->Driver = new KVRedis;
    }
    protected function tearDown()
    {
        $this->Driver->flush();
    }

    /**
     * 基本测试
     * @group sleep
     * @group server
     */
    public function testGeneral()
    {
        $this->Driver->flush();
        $this->assertNull($this->Driver->get('none'));

        $key = md5(mt_rand());
        $v   = mt_rand();
        $this->Driver->set($key, $v);
        $this->assertTrue($this->Driver->exists($key));
        $this->assertSame($this->Driver->get($key), $v);

        //测试默认过期
        sleep(3);
        $this->assertNull($this->Driver->get($key));

        //测试过期
        $this->Driver->set($key, $v, 1);
        sleep(2);
        $this->assertFalse($this->Driver->exists($key));

        //测试删除
        $this->Driver->set($key, $v);
        $this->assertTrue($this->Driver->delete($key));
        $this->assertFalse($this->Driver->exists($key));
        $this->assertFalse($this->Driver->delete($key));

    }
    public function testUserIndexAndDriver()
    {
        //测试外部指定索引
        $Driver = new KVRedis('kv0');
        $this->assertSame('kv0', $Driver->getIndex());

        //测试外部指定驱动
        $UserDriver = new stdclass;
        $Driver     = new KVRedis($UserDriver);

        $this->assertSame('user', $Driver->getIndex());
        $this->assertSame($UserDriver, $Driver->getDriver());
        $this->assertSame('redis', $Driver->getServerName());

    }

}
