<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-10 10:48:13
 * @Modified time:      2018-10-23 13:13:42
 * @Depends on Linker:  none
 * @Description:        查询构建器测试
 */
namespace lin\tests\components\orm;

use Exception;
use lin\orm\query\Query;
use PHPUnit\Framework\TestCase;
use stdclass;

class QueryTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;
    private $Query;
    public static function setUpBeforeClass()
    {
        self::createDB('query');
    }
    protected function setUp()
    {
        $this->Query = new Query;
    }
    protected function tearDown()
    {
        $this->Query = null; //释放掉链接
    }
    public function testSetDriver()
    {
        $object = new stdclass;
        $this->Query->setDriver($object);
        $this->assertSame($object, $this->Query->getDriver());
    }

    /**
     * 测试读写
     * @group database
     */
    public function testCURD()
    {
        //读
        $r = $this->Query->table('query1')->select();
        $n = $this->Query->table('query1')->fields('x1,y1,*')->count(); //多字段的时候聚合位于最后一个
        $this->assertEquals(count($r), $n);
        $yield = $this->Query->table('query1')->yield();
        $count = 0;
        foreach ($yield as $key => $value) {
            $count++;
        }
        $this->assertEquals($count, $n);

        //写
        try {
            $this->Query->table('query1')->where('x1', 1)->update('x1', 2);
            $this->Query->table('query1')->update('x1', 1); //无条件更新抛出异常
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $this->Query->table('query1')->delete(); //无条件删除抛出异常
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * 测试自动事务
     * @group database
     */
    public function testAutoTrans()
    {
        //事务成功
        $r = $this->Query->autoTrans(function ($Query) {
            $Query->table('query1')->insert('x1,y1', 1);
            $Query->table('query2')->insert('x2,y2', 1);
        });
        $this->assertTrue($r);
        $this->assertEquals($this->Query->table('query1')->count(), 3);
        $this->assertEquals($this->Query->table('query2')->count(), 3);

        //事务失败
        $r = $this->Query->autoTrans(function ($Query) {
            $Query->table('query1')->insert('x1,y1', 1);
            $Query->table('query2')->insert('x2,y2', 1);
            $Query->table('query1')->where('x1', 10)->delete(); //不存在该记录，则失败
        });
        $this->assertFalse($r);
        $this->assertEquals($this->Query->table('query1')->count(), 3);
        $this->assertEquals($this->Query->table('query2')->count(), 3);
    }
}
