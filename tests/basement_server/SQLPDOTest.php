<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 10:36:16
 * @Modified time:      2018-08-29 16:59:04
 * @Depends on Linker:  None
 * @Description:        使用pdo封装的ServerSQL，注：由于需要读写及创建删除表，全局只能有一个实例（链接），
 *                      否则会导致竞争加锁。
 */
namespace lin\tests\basement_server;

use Exception;
use lin\basement\server\sql\SQLPDO;
use PHPUnit\Framework\TestCase;
use stdclass;

/**
 * @requires extension pdo
 */
class SQLPDOTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;

    private $Driver;
    private static $data = []; //记录插入数据，不可用非静态，因为每一次测试方法都会重新实例化

    public static function setUpBeforeClass()
    {
        self::createDB('server_sql');
    }

    /**
     * 测试绑定变量
     * @group database
     */
    public function testBindParam()
    {
        $Driver = new SQLPDO;
        $Driver->execute('select * from server_sql where id=:id or content=:content', [':id' => 1, ':content' => null]);
        $count1 = count($Driver->fetchAllAssoc());
        $Driver->execute('select * from server_sql where id=? or content=?', [1, null]);
        $count2 = count($Driver->fetchAllAssoc());
        $this->assertSame($count1, $count2);
        $this->assertEquals($count1, 1);
    }

    /**
     * @group database
     */
    public function testFetchAssoc()
    {
        //测试单条记录获取
        $Driver = new SQLPDO;
        $Driver->execute('select * from server_sql');
        $n = 0;
        while ($r = $Driver->fetchAssoc()) {
            $this->assertTrue(is_array($r));
            $n++;
        }
        $this->assertEquals(3, $n);

        //测试一次性获取
        $Driver->execute('select * from server_sql');
        $r = $Driver->fetchAllAssoc();
        $this->assertTrue(is_array($r));
        $this->assertEquals(count($r), $n);
    }

    /**
     * @group database
     */
    public function testFetchObject()
    {
        //测试单条记录获取
        $Driver = new SQLPDO;
        $Driver->execute('select * from server_sql');
        $n = 0;
        while ($r = $Driver->fetchObject()) {
            $this->assertTrue(is_object($r));
            $n++;
        }
        $this->assertEquals(3, $n);

        //测试一次性获取
        $Driver->execute('select * from server_sql');
        $r = $Driver->fetchAllObject();
        for ($i = 0; $i < $n; $i++) {
            $this->assertTrue(is_object($r[$i]));
        }
        $this->assertTrue(is_array($r));
        $this->assertEquals(count($r), $n);
    }

    /**
     * @group database
     */
    public function testTransaction()
    {
        $Driver = new SQLPDO;
        $Driver->beginTransaction();
        $this->assertTrue($Driver->inTransaction()); //处于事务中

        $Driver->execute('insert into server_sql(content) values("any thing")');
        $this->assertSame($Driver->rowCount(), 1); //测试影响记录数
        $Driver->rollback();
        $this->assertFalse($Driver->inTransaction());
    }

    public function testUserIndexAndDriver()
    {
        //测试外部指定索引
        $Driver = new SQLPDO('w');
        $this->assertSame('w', $Driver->getIndex()); //只写

        //测试读写索引
        //只读
        try {
            $Driver->execute('select * from server_sql'); //无读服务器，抛出异常
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        $Driver = new SQLPDO('r');
        $this->assertSame('r', $Driver->getIndex());

        //只写
        try {
            $Driver->execute('delete from server_sql'); //无读服务器，抛出异常
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $Driver->beginTransaction(); //事务下无读服务器，抛出异常，且事务自动关闭
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
            $this->assertFalse($Driver->inTransaction());
        }

        //测试读写切换
        $Driver = new SQLPDO('r, w');
        $Driver->execute('select * from server_sql');
        $this->assertSame('r', $Driver->getIndex());
        $Driver->execute('delete from server_sql');
        $this->assertSame('w', $Driver->getIndex());

        //测试外部指定驱动
        $UserDriver = new stdclass;
        $Driver     = new SQLPDO($UserDriver);

        $this->assertSame('user', $Driver->getIndex());
        $this->assertSame($UserDriver, $Driver->getDriver());
        $this->assertSame('sql', $Driver->getServerName());

    }

}
