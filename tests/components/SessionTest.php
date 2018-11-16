<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-03 13:55:46
 * @Modified time:      2018-09-28 15:30:32
 * @Depends on Linker:  Config ServerKV
 * @Description:        Session驱动测试
 */
namespace lin\tests\components;

use Linker;
use lin\session\structure\KVHandler;
use lin\session\structure\SQLHandler;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;
    use \lin\tests\traits\RemoveTrait;

    public static function tearDownAfterClass()
    {
        $KV = Linker::ServerKV(true);
        $KV->close(); //关闭文件才可删除
        self::rmdir(Linker::Config()::lin('server.kv.driver.local.path'));
    }

    /**
     * @group server
     * @group sleep
     */
    public function testSQLHandler()
    {
        self::CreateDB('session'); //创建数据库

        $config  = Linker::Config()::lin('session');
        $Handler = new SQLHandler($config['server']['sql'], $config['life']);
        $this->handle($Handler);

        //测试gc
        $key   = md5(mt_rand());
        $value = md5(mt_rand());

        //替换成唯一实例，避免读写锁定
        $DriverName = Linker::ServerSQL();
        $Driver     = Linker::ServerSQL(true);
        Linker::register(['ServerSQL' => $Driver]);

        $Handler = new SQLHandler($config['server']['sql'], $config['life']);
        $Handler->write($key, $value);
        sleep(2);
        $this->assertEmpty($Handler->read($key)); //已过期但记录未删除

        $table = $config['server']['sql']['table'];

        $Driver->execute("select count(*) from $table");
        $this->assertNotEmpty($Driver->fetchAssoc()); //未执行gc前，记录还在

        $Handler->gc(1);
        $Driver->execute("select count(*) from $table");
        $this->assertNotEmpty($Driver->fetchAssoc()); //执行gc后，记录不在

        Linker::register(['ServerSQL' => $DriverName]); //复原
    }

    /**
     * @group server
     * @group sleep
     */
    public function testKVHandler()
    {
        $config  = Linker::Config()::lin('session');
        $Handler = new KVHandler($config['server']['kv'], $config['life']);
        $this->handle($Handler);
    }

    private function handle($Handler)
    {
        $key   = md5(mt_rand());
        $value = md5(mt_rand());

        $this->assertEmpty($Handler->read($key));

        //测试读写
        $Handler->write($key, $value);
        $this->assertSame($Handler->read($key), $value);

        //测试销毁
        $Handler->destroy($key);
        $this->assertEmpty($Handler->read($key));

        //测试过期
        $Handler->write($key, $value);
        sleep(2);
        $this->assertEmpty($Handler->read($key));

    }

}
