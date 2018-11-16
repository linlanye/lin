<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-21 15:22:13
 * @Modified time:      2018-09-04 10:31:17
 * @Depends on Linker:  Config
 * @Description:        测试路由器
 */
namespace lin\tests\components;

use Exception;
use Linker;
use lin\basement\request\Request;
use lin\route\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{

    private $url; //原始的url值，用于修改url
    public function setUp()
    {
        $this->url = $_SERVER['REQUEST_URI'] ?? null;
    }
    public function tearDown()
    {
        $_SERVER['REQUEST_URI'] = $this->url;
        Route::reset();
        Request::reset(); //重置请求避免缓存
    }
    //测试运行文件读取
    public function testLoading()
    {
        //读取的两个路由文件有规则冲突
        try {
            Route::run('*');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
            Route::reset();
        }

        //读取的两个路由文件有规则冲突
        try {
            Route::run('route1, route2');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
            Route::reset();
        }
        //读取的两个路由文件有规则冲突
        try {
            Route::run('route*');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
            Route::reset();
        }

        //未读取路由文件，调用通用规则
        $this->expectOutputString(Request::getURL() . Request::getMethod());
        Route::run(null);
    }

    //测试三种规则格式的执行
    public function testFormatString()
    {
        $_SERVER['REQUEST_URI'] = '/string';
        $this->expectOutputString('string');
        Route::run('route1');
    }
    public function testFormatArray()
    {
        $_SERVER['REQUEST_URI'] = '/array';
        $this->expectOutputString('array1array2');
        Route::run('route1');
    }
    public function testFormatClosure()
    {
        $_SERVER['REQUEST_URI'] = '/Closure';
        $this->expectOutputString('Closure');
        Route::run('route1');
    }

    //测试路由匹配大小写不敏感
    public function testCI()
    {
        $_SERVER['REQUEST_URI'] = '/closure';

        Linker::Config()::lin(['route.ci' => true]);
        $this->expectOutputString('Closure');
        Route::run('route1');
    }
    //测试路由匹配大小写敏感
    public function testCS()
    {
        $_SERVER['REQUEST_URI'] = '/closure';

        Linker::Config()::lin(['route.ci' => false]);
        $this->expectOutputString(Request::getURL() . Request::getMethod()); //大小写敏感，转入通用规则
        Route::run('route1');
    }

    //测试前置后置执行
    public function testPrePost()
    {
        $_SERVER['REQUEST_URI'] = "/pre";
        $this->expectOutputString('preprepost');
        Route::run('route1');

    }

    //测试动态规则
    public function testDynamic()
    {
        $backup                 = Request::getCurrent(); //备份请求参数
        $a                      = md5(mt_rand());
        $b                      = md5(mt_rand());
        $_SERVER['REQUEST_URI'] = "/$a-$b";

        $this->expectOutputString($a . $b);
        Route::run('route1');

        $params = Request::getCurrent();
        $this->assertSame($params['a'], $a); //动态参数写入请求
        $this->assertSame($params['b'], $b);
        Request::setCurrent($backup);
    }

    //测试规则绑定在专用的方法上
    public function testOnMethod()
    {
        Request::reset();
        $backup                    = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'post'; //此处get就一定可以匹配
        $_SERVER['REQUEST_URI']    = "/get";
        Route::run('route1');
        $this->expectOutputString(Request::getURL() . Request::getMethod()); //未匹配到当前方法的路由，转入通用规则
    }

    //测试路由终止
    public function testTerminal()
    {
        $_SERVER['REQUEST_URI'] = "/terminal";
        $this->expectOutputString('terminal'); //前置便已终止，不再执行
        Route::run('route1');
    }

    //测试带后缀名的url正常处理
    public function testSuffix()
    {
        $_SERVER['REQUEST_URI'] = "/string.html";
        Route::run('route1');
        Route::reset();
        $_SERVER['REQUEST_URI'] = "/string.htm"; //多个后缀名都可以处理
        Route::run('route1');
        $this->expectOutputString('stringstring');
    }
}
