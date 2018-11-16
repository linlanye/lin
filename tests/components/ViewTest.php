<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-05 09:07:41
 * @Modified time:      2018-09-10 14:43:46
 * @Depends on Linker:  None
 * @Description:        视图渲染引擎测试，未测试缓存过期
 */

namespace lin\tests\components;

use DOMDocument;
use Linker;
use lin\view\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    use \lin\tests\traits\RemoveTrait;

    private $View;
    private $Dom;
    private $data;
    private $config;

    public function setUp()
    {
        $this->View   = new View;
        $this->Dom    = new DOMDocument;
        $this->config = Linker::Config()::lin('view');
        $this->data   = [ //分配变量
            'if'           => 1, 'switch' => 1, 'while' => 1,
            'value_var'    => md5(mt_rand()),
            'value_if'     => md5(mt_rand()),
            'value_switch' => md5(mt_rand()),
            'value_while'  => md5(mt_rand()),
            'value_array'  => [md5(mt_rand()), md5(mt_rand())],
            'escape'       => '{:escape}',
        ];
    }
    public static function tearDownAfterClass()
    {
        $path = Linker::Config()::lin('view.cache.path');
        self::rmdir($path);
    }

    //测试基本输出php代码
    public function testBase()
    {
        $contents = $this->View->getContents('base');
        $this->assertRegExp('/\<\?php/', $contents);
        $this->assertRegExp('/\?\>/', $contents); //存在php标签
        $cache = $this->config['cache']['path'] . '/dynamic';
        file_put_contents($cache, $contents);
        extract($this->data, EXTR_OVERWRITE);
        ob_clean();
        ob_start();
        include $cache;
        $contents = ob_get_clean(); //获取解析内容
        $this->Dom->loadHTML($contents); //从字符串获得节点
        $this->assertHTMLNode();
    }

    //测试引入和输出纯html内容
    public function testStatic()
    {
        $contents = $this->View->withData($this->data)->getContents('static');
        $cache    = $this->config['cache']['path'] . '/static';
        file_put_contents($cache, $contents);

        $this->Dom->loadHTMLFile($cache); //从文件获得节点
        $this->assertHTMLNode();

    }

    //测试继承
    public function testExtends()
    {
        $contents = $this->View->getContents('extends');
        $this->Dom->loadHTML($contents);

        //断言父节点
        $parent = $this->Dom->getElementById('parent');
        $this->assertNotNull($parent);
        $this->assertNotFalse(strstr($parent->nodeValue, 'parent'));

        //断言子节点
        $children = $parent->getElementsByTagName('div');
        $this->assertNotNull($children);
        $this->assertSame(2, $children->length);
        foreach ($children as $child) {
            $this->assertNotFalse(strstr($child->nodeValue, $child->getAttribute('name')));
        }
    }

    //测试视图安全输出
    public function testSecurity()
    {
        $xss   = '<script></script>';
        $array = [
            [
                $xss,
                $xss,
            ],
            [
                $xss,
                $xss,
            ],
        ];
        $contents = $this->View->assign('array', $array)->assign('var', $xss)->getContents('security');
        $this->assertNotFalse(strstr($contents, htmlspecialchars($xss))); //断言已含安全转义
        $this->assertFalse(strstr($contents, $xss)); //断言不含xss注入
    }

    //用于测试模板中函数输出解析
    public static function outputMD5($var)
    {
        return md5($var);
    }

    //断言HTML节点
    private function assertHTMLNode()
    {
        $nodes = $this->Dom->getElementsByTagName('div');
        foreach ($nodes as $node) {
            $expected = $node->getAttribute('output'); //目标输出
            $actual   = $node->nodeValue; //静态化后内容
            $this->assertNotFalse(strstr($actual, $expected));
        }
    }

}
