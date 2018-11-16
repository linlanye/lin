<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-06-16 12:52:02
 * @Modified time:      2018-08-29 16:58:58
 * @Depends on Linker:  None
 * @Description:        对环状一致性hash服务器进行测试，每一种情况都需运行在单独进程
 */
namespace lin\tests\basement_server;

use Exception;
use Linker;
use lin\basement\server\structure\HashServer;
use PHPUnit\Framework\TestCase;
use stdClass;

class HashServerTest extends TestCase
{
    /**
     * 测试全部可用
     * @runInSeparateProcess
     */
    public function testScenario1()
    {
        $Driver = new MockHashServer(1);

        $Driver->set('2'); //长度1，映射到第2个服务器，环状顺时针寻找
        $this->assertSame($Driver->getIndex(), 'valid2');

        $Driver->set('3--'); //长度3，映射到第3个服务器，
        $this->assertSame($Driver->getIndex(), 'valid3');

        $Driver->set('4---'); //长度4，映射到第4个服务器，
        $this->assertSame($Driver->getIndex(), 'valid4');

        $Driver->set('1----'); //长度5，映射到第1个服务器,
        $this->assertSame($Driver->getIndex(), 'valid1');

    }
    /**
     * 测试部分服务器可用
     * @runInSeparateProcess
     */
    public function testScenario2()
    {
        $Driver = new MockHashServer(2);

        $Driver->set('2'); //长度1，映射到第2个服务器，但其不可用，应该到第3个
        $this->assertSame($Driver->getIndex(), 'valid3');

        $Driver->set('3--'); //长度3，映射到第3个服务器，
        $this->assertSame($Driver->getIndex(), 'valid3');

        $Driver->set('4---'); //长度4，映射到第4个服务器，但其不可用，应该到第1个
        $this->assertSame($Driver->getIndex(), 'valid1');

        $Driver->set('1----'); //长度5，映射到第1个服务器,
        $this->assertSame($Driver->getIndex(), 'valid1');

    }
    /**
     * 测试所有服务器不可用
     * @runInSeparateProcess
     */
    public function testScenario3()
    {
        $Driver = new MockHashServer(3);

        //无服务器可用，应抛出异常
        try {
            $Driver->set('2');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $Driver->set('3--');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);

        }

        try {
            $Driver->set('4---');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $Driver->set('1----');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

    }

    /**
     * 测试单服务器可用
     * @runInSeparateProcess
     */
    public function testScenario4()
    {
        $Driver = new MockHashServer(4);
        $Driver->set(mt_rand(1, PHP_INT_MAX)); //随机长度
        $this->assertSame($Driver->getIndex(), 'valid1');

    }
    /**
     * 测试单服务器不可用
     * @runInSeparateProcess
     */
    public function testScenario5()
    {
        try {
            $Driver = new MockHashServer(5);
            $Driver->set(mt_rand(1, PHP_INT_MAX)); //随机长度
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }
}

//模拟hash服务器
class MockHashServer extends HashServer
{

    public function __construct($scenario = 1)
    {
        switch ($scenario) {
            case 1: //所有服务器都可以用
                $data = [
                    'valid1' => ['weight' => 1],
                    'valid2' => ['weight' => 2],
                    'valid3' => ['weight' => 1],
                    'valid4' => ['weight' => 1],
                ];
                break;
            case 2: //部分服务器可用
                $data = [
                    'valid1'   => ['weight' => 1], //可用
                    'invalid2' => ['weight' => 2], //不可用
                    'valid3'   => ['weight' => 1],
                    'invalid4' => ['weight' => 1],
                ];
                break;
            case 3: //所有服务器不可用
                $data = [
                    'invalid1' => ['weight' => 1],
                    'invalid2' => ['weight' => 2],
                    'invalid3' => ['weight' => 1],
                    'invalid4' => ['weight' => 1],
                ];
                break;
            case 4: //单服务器可用
                $data = [
                    'valid1' => ['weight' => 1],
                ];
                break;
            case 5: //单服务器不可用
                $data = [
                    'invalid1' => ['weight' => 1],
                ];
                break;
        }
        Linker::Config()::servers(['hashServer' => $data]); //专用配置
        $this->init('hashServer', null, '*');
    }

    //设置键
    public function set($key)
    {
        $this->chooseDriver($key);
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

    protected function getHash($key)
    {
        $unit = self::MAX_INT / 5; //hash区间分离成5等分
        return intval((strlen($key) % 5) * $unit); //根据长度来简单映射
    }

    public function __destruct()
    {
        Linker::Config()::servers(['hashServer' => null]);
    }
}
