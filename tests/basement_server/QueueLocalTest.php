<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-09-05 09:06:05
 * @Depends on Linker:  None
 * @Description:        测试本地文件模拟队列服务器
 */
namespace lin\tests\basement_server;

use Linker;
use lin\basement\server\queue\QueueLocal;
use PHPUnit\Framework\TestCase;
use stdclass;

class QueueLocalTest extends TestCase
{
    use \lin\tests\traits\RemoveTrait;

    private $Driver;
    private $data = [
        '数据', '总共4个', '，总长度超过', '20个字符',
    ];

    public static function tearDownAfterClass()
    {
        //删除队列文件
        $queue_dir = Linker::Config()::lin('server.queue.driver.local.path');
        self::rmdir($queue_dir);
    }

    protected function setUp()
    {
        $this->Driver = new QueueLocal;
    }
    protected function tearDown()
    {
        $this->Driver->close(); //关闭所有队列文件
    }
    public function testGetName()
    {
        //默认队列名
        $defaultName = Linker::Config()::lin('server.queue.default.name');
        $this->assertSame($this->Driver->getName(), $defaultName);

        //设置队列名
        $name = md5(mt_rand());
        $this->Driver->setName($name);
        $this->assertSame($this->Driver->getName(), $name);

        //初始化队列名
        $name   = md5(mt_rand());
        $Driver = new QueueLocal($name);
        $this->assertSame($Driver->getName(), $name);
    }

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

    //测试冗余文件自动整理
    public function testMaintain()
    {
        $max_len = strlen(implode('', $this->data));
        $this->Driver->setThreshold($max_len); //设置最大冗余大小
        $this->Driver->setName('new'); //新队列
        $this->Driver->multiPush($this->data); //数据
        $queue_dir = Linker::Config()::lin('server.queue.driver.local.path');

        //新文件断言
        $current = (int) file_get_contents($queue_dir . '/new.cursorlq');
        $this->assertTrue($current === 0);
        $content = file_get_contents($queue_dir . '/new.lq');
        $this->assertTrue(strlen($content) > 20); //总数据长度大于20

        //未重整文件前断言
        $this->Driver->pop();
        $current = (int) file_get_contents($queue_dir . '/new.cursorlq');
        $this->assertTrue($current > 0);

        //断言整理文件后
        $this->Driver->pop(count($this->data)); //弹出所有文件
        $current = (int) file_get_contents($queue_dir . '/new.cursorlq');
        $this->assertTrue($current === 0);
        $content = file_get_contents($queue_dir . '/new.lq');
        $this->assertTrue(strlen($content) < $max_len);

        /****测试冗余文件手动整理****/
        $this->Driver->setThreshold(PHP_INT_MAX);
        $this->Driver->setName('new2'); //新队列2
        $this->Driver->multiPush($this->data); //存入又取出数据
        $this->Driver->pop($max_len);

        $this->Driver->setName('new3'); //新队列3
        $this->Driver->multiPush($this->data); //数据
        $this->Driver->pop($max_len);

        //未整理前
        $current = (int) file_get_contents($queue_dir . '/new2.cursorlq');
        $this->assertTrue($current > $max_len);
        $current = (int) file_get_contents($queue_dir . '/new3.cursorlq');
        $this->assertTrue($current > $max_len);

        //整理后
        $this->Driver->maintain(true);
        $current = (int) file_get_contents($queue_dir . '/new2.cursorlq');
        $this->assertTrue($current == 0);
        $current = (int) file_get_contents($queue_dir . '/new3.cursorlq');
        $this->assertTrue($current == 0);
    }

    private function clean()
    {
        while (!$this->Driver->isEmpty()) {
            $this->Driver->pop(10000);
        }
    }
}
