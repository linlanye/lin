<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-20 16:28:44
 * @Modified time:      2018-09-19 21:21:12
 * @Depends on Linker:  None
 * @Description:        测试格式化器
 */
namespace lin\tests\components\processor;

use Exception;
use lin\processor\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    protected $F1;
    protected $F2;

    public function setUp()
    {
        $this->F1 = new F1;
        $this->F2 = new F2;
    }

    //测试格式化类型
    public function testType()
    {
        //对may进行测试
        $data = ['var1' => ''];
        $r    = $this->F1->withRule('may')->format($data); //不存在不格式化
        $this->assertSame($r, $data);

        $data = ['var' => ''];
        $r    = $this->F1->withRule('may')->format($data); //存在为空不格式化
        $this->assertSame($r, $data);

        $data = ['var' => 'yes'];
        $r    = $this->F1->withRule('may')->format($data); //存在不为空格式化
        $this->assertNotSame($r, $data);
        $this->assertSame($r['var'], 1);

        //对should进行测试
        $data = ['var1' => ''];
        $r    = $this->F1->withRule('should')->format($data); //不存在不格式化
        $this->assertSame($r, $data);

        $data = ['var' => ''];
        $r    = $this->F1->withRule('should')->format($data); //存在为空格式化
        $this->assertNotSame($r, $data);
        $this->assertSame($r['var'], 1);

        $data = ['var' => 'yes'];
        $r    = $this->F1->withRule('should')->format($data); //存在不为空格式化
        $this->assertNotSame($r, $data);
        $this->assertSame($r['var'], 1);

        //对should进行测试
        $data = ['var1' => ''];
        $r    = $this->F1->withRule('must')->format($data); //不存在格式化
        $this->assertNotSame($r, $data);
        $this->assertSame(count($r), 2);
        $this->assertSame($r['var'], 1);

        $data = ['var' => ''];
        $r    = $this->F1->withRule('must')->format($data); //存在为空格式化
        $this->assertNotSame($r, $data);
        $this->assertSame($r['var'], 1);

        $data = ['var' => md5(mt_rand())];
        $r    = $this->F1->withRule('must')->format($data); //存在不为空格式化
        $this->assertNotSame($r, $data);
        $this->assertSame($r['var'], 1);

        //对默认(should)进行测试
        $data = ['var1' => ''];
        $r    = $this->F1->withRule('must')->format($data); //不存在格式化
        $this->assertNotSame($r, $data);
        $this->assertSame(count($r), 2);
        $this->assertSame($r['var'], 1);

        $data = ['var' => ''];
        $r    = $this->F1->withRule('must')->format($data); //存在为空格式化
        $this->assertNotSame($r, $data);
        $this->assertSame($r['var'], 1);

        $data = ['var' => md5(mt_rand())];
        $r    = $this->F1->withRule('must')->format($data); //存在不为空格式化
        $this->assertNotSame($r, $data);
        $this->assertSame($r['var'], 1);

    }

    //测试规则
    public function testRule()
    {
        $data = ['var' => mt_rand()];
        $r    = $this->F2->withRule('Closure')->format($data); //闭包形式
        $this->assertSame($r['var'], md5($data['var']));

        $data = [];
        $r    = $this->F2->withRule('default_value_for_must')->format($data); //must赋值默认
        $this->assertSame($r['var'], 'var');

        $data = ['var' => -1];
        $r    = $this->F2->withRule('use_func')->format($data); //使用内置函数
        $this->assertSame($r['var'], 1);

        $data = ['var' => md5(mt_rand()), 'var1' => md5(mt_rand())];
        $r    = $this->F2->withRule('multi_params')->format($data); //使用多参数
        $this->assertSame($r['var, var1'], $data['var'] . $data['var1']);

        $r = $this->F2->format($data); //不使用规则返回源数据
        $this->assertSame($r, $data);

        //同时使用多个规则
        $r = $this->F1->withRule('must, others')->format([]);
        $this->assertSame(count($r), 1); //两个规则针对同一字段，都只对源数据进行处理，后者覆盖前者
        $this->assertSame($r['var'], 0);
    }

    //测试两种模式
    public function testMode()
    {
        $data = ['var' => mt_rand(), 'var1' => ''];
        $r    = $this->F2->withRule('mode')->format($data); //返回所有数据
        $this->assertSame($r, $data);

        $data = ['var' => -mt_rand(), 'var1' => ''];
        $r    = $this->F2->withRule('mode')->format($data); //返回所有数据并且被正确覆盖
        $this->assertNotSame($r, $data);
        $data['var'] = 1;
        $this->assertSame($r, $data);

        $r = $this->F2->withRule('mode')->format($data, true); //只保留格式化的数据
        $this->assertNotSame($r, $data);
        unset($data['var1']);
        $this->assertSame($r, $data);

    }

    //测试规则和方法不存在时候抛出异常
    public function testException()
    {
        try {
            $this->F1->withRule('none')->format([]); //规则不存在
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $this->F2->withRule('none_method')->format([]); //方法不存在
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $this->F2->withRule('must')->format([]); //测试不同类的规则互不影响
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

    }

}

class F1 extends Formatter
{

    protected function setting()
    {
        $this->setRule('must', [
            'var:must' => 'toOne',
        ]);
        $this->setRule('should', [
            'var: should' => 'toOne',
        ]);
        $this->setRule('may', [
            'var:may' => 'toOne',
        ]);
        $this->setRule('default', [
            'var' => 'toOne',
        ]);
        $this->setRule('others', [
            'var:must' => 'toActive',
        ]);
    }
    public function toOne($v)
    {
        return 1;
    }

}
class F2 extends Formatter
{

    protected function setting()
    {
        $this->setRule('Closure', [
            'var' => function ($field) {return md5($field);},
        ]);
        $this->setRule('default_value_for_must', [
            'var: must' => 'toSelf',
        ]);
        $this->setRule('use_func', [
            'var' => 'toPNum',
        ]);
        $this->setRule('multi_params', [
            'var,var1' => 'strcat',
        ]);
        $this->setRule('none_method', [
            'var:must' => 'none_method',
        ]);

        $this->setRule('mode', [
            'var:must' => 'toPNum',
            'var1:may' => 'toPNum',
        ]);
        $this->setRule('may', [
            'var1:may' => 'toOne',
        ]);
    }

    public function toSelf($v)
    {
        return $v;
    }
    protected function strcat($a, $b)
    {
        return $a . $b;
    }
}
