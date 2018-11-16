<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-08-29 16:59:04
 * @Depends on Linker:  Config
 * @Description:        默认本地memcached服务可用，该测试才有效，键值生命期默认2s，见配置文件
 *                      并替代测试CommonTrait
 */
namespace lin\tests\basement_server;

use Linker;
use PHPUnit\Framework\TestCase;
use stdclass;

class TestForMemcached extends TestCase
{

    public static function tearDownAfterClass()
    {
        $Test   = new static;
        $Driver = $Test->getDriver();
        $Driver->flush();
    }

    /**
     * 基本测试
     * @group sleep
     * @group server
     */
    public function testGeneral()
    {
        $Driver = $this->getDriver();
        $Driver->flush();
        $this->assertNull($Driver->get('none'));

        $key = md5(mt_rand());
        $v   = mt_rand();
        $Driver->set($key, $v);
        $this->assertTrue($Driver->exists($key));
        $this->assertSame($Driver->get($key), $v);

        //测试默认过期
        sleep(3);
        $this->assertNull($Driver->get($key));

        //测试过期
        $Driver->set($key, $v, 1);
        sleep(2);
        $this->assertFalse($Driver->exists($key));

        //测试删除
        $Driver->set($key, $v);
        $this->assertTrue($Driver->delete($key));
        $this->assertFalse($Driver->exists($key));
        $this->assertFalse($Driver->delete($key));
    }

    public function testUserIndexAndDriver()
    {
        //测试指定索引
        $Driver = $this->getDriver('valid1');
        $this->assertSame('valid1', $Driver->getIndex());

        //测试外部指定驱动
        $UserDriver = new stdclass;
        $Driver     = $this->getDriver($UserDriver);

        $this->assertSame('user', $Driver->getIndex());
        $this->assertSame($UserDriver, $Driver->getDriver());
        $this->assertSame('memcached', $Driver->getServerName());
    }

    //补充测试CommonTrait的parseRawIndex方法,使用该类进行测试，是因为单个实例会使用全部索引
    public function testCommonTrait()
    {
        //测试parseRawIndex方法，验证其对使用索引的正确解析

        //涵盖所有索引和语法情形
        $Driver  = $this->getDriver('valid*, valid0,invalid*, invalid1');
        $Driver2 = $this->getDriver();
        $this->assertSame($Driver->getIndex(), $Driver2->getIndex());

        $Driver  = $this->getDriver('valid*, invalid*');
        $Driver2 = $this->getDriver('valid*');
        $this->assertSame($Driver->getIndex(), $Driver2->getIndex());

        $Driver  = $this->getDriver('valid*');
        $Driver2 = $this->getDriver('valid0, valid1');
        $this->assertSame($Driver->getIndex(), $Driver2->getIndex());

        //无可用服务器
        try {
            $this->getDriver('none'); //不存在none索引的服务器
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $this->getDriver('invalid0'); //服务器为权重0，
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    protected function getDriver($DriverOrIndex = null)
    {

    }

}
