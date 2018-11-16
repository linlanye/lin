<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-09-28 15:28:09
 * @Depends on Linker:  None
 * @Description:
 */
namespace lin\tests\basement_server;

use Exception;
use Linker;
use lin\basement\server\kv\KVLocal;
use PHPUnit\Framework\TestCase;

class KVLocalTest extends TestCase
{
    use \lin\tests\traits\RemoveTrait;
    private $Driver;

    //注意多个线程运行，也会多次运行该方法
    public static function setUpBeforeClass()
    {
        KVLocal::setMaxParameters(['file_size' => 40, 'hash' => 10, 'scan_time' => 0.1]);
        KVLocal::setBlockParameters(['size' => 10, 'factor' => 2]);
    }
    public static function tearDownAfterClass()
    {
        $kv_dir = Linker::Config()::lin('server.kv.driver.local.path');
        KVLocal::setMaxParameters(['file_size' => 1000]);
        KVLocal::setBlockParameters(['size' => 1000, 'factor' => 2]); //后续测试还要用到大数据，size需变大
        self::rmdir($kv_dir);
    }
    protected function setUp()
    {
        $this->Driver = new KVLocal;
    }
    protected function tearDown()
    {
        $this->Driver->close(); //关闭
    }

    /**
     * 测试非冲突情况下，写入数据超限，需放在首位测试，避免冲突
     * @group server
     */
    public function testException()
    {
        try {
            $this->Driver->set(mt_rand(), sha1('dah') . sha1('dah'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

    }

    /**
     * @group sleep
     */
    public function testGet()
    {
        $this->assertNull($this->Driver->get('none'));

        //默认过期时间
        $v = mt_rand();
        $this->Driver->set('defaultLife', $v);
        $this->assertSame($this->Driver->get('defaultLife'), $v);

        //测试最大过期时间
        $v = mt_rand();
        $this->Driver->set('maxLife', $v, time() + 1);
        $this->assertSame($this->Driver->get('maxLife'), $v);

        //自定义过期
        $v = mt_rand();
        $this->Driver->set('userLife', $v, 1);
        $this->assertSame($this->Driver->get('userLife'), $v);

        //测试过期
        sleep(2);
        $this->assertNull($this->Driver->get('userLife'));

        sleep(1);
        $this->assertNull($this->Driver->get('defaultLife'));

        sleep(1);
        $this->assertNull($this->Driver->get('maxLife'));
    }

    /**
     * @group server
     */
    public function testExitsDelete()
    {
        $key = md5(mt_rand());
        $v   = mt_rand();

        //测试存在
        $this->Driver->set($key, $v);
        $this->assertTrue($this->Driver->exists($key));

        //测试删除
        $this->assertTrue($this->Driver->delete($key));
        $this->assertFalse($this->Driver->exists($key));
        $this->assertFalse($this->Driver->delete($key));
    }

    /**
     * 测试并发
     * @group server
     * @runInSeparateProcess
     */
    public function testSetGet()
    {
        for ($i = 0; $i < 1000; $i++) {
            $key = md5(mt_rand());
            $v   = mt_rand();

            $this->Driver->set($key . $i, $v);
            $this->assertSame($this->Driver->get($key . $i), $v);
        }
    }
    /**
     * 测试并发
     * @group server
     * @runInSeparateProcess
     */
    public function testSetGet2()
    {
        for ($i = 0; $i < 1000; $i++) {
            $key = md5(mt_rand());
            $v   = mt_rand();

            $this->Driver->set($key . $i, $v);
            $this->assertSame($this->Driver->get($key . $i), $v);
        }
    }
    /**
     * @group server
     */
    public function testFlush()
    {
        $key = md5(mt_rand());
        $v   = mt_rand();
        $this->Driver->set($key, $v);
        $this->assertTrue($this->Driver->exists($key));

        $this->Driver->flush();
        $this->assertFalse($this->Driver->exists($key));
    }
}
