<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-03 17:11:01
 * @Modified time:      2018-11-07 10:05:18
 * @Depends on Linker:  Config
 * @Description:        测试自定义异常处理
 */
namespace lin\tests\components;

use Exception;
use Linker;
use lin\exception\structure\Parser;
use PHPUnit\Framework\TestCase;

class ExceptionParserTest extends TestCase
{

    private $LogName; //原日志类名
    private $Log; //日志模拟类

    protected function setUp()
    {
        //模拟日志服务
        $this->LogName = Linker::Log();
        $this->Log     = $this->getMockBuilder($this->LogName)->setMethods([
            'record',
        ])->getMock();

        //替代
        Linker::register([
            'Log' => $this->Log,
        ]);
    }
    protected function tearDown()
    {
        Linker::register([
            'Log' => $this->LogName, //复原
        ]);
    }

    public function testException()
    {
        $info      = mt_rand();
        $Exception = new Exception($info);

        //预言回调
        $this->expectOutputRegex("/$info/");
        Linker::Config()::lin(['exception.exception.callback' => function ($E) {
            echo $E->getMessage();
        }]);

        //预言记录日志
        $this->Log->expects($this->exactly(1))->method('record');

        //执行
        Parser::setException($Exception);

    }

    public function testError()
    {

        $level = mt_rand(0, PHP_INT_MAX);
        $msg   = mt_rand();
        $file  = mt_rand();
        $line  = mt_rand(0, PHP_INT_MAX);
        $match = $level . $msg . $file . $line;

        //预言错误回调
        $this->expectOutputRegex("/$match/");
        Linker::Config()::lin(['exception.error.callback' => function ($level, $msg, $file, $line) {
            echo $level . $msg . $file . $line;
        }]);

        //预言记录日志
        $this->Log->expects($this->exactly(1))->method('record');

        //执行
        Parser::setError($level, $msg, $file, $line);
    }

}
