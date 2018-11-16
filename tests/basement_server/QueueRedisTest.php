<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-08-29 16:59:03
 * @Depends on Linker:  Config
 * @Description:        默认本地redis服务可用，该测试才有效
 */
namespace lin\tests\basement_server;

use Linker;
use lin\basement\server\queue\QueueRedis;
use PHPUnit\Framework\TestCase;
use stdclass;

/**
 * @requires extension redis
 */
class QueueRedisTest extends TestCase
{

    private $Driver;
    protected function setUp()
    {
        $this->Driver = new QueueRedis;
    }
    protected function tearDown()
    {
        $this->clean();
    }
    public function testGetName()
    {
        $defaultName = Linker::Config()::lin('server.queue.default.name');
        $this->assertSame($this->Driver->getName(), $defaultName);

        $name = md5(mt_rand());
        $this->Driver->setName($name);
        $this->assertSame($this->Driver->getName(), $name);
    }

    /**
     * @group server
     */
    public function testPop()
    {
        //测试无数据
        $this->clean();
        $this->assertNull($this->Driver->pop());

        //测试标量
        $v = mt_rand();
        $this->Driver->push($v);
        $this->assertSame($this->Driver->pop()[0], $v);

        //测试存入对象
        $object       = new stdclass;
        $object->data = mt_rand();
        $this->Driver->push($object);
        $object2 = $this->Driver->pop()[0];
        $this->assertSame($object2->data, $object->data);

        //测试存入数组
        $array = [mt_rand() => mt_rand()];
        $this->Driver->push($array);
        $this->assertSame($this->Driver->pop()[0], $array);

        //测试批量存入
        $multiData = [mt_rand(), [mt_rand()], 3, null, false];
        $this->Driver->multiPush($multiData);
        $this->assertSame($this->Driver->pop(5), $multiData);

    }

    /**
     * @group server
     */
    public function testIsEmpty()
    {
        //另一个队列不为空
        $old_name = $this->Driver->getName();
        $name     = mt_rand();
        $this->Driver->setName($name);
        $this->Driver->push(mt_rand());
        $this->assertFalse($this->Driver->isEmpty());

        //测试当前队列为空
        $this->Driver->setName($old_name);
        $this->Driver->push(mt_rand());
        $this->assertFalse($this->Driver->isEmpty());
        $this->clean();
        $this->assertTrue($this->Driver->isEmpty());

        //测试当前队列为空,另一个队列不为空
        $this->Driver->setName($name);
        $this->assertFalse($this->Driver->isEmpty());
        $this->clean();
        $this->assertTrue($this->Driver->isEmpty());
    }

    /**
     * @group server
     */
    public function testGetSize()
    {
        $this->assertEquals($this->Driver->getSize(), 0);
        $this->Driver->multiPush([mt_rand(), mt_rand(), mt_rand()]);
        $this->assertEquals($this->Driver->getSize(), 3);
        $this->Driver->pop(3);
        $this->assertTrue($this->Driver->isEmpty());

        //测试数据文件不存在时
        $this->Driver->setName('none');
        $this->assertEquals($this->Driver->getSize(), 0);
    }

    public function testUserIndexAndDriver()
    {
        //测试指定索引
        $Driver = new QueueRedis('queue0');
        $this->assertSame('queue0', $Driver->getIndex());

        //测试外部指定驱动
        $UserDriver = new stdclass;
        $Driver     = new QueueRedis($UserDriver);

        $this->assertSame('user', $Driver->getIndex());
        $this->assertSame($UserDriver, $Driver->getDriver());
        $this->assertSame('redis', $Driver->getServerName());
    }

    private function clean()
    {
        while (!$this->Driver->isEmpty()) {
            $this->Driver->pop(10000);
        }
    }
}
