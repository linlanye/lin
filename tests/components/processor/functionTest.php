<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-22 15:03:53
 * @Modified time:      2018-08-20 16:25:41
 * @Depends on Linker:  None
 * @Description:        测试格式化函数
 */
namespace lin\tests\components\processor;

use lin\processor\structure\Functions;
use PHPUnit\Framework\TestCase;
use stdclass;

class ExceptionParserTest extends TestCase
{
    //测试基础类型转换
    public function testBaseConvert()
    {
        $data = $this->getBaseData();
        foreach ($data as $value) {
            $this->assertTrue(is_int(Functions::toInt($value)));
        }
        foreach ($data as $value) {
            $this->assertTrue(is_float(Functions::toFloat($value)));
        }
        foreach ($data as $value) {
            $this->assertTrue(is_string(Functions::toString($value)));
        }
        foreach ($data as $value) {
            $this->assertTrue(is_bool(Functions::toBool($value)));
        }
        foreach ($data as $value) {
            $this->assertTrue(is_array(Functions::toArray($value)));
        }
        foreach ($data as $value) {
            $this->assertTrue(is_object(Functions::toObject($value)));
        }
    }

    //测试任意类型转换指定类型
    public function testAnyToFixed()
    {
        $data = $this->getBaseData();

        $least_toDate    = false; //最少转换成功一次
        $least_toActive  = false;
        $least_toPNum    = false;
        $least_toNatNum  = false;
        $least_toNNum    = false;
        $least_toPrice   = false;
        $least_toTString = false;

        foreach ($data as $value) {
            $toDate    = Functions::toDate($value);
            $toActive  = Functions::toActive($value);
            $toPNum    = Functions::toPNum($value);
            $toNatNum  = Functions::toNatNum($value);
            $toNNum    = Functions::toNNum($value);
            $toPrice   = Functions::toPrice($value);
            $toTString = Functions::toTString($value);

            $least_toDate !== null && $least_toDate       = true;
            $least_toActive !== 0 && $least_toActive      = true;
            $least_toPNum !== null && $least_toPNum       = true;
            $least_toNatNum !== null && $least_toNatNum   = true;
            $least_toNNum !== null && $least_toNNum       = true;
            $least_toPrice !== null && $least_toPrice     = true;
            $least_toTString !== null && $least_toTString = true;
            $this->assertTrue($toDate === null || preg_match('/:/', $toDate));
            $this->assertTrue($toActive === 0 || $toActive === 1);
            $this->assertTrue($toPNum === null || $toPNum > 0);
            $this->assertTrue($toNatNum === null || $toNatNum >= 0);
            $this->assertTrue($toNNum === null || $toNNum < 0);
            $this->assertTrue($toPrice === null || preg_match('/^[0-9]+\.[0-9]{2}$/', $toPrice));
            $this->assertTrue($toTString === null || preg_match('/,|\d+/', $toTString));

        }
        $this->assertTrue($least_toDate);
        $this->assertTrue($least_toActive);
        $this->assertTrue($least_toPNum);
        $this->assertTrue($least_toNatNum);
        $this->assertTrue($least_toNNum);
        $this->assertTrue($least_toPrice);
        $this->assertTrue($least_toTString);

        //时间戳转换
        $time = [0 => '现在', 59 => '秒', 60 => '分', 3600 => '时', 3600 * 24 => '天', 3600 * 24 * 30 => '月', 3600 * 24 * 30 * 12 => '年'];
        foreach ($time as $value => $pattern) {
            $value += time();
            $this->assertSame(1, preg_match("/$pattern/", Functions::toPast($value)));
            $this->assertSame(1, preg_match("/$pattern/", Functions::toFuture($value)));
        }
        //倒计时转换
        $time = [60 => '\d+分\d+秒', 3600 => '\d+时\d+分\d+秒', 3600 * 24 => '\d+天\d+时\d+分\d+秒'];
        foreach ($time as $value => $pattern) {
            $value += time();
            $this->assertSame(1, preg_match("/$pattern/", Functions::toCountdown($value)));
        }

    }

    public function testFixedToFixed()
    {
        //ip转换
        $default    = 'unknown';
        $num        = mt_rand(0, 0x7fffffff);
        $ip         = Functions::num2IP($num, $default);
        $ip_invalid = Functions::num2IP($num + 0x80000000, $default); //溢出
        $this->assertSame(1, preg_match('/\./', $ip));
        $this->assertSame($default, $ip_invalid);
        $default = 0;
        $this->assertSame($num, Functions::ip2Num($ip, $default));
        $this->assertSame($default, Functions::ip2Num($ip_invalid, $default));

        //日期转换
        $date         = '2018-8-20';
        $date_invalid = '2018 8-20';
        $this->assertTrue(Functions::date2Time($date) > 0);
        $this->assertNull(Functions::date2Time($date_invalid));
    }

    //字符专用转换
    public function testStringConvert()
    {
        $this->assertTrue(strlen(Functions::forPwd(mt_rand())) > 32); //明文密码转换
        $this->assertSame(0, preg_match('/</', Functions::forHTML('<HTML>'))); //HTML安全

        //字符剔除
        $this->assertSame(0, preg_match('/\s/', Functions::stripSpace('any thing ')));
        $this->assertSame(0, preg_match('/,/', Functions::stripComma('any, thing,')));
        $this->assertSame('any', Functions::stripSymbol('any thing', ' thing'));
    }

    //获得基础数据类型
    private function getBaseData()
    {
        $array    = [mt_rand()];
        $int      = mt_rand(1000, PHP_INT_MAX); //便于千分位转换
        $float    = mt_rand(0, PHP_INT_MAX) / PHP_INT_MAX;
        $object   = new stdclass;
        $string   = md5(mt_rand());
        $resource = fopen(__TMP__ . 'anything', 'a');
        $null     = null;
        return [$array, $int, $float, $object, $string, $resource, $null];
    }
    public static function tearDownAfterClass()
    {
        @unlink(__TMP__ . 'anything');
    }
}
