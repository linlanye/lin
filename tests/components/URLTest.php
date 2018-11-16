<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-03 15:47:26
 * @Modified time:      2018-09-04 11:12:43
 * @Depends on Linker:  None
 * @Description:        测试url创建器
 */
namespace lin\tests\components;

use Linker;
use lin\url\URL;
use PHPUnit\Framework\TestCase;

class URLTest extends TestCase
{

    //测试动态url
    public function testDynamic()
    {
        $a      = md5(mt_rand());
        $b      = mt_rand();
        $params = URL::getParameters();
        $domin  = $params['domin'];
        $script = $params['script'];
        $this->assertSame(URL::get("/$a/$b"), "http://$domin/$script/$a/$b.html");
        $this->assertSame(URL::get("/$a/$b", [$a => $b]), "http://$domin/$script/$a/$b.html?$a=$b");
        $this->assertSame(URL::get("/"), "http://$domin");
    }

    //测试静态url
    public function testStatic()
    {
        $a      = md5(mt_rand());
        $b      = mt_rand();
        $params = URL::getParameters();
        $domin  = $params['domin'];
        $script = $params['script'];
        $path   = "$a-$b";
        $this->assertSame(URL::getStatic("/$path"), "http://$domin/$path");
        $this->assertSame(URL::getStatic("/$path", [$a => $b]), "http://$domin/$path");
    }
    //测试参数前后不含'/'
    public function testParams()
    {
        $params = URL::getParameters();
        $domin  = $params['domin'];
        $script = $params['script'];
        foreach ($params as $param) {
            $this->assertFalse($param[0] == '/');
            $this->assertFalse(substr($param, -1) == '/');
        }
    }
}
