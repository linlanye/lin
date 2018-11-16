<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-08-29 16:59:01
 * @Depends on Linker:  None
 * @Description:        测试本地文件读写
 */
namespace lin\tests\basement_server;

use lin\basement\server\local\Local;
use PHPUnit\Framework\TestCase;

class LocalTest extends TestCase
{
    private $file     = 'localfile.test';
    private $contents = 'some contents';

    public function testRead()
    {
        $Driver = new Local;
        $Driver->write($this->file, $this->contents, 'w');
        $this->assertSame($Driver->read($this->file), $this->contents);
        return $Driver;
    }

    /**
     * 测试属性
     * @depends testRead
     */
    public function testAttribute($Driver)
    {
        //测试存在
        $this->assertTrue($Driver->exists($this->file));
        $this->assertTrue($Driver->exists('')); //文件所在目录
        $this->assertFalse($Driver->exists('none'));

        //测试大小
        $this->assertEquals($Driver->getSize($this->file), strlen($this->contents));

        //测试时间
        $this->assertTrue(is_int($Driver->getMTime($this->file)));
        $this->assertTrue(is_int($Driver->getCTime($this->file)));
        $this->assertTrue(is_int($Driver->getATime($this->file)));
        $this->assertTrue(is_int($Driver->getMTime(''))); //文件所在目录情况
        $this->assertTrue(is_int($Driver->getCTime('')));
        $this->assertTrue(is_int($Driver->getATime('')));
        $this->assertNull($Driver->getMTime('none'));
        $this->assertNull($Driver->getCTime('none'));
        $this->assertNull($Driver->getATime('none'));

        //测试读写,不对目录进行测试
        $absFile = $Driver->getPath() . $this->file;
        $this->assertSame($Driver->isWritable($this->file), is_writable($absFile));
        $this->assertSame($Driver->isReadable($this->file), is_readable($absFile));

        //删除文件,不对目录进行测试
        $Driver->remove($this->file);
        $this->assertFalse($Driver->exists($this->file));

    }

    /**
     * 测试不同路径
     * @depends testRead
     */
    public function testPath($Driver)
    {
        $path1 = $Driver->getPath();
        $path2 = $path1 . '/' . md5(mt_rand()) . '/';

        $Driver = new Local($path1);
        $this->assertSame($Driver->getPath(), $path1);

        //更改路径
        $Driver->setPath($path2);

        $this->assertSame($Driver->getPath(), $path2);
        $this->assertTrue($Driver->getPath() != $path1);

        //不同路径写
        $Driver->write($this->file, $this->contents);
        $this->assertTrue($Driver->exists($this->file));

        $Driver->setPath($path1);
        $this->assertFalse($Driver->exists($this->file));

        $Driver->setPath($path2);
        $this->assertTrue($Driver->exists($this->file));

        //删除path2文件夹
        $this->assertTrue($Driver->remove(''));
        $this->assertFalse($Driver->exists($this->file));
        $this->assertFalse($Driver->exists(''));
        $this->assertFalse($Driver->remove('')); //删除不存在的

        //删除path1文件夹
        $Driver->setPath($path1);
        $this->assertTrue($Driver->remove(''));
        $this->assertFalse($Driver->exists(''));
        $this->assertFalse($Driver->exists($this->file));

    }

    //测试打开php脚本
    public function testInclude()
    {
        $Driver = new Local;
        $this->assertNull($Driver->getContents('none'));

        $data = md5(mt_rand());
        $Driver->write($this->file, "<?php return ['$data'];", 'w');
        $this->assertSame($Driver->getContents($this->file), [$data]);

        //清除
        $Driver->remove('');
    }
}
