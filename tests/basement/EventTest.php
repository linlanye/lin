<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-29 08:38:50
 * @Modified time:      2018-08-30 16:49:51
 * @Depends on Linker:  None
 * @Description:        测试事件类，该类属于全局变量，测试方法顺序不能变
 */
namespace lin\tests\basement;

use lin\basement\event\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    //测试清除，必须置于开头测试
    public function testClean()
    {
        $event = md5(mt_rand());
        Event::on($event, function () {});
        $this->assertTrue(Event::exists($event));
        Event::clean($event);
        $this->assertFalse(Event::exists($event));
    }
    //测试事件文件读取
    public function testLoading()
    {
        Event::run(null); //不加载任何事件脚本
        $this->assertFalse(Event::exists('a'));
        Event::reset();

        Event::run('event1'); //加载指定事件脚本
        $this->assertTrue(Event::exists('a'));
        $this->assertFalse(Event::exists('b'));
        Event::reset();

        Event::run('event*'); //使用通配符加载指定事件脚本
        $this->assertTrue(Event::exists('a'));
        $this->assertTrue(Event::exists('b'));
        Event::reset();

        Event::run('event1, event2'); //加载多个指定事件脚本
        $this->assertTrue(Event::exists('a'));
        $this->assertTrue(Event::exists('b'));
        Event::reset();

    }

    public function testExists()
    {
        $event = md5(mt_rand());
        $this->assertFalse(Event::exists($event));

        Event::on($event, function () {});
        $this->assertTrue(Event::exists($event));

        Event::off($event);
        $this->assertFalse(Event::exists($event));
    }

    public function testTrigger()
    {
        $event  = md5(mt_rand());
        $param1 = md5(mt_rand());
        $param2 = md5(mt_rand());

        //测试执行
        Event::on($event, function ($param1, $param2) use ($event) {
            echo $param1;
            echo $param2;
            return $event;
        });
        $this->expectOutputRegex("/$param1/");
        $this->expectOutputRegex("/$param2/");
        $this->assertSame($event, Event::trigger($event, $param1, $param2));
        $this->assertTrue(Event::exists($event));

        //测试限定执行次数
        Event::on($event, function () {return true;}, 2);
        $this->assertNotNull(Event::trigger($event));
        $this->assertNotNull(Event::trigger($event));
        $this->assertNull(Event::trigger($event));
        $this->assertFalse(Event::exists($event));

        //测试执行一次
        Event::one($event, function () use ($event) {
            return $event;
        });
        $this->assertSame($event, Event::trigger($event));
        $this->assertFalse(Event::exists($event));

        //测试不存在数据
        $this->assertNull(Event::trigger('none'));

        Event::clean();
    }

}
