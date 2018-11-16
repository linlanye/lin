<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-31 14:04:33
 * @Modified time:      2018-10-27 21:26:36
 * @Depends on Linker:  Config
 * @Description:        使用控制台测试，默认请求方法为post
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\request\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private $config;

    protected function setUp()
    {
        $_SERVER['REQUEST_URI'] = '/';
        // Request::getURL();
        // var_dump($_SERVER['PHP_SELF']);
        // var_dump($_SERVER['SCRIPT_FILENAME']);
        $this->config = Linker::Config()::lin('request');
    }
    protected function tearDown()
    {
        Request::reset();
    }

    public function testGetSet()
    {
        //设置某个方法携带的参数
        $data = [
            md5(mt_rand()) => md5(mt_rand()),
        ];
        $method = md5(mt_rand());
        Request::set($method, $data);
        $this->assertSame(Request::get($method), $data);

        //断言当前方法。默认应为post方法
        $data = [
            md5(mt_rand()) => md5(mt_rand()),
        ];
        Request::setCurrent($data);
        $this->assertSame(Request::getCurrent(), $data);
        $this->assertSame(Request::getCurrent(), $_POST);

    }
    public function testUserMethod()
    {
        //使用post做模拟
        $_POST              = [md5(mt_rand()) => md5(mt_rand())];
        $userMethod         = md5(mt_rand());
        $methodName         = $this->config['type']['tag'];
        $_POST[$methodName] = $userMethod;

        //测试模拟方法
        $this->assertNotSame(Request::getMethod(), Request::getRawMethod());
        $this->assertSame(Request::getRawMethod(), 'POST');
        $this->assertSame(Request::getMethod(), strtoupper($userMethod));

        //测试用户模拟的自定义请求是否释放原始请求参数
        $this->assertTrue(empty(Request::get('post')));
        $this->assertTrue(empty($_POST));
    }

    public function testMethod()
    {
        //模拟方法
        $rawMethod                 = $_SERVER['REQUEST_METHOD'] ?? null;
        $method                    = md5(mt_rand());
        $_SERVER['REQUEST_METHOD'] = $method;

        $this->assertSame(Request::getMethod(), Request::getRawMethod());
        $this->assertSame(Request::getMethod(), strtoupper($method));

        //复原
        $_SERVER['REQUEST_METHOD'] = $rawMethod;
    }
    public function testDynamicGetSet()
    {
        $key     = md5(mt_rand());
        $value   = md5(mt_rand());
        $_POST   = [$key => $value];
        $Request = new Request;

        //测试读取
        $this->assertSame($Request->$key, Request::getCurrent()[$key]);
        $this->assertTrue(isset($Request->$key));
        $this->assertFalse(isset($Request->none));

        //测试写
        $value         = md5(mt_rand());
        $Request->$key = $value;
        $this->assertSame($Request->$key, Request::getCurrent()[$key]);
    }

    //测试某个请求方法携带参数的动态读写
    public function testDynamicMethod()
    {
        $method  = md5(mt_rand());
        $k0      = md5(mt_rand());
        $k1      = md5(mt_rand());
        $k2      = md5(mt_rand());
        $v       = md5(mt_rand());
        $Request = new Request;
        $Request->$method(["$k0.$k1.$k2" => $v]);
        $this->assertSame($Request->$method("$k0.$k1.$k2"), $v);
        $this->assertSame($Request->$method(), [$k0 => [$k1 => [$k2 => $v]]]);
    }

    public function testGetURL()
    {
        //模拟
        $rawMethod = $_SERVER['REQUEST_URI'] ?? null;
        $url       = md5(mt_rand()) . '/' . md5(mt_rand());

        $_SERVER['REQUEST_URI'] = $url;
        $this->assertSame(Request::getURL(), '/' . $url);

        //复原
        $_SERVER['REQUEST_URI'] = $rawMethod;
    }

    public function testUploads()
    {
        //模拟数据
        $_FILES = [
            'item1' => [
                'name'     => [
                    'some file.ext',
                    'another file.ext',
                ],
                'type'     => [
                    'some type',
                    'another type',
                ],
                'tmp_name' => [
                    'tmp_directory',
                    'tmp_directory',
                ],
                'error'    => [
                    Request::UPLOAD_OK,
                    Request::UPLOAD_INI_SIZE,
                ],
                'size'     => [
                    1000,
                    2000,
                ],
            ],
            'item2' => [
                'name'     => 'some file2.ext',
                'type'     => 'some type2',
                'tmp_name' => 'tmp_directory',
                'error'    => Request::UPLOAD_FORM_SIZE,
                'size'     => 3000,
            ],
        ];

        $Request = new Request;
        $r       = Request::getUploadsError();
        $this->assertSame($r['item1'][0], Request::UPLOAD_NOT_UPLOADED_FILE);
        $this->assertSame($r['item1'][1], Request::UPLOAD_INI_SIZE);
        $this->assertSame($r['item2'][0], Request::UPLOAD_FORM_SIZE);
        $this->assertTrue(empty(Request::getUploads()));

        //复原
        $_FILES = [];
        @rmdir($this->config['uploads']['path']);
    }

}
