<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-11-01 16:05:42
 * @Modified time:      2018-11-13 10:39:55
 * @Depends on Linker:  None
 * @Description:        测试LSE算法
 */
namespace lin\tests\components;

use lin\algorithms\LSE;
use PHPUnit\Framework\TestCase;

class LSETest extends TestCase
{
    public function testLSE()
    {
        $key1 = md5(mt_rand());
        $LSE1 = new LSE($key1);

        $data = (string) mt_rand();
        $en1  = $LSE1->encrypt($data);
        $de1  = $LSE1->decrypt($en1); //一次加密解密
        $this->assertSame($data, $de1);

        $en2 = $LSE1->encrypt($data); //每一次加密输出不一样
        $this->assertNotSame($en1, $en2);
        $de2 = $LSE1->decrypt($en2);
        $this->assertSame($data, $de2);

        //使用不同密钥加密
        $key2 = md5(mt_rand());
        $LSE2 = new LSE($key2);

        $_en1 = $LSE1->encrypt($data);
        $_de1 = $LSE1->decrypt($_en1); //一次加密解密
        $this->assertSame($data, $_de1);
        $this->assertNotSame($en1, $_en1);

        //循环多次加密解密
        for ($i = 0; $i < 200; $i++) {
            $LSE  = new LSE(md5(mt_rand()), 2);
            $data = (string) mt_rand();
            $en   = $LSE->encrypt($data);
            $de   = $LSE->decrypt($en);
            $this->assertSame($data, $de);
            $this->assertNotSame($data, $en);
        }

        //加密次数多次
        $times = mt_rand(100, 999);
        $LSE   = new LSE(md5(mt_rand()), $times);
        $data  = (string) mt_rand();
        $en    = $LSE->encrypt($data);
        $de    = $LSE->decrypt($en);
        $this->assertSame($data, $de);
        $this->assertNotSame($data, $en);
    }
}
