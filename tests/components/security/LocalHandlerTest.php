<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-28 10:55:38
 * @Modified time:      2018-09-28 14:56:57
 * @Depends on Linker:  None
 * @Description:        测试使用本地文件存储安全校验数据
 */
namespace lin\tests\components;

use Linker;
use lin\security\structure\handler\LocalHandler;
use PHPUnit\Framework\TestCase;

class LocalHandlerTest extends TestCase
{
    private $Handler;
    private $path;
    public function setUp()
    {
        $config        = Linker::Config()::lin('security');
        $this->Handler = new LocalHandler($config['server']['local'], $config['gc']);
        $this->path    = rtrim($config['server']['local']['path'], '/') . '/';
    }

    /**
     * @group sleep
     */
    public function testRW()
    {
        //测试写正式客户端
        $filename = md5(mt_rand());
        $data     = mt_rand();
        $this->Handler->write([$filename => $data]);
        $r = $this->Handler->read($filename);
        $this->assertSame($r, $data);

        $r = $this->Handler->read('none');
        $this->assertSame($r, []);
        unlink($this->path . $filename);

        //测试临时客户端
        $tmp1     = '_tmp_' . md5(mt_rand());
        $tmp2     = '_tmp_' . md5(mt_rand());
        $tmp_data = md5(mt_rand());
        $r        = $this->Handler->writeTmp($tmp1, $tmp_data);
        $this->assertTrue(file_exists($this->path . $tmp1)); //文件存在
        $r = $this->Handler->read($tmp1);
        $this->assertSame($r, $tmp_data); //内容读取一致

        $this->Handler->writeTmp($tmp1, null);
        $this->assertTrue(file_exists($this->path . $tmp1)); //无内容，文件被删除

        //测试gc
        $this->Handler->writeTmp($tmp1, $tmp_data);
        sleep(2);
        $this->Handler->writeTmp($tmp2, $tmp_data);
        $this->assertFalse(file_exists($this->path . $tmp1));
        unlink($this->path . $tmp2);
        rmdir($this->path);
    }

}
