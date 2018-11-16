<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-06-16 14:30:15
 * @Modified time:      2018-08-29 16:59:03
 * @Depends on Linker:  None
 * @Description:        对读写分离服务器进行测试，每个测试需运行在单独进程
 */
namespace lin\tests\basement_server;

use Exception;
use Linker;
use lin\basement\server\structure\RWSServer;
use PHPUnit\Framework\TestCase;
use stdClass;

class RWSServerTest extends TestCase
{

    /**
     * 测试不同权重都有可能被选中
     * @runInSeparateProcess
     */
    public function testScenario1()
    {
        //统计100次实例化，不同权重服务器都会被选中
        for ($i = 0; $i < 100; $i++) {
            $Driver                     = new MockRWSServer(1);
            $index[$Driver->getIndex()] = 1; //总是读服务器

        }
        $this->assertEquals(2, count($index));

        $this->assertTrue(isset($index['valid1']));
        $this->assertFalse(isset($index['valid2']));
        $this->assertTrue(isset($index['valid3']));

    }

    /**
     * 测试部分服务器可用，且小权重服务器不会被选中
     * @runInSeparateProcess
     */
    public function testScenario2()
    {
        //统计100次实例化,小权重服务器出现的概率约为千万分之一
        for ($i = 0; $i < 100; $i++) {
            $Driver                     = new MockRWSServer(2);
            $index[$Driver->getIndex()] = 1; //r和rw
        }
        $this->assertEquals(1, count($index));
        $this->assertFalse(isset($index['valid1']));
        $this->assertFalse(isset($index['valid2']));
        $this->assertTrue(isset($index['valid3']));
    }

    /**
     * 测试所有服务器不可用
     * @runInSeparateProcess
     */
    public function testScenario3()
    {
        //统计100次实例化,所有服务器都不可用
        for ($i = 0; $i < 100; $i++) {
            try {
                new MockRWSServer(3);
                $this->assertTrue(false);
            } catch (Exception $e) {
                $this->assertTrue(true);
            }
        }
    }
}

//模拟实现读写分离服务器
class MockRWSServer extends RWSServer
{
    public function __construct($scenario = 1)
    {
        switch ($scenario) {
            case 1: //所有服务器都可以用
                $data = [
                    'valid1' => ['weight' => 1, 'mode' => 'r'],
                    'valid2' => ['weight' => 1, 'mode' => 'w'],
                    'valid3' => ['weight' => 2, 'mode' => 'rw'],
                ];
                break;
            case 2: //小权重服务器不会被选中
                $data = [
                    'valid1' => ['weight' => 1, 'mode' => 'r'],
                    'valid2' => ['weight' => 1, 'mode' => 'w'],
                    'valid3' => ['weight' => 1000000000, 'mode' => 'rw'],
                ];
                break;
            case 3: //全部服务器不可用
                $data = [
                    'invalid1' => ['weight' => 1, 'mode' => 'r'],
                    'invalid2' => ['weight' => 1, 'mode' => 'w'],
                    'invalid3' => ['weight' => 2, 'mode' => 'rw'],
                ];
                break;
        }
        Linker::Config()::servers(['rwsServer' => $data]);
        $this->init('rwsServer', null, '*'); //专用配置
    }

    //模拟连接，当索引为invalid的时，连接不可用
    protected function newConnection(array $config, $index):  ? object
    {
        if (preg_match('/invalid/', $index)) {
            return null;
        }
        $Driver        = new stdClass;
        $Driver->index = $index;
        return $Driver;
    }

    public function __destruct()
    {
        Linker::Config()::servers(['rwsServer' => null]);
    }
}
