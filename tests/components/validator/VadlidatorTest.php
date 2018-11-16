<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-11 19:46:53
 * @Modified time:      2018-11-13 20:23:11
 * @Depends on Linker:  Config
 * @Description:        对验证器进行测试
 */
namespace lin\tests\components\validator;

use Exception;
use Linker;
use lin\validator\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    protected $V1;
    protected $V2;

    public function setUp()
    {
        $this->V1 = new V1;
        $this->V2 = new V2;
    }

    public function testType()
    {
        //对may验证
        $r = $this->V1->withRule('may')->validate(['var1' => '']); //不存在不验证
        $this->assertTrue($r);
        $r = $this->V1->withRule('may')->validate(['var' => '']); //存在为空不验证
        $this->assertTrue($r);
        $r = $this->V1->withRule('may')->validate(['var' => 0]); //存在不为空验证
        $this->assertFalse($r);

        //对should验证
        $r = $this->V1->withRule('should')->validate(['var1' => '']); //不存在不验证
        $this->assertTrue($r);
        $r = $this->V1->withRule('should')->validate(['var' => '']); //存在为空验证
        $this->assertFalse($r);
        $r = $this->V1->withRule('should')->validate(['var' => 0]); //存在不为空验证
        $this->assertFalse($r);

        //对must验证
        $r = $this->V1->withRule('must')->validate(['var1' => '']); //不存在验证
        $this->assertFalse($r);
        $r = $this->V1->withRule('must')->validate(['var' => '']); //存在为空验证
        $this->assertFalse($r);
        $r = $this->V1->withRule('must')->validate(['var' => 0]); //存在不为空验证
        $this->assertFalse($r);

        //对默认方式为may验证
        $r = $this->V1->withRule('default')->validate(['var1' => '']); //不存在不验证
        $this->assertTrue($r);
        $r = $this->V1->withRule('default')->validate(['var' => '']); //存在为空不验证
        $this->assertTrue($r);
        $r = $this->V1->withRule('default')->validate(['var' => 0]); //存在不为空验证
        $this->assertFalse($r);
    }

    //对规则进行验证
    public function testRule()
    {
        Linker::Config()::lin(['validator.default.type' => 'must']); //更改为must模式
        $V = new V1;
        $r = $V->withRule('rule')->validate([]);
        $this->assertFalse($r);
        $msg = $V->getErrors();
        $this->assertSame(1, count($msg)); //严格模式下一个不通过就不再验证

        $r = $V->withRule('rule')->validate([], true);
        $this->assertFalse($r);
        $msg = $V->getErrors();
        $this->assertSame(4, count($msg)); //弱模式下一个不通过也要继续验证
        foreach ($msg as $field => $info) {
            $this->assertSame($field, $info); //验证错误信息
        }
        $this->assertSame('var1', $msg['var1']);

        Linker::Config()::lin(['validator.default.type' => 'may']); //更改为may模式
        $V = new V1;
        $r = $V->withRule('rule')->validate([]);
        $this->assertTrue($r);
        $msg = $V->getErrors();
        $this->assertSame(0, count($msg));

    }

    //测试常规情况
    public function testGeneral()
    {
        $data = [
            'var' => 2, 'znum' => 3,
        ];
        $r = $this->V1->withRule('mixed, must')->validate($data);
        $this->assertFalse($r);

        $data = [
            'big' => 2, 'small' => 1.1, 'eq1' => 1, 'eq2' => 1, 'no_need_to_validate' => '',
        ];
        $r = $this->V2->withRule('two_args')->validate($data);
        $this->assertTrue($r);
        $this->assertSame(0, count($this->V2->getErrors()));

        //多条规则
        $data = ['var' => 1, 'znum' => 12.2];
        $r    = $this->V1->withRule('must, mixed')->validate($data, true);
        $this->assertFalse($r);
        $this->assertSame(2, count($this->V1->getErrors()));
    }

    //测试规则和方法不存在时候抛出异常
    public function testException()
    {
        try {
            $this->V1->withRule('none')->validate([]);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $this->V2->withRule('none_method')->validate([]);
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

    }
}

class V1 extends Validator
{
    protected function setting()
    {
        $this->setRule('may', [
            'var:may' => 'failed',
        ]);
        $this->setRule('should', [
            'var: should' => 'failed',
        ]);
        $this->setRule('must', [
            'var: must' => 'failed',
        ]);
        $this->setRule('default', [
            'var' => 'failed',
        ]);
        $this->setRule('rule', [
            'var'  => 'failed', //规则的几种格式
            'var1' => ['failed', 'var1'],
            'var2' => function () {return false;},
            'var3' => [function () {return false;}, 'var3'],
        ]);

        $this->setRule('mixed', [
            'var'  => 'failed',
            'znum' => 'isZNUM',
        ]);
    }

    protected function failed($anything)
    {
        return false;
    }
}

class V2 extends Validator
{
    protected function setting()
    {
        $this->setRule('two_args', [
            'big, small' => 'gt',
            'eq1, eq2'   => function ($a, $b) {return $a === $b;},
        ]);
        $this->setRule('none_method', [
            'key:must' => 'none_exists_method',
        ]);
    }
}
