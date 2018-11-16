<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-26 08:13:57
 * @Modified time:      2018-08-29 16:58:52
 * @Depends on Linker:  None
 * @Description:        测试调试类，该类属于全局变量，测试方法顺序不能变
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\debug\Debug;
use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
    private $Debug;

    protected function setUp()
    {
        $this->Debug = new Debug;
    }
    protected function tearDown()
    {
        Debug::clean();
    }

    public function testName()
    {
        //默认调试名
        $Debug = new Debug;
        $this->assertSame($Debug->getName(), Linker::Config()::lin('debug.default.name'));

        //设置调试名
        $name = md5(mt_rand());
        $Debug->setName($name);
        $this->assertSame($Debug->getName(), $name);

        //实例化入参调试名
        $name  = md5(mt_rand());
        $Debug = new Debug($name);
        $Debug->setName($name);
        $this->assertSame($Debug->getName(), $name);
    }

    //测试清除，必须置于开头测试
    public function testClean()
    {
        Debug::clean(); //清理所有

        $name = md5(mt_rand());
        $this->Debug->setName($name);
        $this->assertNull($this->Debug->getAll());

        $this->Debug->setAll([md5(mt_rand())]);
        $this->assertFalse(empty($this->Debug->getAll()));

        Debug::clean($name); //只清理该调试
        $this->assertNull($this->Debug->getAll());
    }

    public function testGet()
    {
        $name = md5(mt_rand());
        $this->Debug->setName($name);

        //测试不存在的数据
        $this->assertNull($this->Debug->get('none'));
        $this->assertNull($this->Debug->getAll());

        //读写
        $key      = md5(mt_rand());
        $value    = md5(mt_rand());
        $data_all = [$key => $value];
        $this->Debug->set($key, $value);
        $this->assertSame($this->Debug->get($key), $value);
        $this->assertSame($this->Debug->getAll(), $data_all);

        //追加数据
        $this->Debug->append($key, $value); //以追加形式,数据一样但排序不一样
        $this->assertSame($this->Debug->get($key), [$value, $value]);

        //批量读写
        $data_all = [md5(mt_rand()) => md5(mt_rand())];
        $this->Debug->setAll($data_all);
        $this->assertSame($this->Debug->getAll(), $data_all);
    }

    public function testFlag()
    {
        $flag = md5(mt_rand());
        Debug::beginFlag($flag);
        $this->assertNotNull(Debug::getFlag($flag));
        $this->assertTrue(Debug::endFlag($flag));
        $this->assertFalse(Debug::endFlag($flag));
        $this->assertNotNull(Debug::getFlag($flag));

        $this->assertNull(Debug::getFlag('noneFlag'));
        Debug::clean();
    }
    public function testDump()
    {
        $this->expectOutputRegex('/var1/');
        $this->expectOutputRegex('/var2/');
        Debug::dump('var1', 'var2');
    }

}
