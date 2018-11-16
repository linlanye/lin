<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-31 20:57:00
 * @Modified time:      2018-08-29 16:58:56
 * @Depends on Linker:  Config
 * @Description:        使用ORM Query测试sql写日志
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\log\structure\SQLHandler;
use lin\orm\query\Query;
use PHPUnit\Framework\TestCase;

class LogSQLHandlerTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;

    public static function setUpBeforeClass()
    {
        self::createDB('log');
    }

    /**
     * @group database
     */
    public function testSQLHandler()
    {
        //创建数据
        $config = Linker::Config()::lin('log.server.sql');
        $table  = $config['table'];
        $data   = [
            ['name0', 'type0', 'content0', time()],
            ['name1', 'type1', 'content1', time()],
            ['name0', 'type0', 'content0', time()],
        ];
        $Query   = new Query;
        $Handler = new SQLHandler;

        //断言记录数
        $Handler->write($data, $config);
        $this->assertEquals(count($data), $Query->table($table)->count());

        //断言数据集一致
        foreach ($Query->table($table)->select() as $key => $value) {
            $value = array_values($value);
            ksort($data[$key]);
            ksort($value);
            $this->assertEquals($data[$key], $value);
        }
    }
}
