<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-10 14:50:55
 * @Modified time:      2018-09-13 16:18:21
 * @Depends on Linker:  None
 * @Description:        测试验证函数
 */
namespace lin\tests\components\processor;

use lin\validator\structure\Functions;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{

    //测试基础类型判断
    public function testBaseType()
    {
        $data = [
            'int'    => mt_rand(0, PHP_INT_MAX),
            'float'  => microtime(true),
            'bool'   => true,
            'string' => md5(mt_rand()),
            'null'   => null,
            'array'  => [],
            'object' => (object) [],
        ];

        $funcs = [
            'int'  => 'isInt', 'float'  => 'isFloat', 'bool'   => 'isBool', 'string' => 'isString',
            'null' => 'isNull', 'array' => 'isArray', 'object' => 'isObject',
        ];
        foreach ($funcs as $index => $func) {
            foreach ($data as $index2 => $v) {
                $r = Functions::$func($v);
                if ($index === $index2) {
                    $this->assertTrue($r); //一一对应
                } else {
                    $this->assertFalse($r);
                }
            }
        }
    }

    //测试数字类型判断，采用互斥方式
    public function testNumber()
    {
        $data = [-10e20, -10000, '-12730', '-9370.0', 9370.0, 10e20, 10000, '12730', '9370.0', 9370.0, '12.31',
            -30.2, 0, 10.29, 1.332e2, 1.2e1,
        ];
        foreach ($data as $num) {
            if (Functions::isZNum($num)) {
                if (!Functions::isEven($num)) {
                    $this->assertTrue(Functions::isOdd($num)); //奇偶判断
                }
                if (!Functions::isNNum($num)) {
                    $this->assertTrue(Functions::isPNum($num) || $num == 0);
                }
                if (!Functions::isNNum($num)) {
                    $this->assertTrue(Functions::isNatNum($num));
                }
            } else {
                $this->assertTrue(Functions::isDecimal($num)); //整数分数判断
                if (!Functions::isPDecimal($num)) {
                    $this->assertTrue(Functions::isNDecimal($num));
                }
            }
        }

    }

    //测试字符相关
    public function testChars()
    {
        $this->assertTrue(Functions::isAlpha('shoUxla'));
        $this->assertFalse(Functions::isAlpha('sho2Uxla'));

        $this->assertTrue(Functions::isZH('找大红'));
        $this->assertFalse(Functions::isZH('找a大红'));

        $this->assertTrue(Functions::isEmail('ao270.123h0@adpj.com.aj'));
        $this->assertFalse(Functions::isEmail('ao270.123h0@adpj'));

        $this->assertTrue(Functions::isMobile(17301727091));
        $this->assertFalse(Functions::isMobile(27301727091));

        $this->assertTrue(Functions::isIP('0.0.0.0'));
        $this->assertFalse(Functions::isIP('0.0.0.025'));

        $this->assertTrue(Functions::isURL('http://lin-php.com'));
        $this->assertFalse(Functions::isURL('www.lin-php.com'));

        $this->assertTrue(Functions::isZIP('625300'));
        $this->assertFalse(Functions::isZIP('06308'));

        $data = [
            '(010) 20389080', '010-20389080', '20389080', '2038908', '0833 2038908',
        ];
        foreach ($data as $value) {
            $this->assertTrue(Functions::isTel($value));
        }

        $this->assertTrue(Functions::isDate('2018-7-9'));
        $this->assertFalse(Functions::isDate('2018-7/9'));

        $this->assertTrue(Functions::isActive('on'));
        $this->assertFalse(Functions::isActive(0));

        $this->assertTrue(Functions::isID('140831198708302378'));
        $this->assertFalse(Functions::isID('140813198718302378'));

        $this->assertTrue(Functions::isBC('140831198708302378'));
        $this->assertFalse(Functions::isBC('14081319871830237811'));

        $this->assertTrue(Functions::isAccount('的l80X_-@10.'));
        $this->assertFalse(Functions::isAccount('的l80X_-@10!'));
    }

    //测试二值比较
    public function testComparison()
    {
        //密码校验
        $this->assertTrue(Functions::checkPwd('的l80X_-@10.', password_hash('的l80X_-@10.', PASSWORD_DEFAULT)));
        $this->assertFalse(Functions::checkPwd('的l80X_-@10.', '的l80X_-@10.'));

        //二值比较
        $resource = fopen(__TMP__ . '/tmp_validator_funcs', 'w');
        $a        = [
            'int'      => 10,
            'float'    => 1.1,
            'bool'     => true,
            'string'   => 'abcds',
            'null'     => null,
            'array'    => [1],
            'object'   => (object) [1],
            'resource' => $resource,
            'eq'       => 0,
        ];
        $b = [
            'int'      => 9,
            'float'    => 0.9,
            'bool'     => false,
            'string'   => 'bbcds',
            'null'     => null,
            'array'    => [],
            'object'   => (object) [],
            'resource' => $resource,
            'eq'       => 0,
        ];
        foreach ($a as $v) {
            foreach ($b as $v2) {
                if (!Functions::gt($v, $v2)) {
                    $this->assertTrue(Functions::le($v, $v2)); //互斥
                }
                if (!Functions::ge($v, $v2)) {
                    $this->assertTrue(Functions::lt($v, $v2)); //互斥
                }
                if (!Functions::eq($v, $v2)) {
                    $this->assertTrue(Functions::ne($v, $v2)); //互斥
                }
            }
        }
        fclose($resource);
        unlink(__TMP__ . '/tmp_validator_funcs');
    }
}
