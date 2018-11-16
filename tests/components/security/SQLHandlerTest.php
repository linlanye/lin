<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-28 10:55:02
 * @Modified time:      2018-09-28 15:14:14
 * @Depends on Linker:  None
 * @Description:        测试使用sql存储安全校验数据
 */
namespace lin\tests\components;

use Linker;
use lin\security\structure\handler\SQLHandler;
use PHPUnit\Framework\TestCase;

class SQLHandlerTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;

    private $Handler;
    public static function setUpBeforeClass()
    {
        self::createDB('security');
    }
    public function setUp()
    {
        $config        = Linker::Config()::lin('security');
        $this->Handler = new SQLHandler($config['server']['sql'], $config['gc']);
    }

    /**
     * @group sleep
     * @group server
     */
    public function testRW()
    {
        //测试写正式客户端
        $data1 = mt_rand();
        $this->Handler->write(['client' => $data1]);
        $r = $this->Handler->read('client');
        $this->assertSame($r, $data1);

        $data2 = mt_rand();
        $this->Handler->write(['client' => $data2]); //读写两次，
        $r = $this->Handler->read('client');
        $this->assertSame($r, $data2);
        $this->assertNotSame($data1, $data2);

        $r = $this->Handler->read('none');
        $this->assertSame($r, []);

        //测试临时客户端
        $tmp1     = '_tmp_' . md5(mt_rand());
        $tmp2     = '_tmp_' . md5(mt_rand());
        $tmp_data = md5(mt_rand());

        $this->Handler->writeTmp($tmp1, $tmp_data);
        $r = $this->Handler->read($tmp1);
        $this->assertSame($r, $tmp_data);

        $this->Handler->writeTmp($tmp1, null); //等效于删除
        $r = $this->Handler->read($tmp1);
        $this->assertSame($r, []);

        //测试gc
        $this->Handler->writeTmp($tmp1, $tmp_data);
        sleep(2);
        $this->Handler->writeTmp($tmp2, $tmp_data);
        $r = $this->Handler->read($tmp1);
        $this->assertSame($r, []);

    }

}
