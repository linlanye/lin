<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 11:11:47
 * @Modified time:      2018-08-29 16:58:57
 * @Depends on Linker:  Config
 * @Description:        测试日志基本功能
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\log\Log;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{

    public function testRecordMethod()
    {
        $Log = $this->getMockBuilder(Log::class)->setMethods(['record'])->getMock();
        $msg = md5(mt_rand());
        $Log->expects($this->once())->method('record')->with($msg, 'info');
        $Log->info($msg);
    }

}
