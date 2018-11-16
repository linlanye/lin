<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-21 20:06:39
 * @Modified time:      2018-11-08 10:09:54
 * @Depends on Linker:  None
 * @Description:        测试配置类，该类属于全局变量，测试方法顺序不能变
 */

namespace lin\tests\basement;

use lin\basement\config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{

    //测试清除，必须置于开头测试
    public function testClean()
    {
        $name = md5(mt_rand());
        Config::clean($name);
        $this->assertFalse(Config::exists($name));

        Config::set($name, ['anything']);
        $this->assertTrue(Config::exists($name));

        Config::clean($name);
        $this->assertFalse(Config::exists($name));
    }

    //常规测试
    public function testGeneral()
    {
        $data = ['a0' => ['b0' => ['c0' => 'value']]];
        $name = md5(mt_rand());
        Config::set($name, $data);

        //测试读常规写
        $this->assertTrue(Config::exists($name));
        $this->assertSame(Config::get($name), $data);

        //边缘情况
        Config::set($name, []);
        $this->assertTrue(is_array(Config::get($name)));
        $this->assertTrue(empty(Config::get($name)));

        //测试不存在的数据
        $this->assertNull(Config::get('none'));
        $this->assertFalse(Config::exists('none'));

        //清理
        Config::clean($name);
    }

    //动态调用测试
    public function testStaticCall()
    {
        //随机配置名
        $data = ['a0.b0.c0' => 'value'];
        $name = md5(mt_rand());
        Config::$name($data);

        //测试
        $this->assertSame(Config::$name(key($data)), current($data));
        $this->assertSame(Config::$name(), ['a0' => ['b0' => ['c0' => 'value']]]); //获取所有
        $this->assertNull(Config::$name('nothing.exists'));

        $value = md5(mt_rand());
        Config::$name(['a0.b1.c1' => $value]);
        $this->assertSame($value, Config::$name('a0.b1.c1'));
        //清理
        Config::clean($name);

    }

}
