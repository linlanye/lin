<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 09:27:03
 * @Modified time:      2018-08-29 16:58:53
 * @Depends on Linker:  None
 * @Description:        测试扩展了的异常
 */
namespace lin\tests\basement;

use lin\basement\exception\GeneralException as Exception;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testThrow()
    {
        $info    = md5(mt_rand());
        $type    = md5(mt_rand());
        $subInfo = md5(mt_rand());
        try {
            throw new Exception($info, 1, $type, $subInfo);
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), $info);
            $this->assertEquals($e->getType(), $type);
            $this->assertEquals($e->getSecondMessage(), $subInfo);
        }
        try {
            Exception::throw ($info, 1, $type, $subInfo);
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), $info);
            $this->assertEquals($e->getType(), $type);
            $this->assertEquals($e->getSecondMessage(), $subInfo);
        }

        try {
            throw new Exception($info);
        } catch (Exception $e) {
            $this->assertTrue(empty($e->getType()));
            $this->assertTrue(empty($e->getSecondMessage()));
        }

    }
}
