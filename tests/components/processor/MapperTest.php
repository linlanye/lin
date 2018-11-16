<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-19 15:57:51
 * @Modified time:      2018-09-19 21:17:57
 * @Depends on Linker:  None
 * @Description:        测试映射器
 */
namespace lin\tests\components\processor;

use Exception;
use lin\processor\Mapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    protected $M1;
    protected $M2;

    public function setUp()
    {
        $this->M1 = new M1;
        $this->M2 = new M2;
    }

    public function testType()
    {
        //对may进行验证
        $r = $this->M1->withRule('may')->map([]); //不存在映射
        $this->assertTrue(empty($r));

        $r = $this->M1->withRule('may')->map(['origin' => '']); //存在为空映射
        $this->assertSame($r['origin'], '');
        $this->assertSame(count($r), 1);

        $r = $this->M1->withRule('may')->map(['origin' => 1]); //存在不为空映射
        $this->assertSame($r['target'], 1);
        $this->assertSame(count($r), 1);

        //对should进行验证
        $r = $this->M1->withRule('should')->map([]); //不存在映射
        $this->assertTrue(empty($r));

        $r = $this->M1->withRule('should')->map(['origin' => '']); //存在为空映射
        $this->assertSame($r['target'], '');
        $this->assertSame(count($r), 1);

        $r = $this->M1->withRule('should')->map(['origin' => 1]); //存在不为空映射
        $this->assertSame($r['target'], 1);
        $this->assertSame(count($r), 1);

        //对must进行验证
        $r = $this->M1->withRule('must')->map([]); //不存在映射
        $this->assertSame($r['target'], 'origin');
        $this->assertSame(count($r), 1);

        $r = $this->M1->withRule('must')->map(['origin' => '']); //存在为空映射
        $this->assertSame($r['target'], '');
        $this->assertSame(count($r), 1);

        $r = $this->M1->withRule('must')->map(['origin' => 1]); //存在不为空映射
        $this->assertSame($r['target'], 1);
        $this->assertSame(count($r), 1);

        //对默认(should)进行验证
        $r = $this->M1->withRule('should')->map([]); //不存在映射
        $this->assertTrue(empty($r));

        $r = $this->M1->withRule('should')->map(['origin' => '']); //存在为空映射
        $this->assertSame($r['target'], '');
        $this->assertSame(count($r), 1);

        $r = $this->M1->withRule('should')->map(['origin' => 1]); //存在不为空映射
        $this->assertSame($r['target'], 1);
        $this->assertSame(count($r), 1);

    }

    //测试规则
    public function testRule()
    {
        $data = [
            'a' => md5(mt_rand()),
        ];
        //单层映射
        $r = $this->M2->withRule('single_multi')->map($data);
        $this->assertSame($r['t'], $data['a']);
        $this->assertSame($r['t1'], $data['a']);
        $this->assertSame(count($r), 2);

        $r = $this->M2->withRule('single_deep')->map($data);
        $this->assertSame($r['t']['t1'], $data['a']);
        $this->assertSame(count($r), 1);

        $r = $this->M2->withRule('single_deep_multi')->map($data);
        $this->assertSame($r['t']['t1'], $data['a']);
        $this->assertSame($r['t2']['t3'], $data['a']);
        $this->assertSame(count($r), 2);

        //多层映射
        $data = [
            'a' => ['b' => md5(mt_rand())],
            'c' => ['d' => md5(mt_rand())],
        ];
        $r = $this->M2->withRule('deep_single')->map($data);
        $this->assertSame($r['t'], $data['a']['b']);
        $this->assertSame($r['t2'], $data['c']['d']);
        $this->assertSame($r['t3'], $data['c']['d']);
        $this->assertSame(count($r), 3);

        $r = $this->M2->withRule('deep_multi')->map($data);
        $this->assertSame($r['t']['t1'], $data['a']['b']);
        $this->assertSame($r['t2']['t3'], $data['c']['d']);
        $this->assertSame($r['t4']['t5'], $data['c']['d']);
        $this->assertSame(count($r), 3);

        //同时使用多个规则
        $r = $this->M1->withRule('must, others')->map([]);
        $this->assertSame(count($r), 2);
    }

    //测试两种模式
    public function testMode()
    {
        $data = [
            'origin'  => 1,
            'origin2' => 2,
        ];
        //保留所有数据
        $r = $this->M1->withRule('should')->map($data);
        $this->assertSame($r['target'], 1);
        $this->assertSame($r['origin2'], 2);
        $this->assertSame(count($r), 2);

        //只保留映射数据
        $r = $this->M1->withRule('should')->map($data, true);
        $this->assertSame($r['target'], 1);
        $this->assertSame(count($r), 1);
    }

    //测试规则和方法不存在时候抛出异常
    public function testException()
    {
        try {
            $this->M1->withRule('none')->map([]); //规则不存在
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $this->M2->withRule('must')->map([]); //测试不同类的规则互不影响
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

}

class M1 extends Mapper
{
    protected function setting()
    {
        $this->setRule('may', [
            'origin: may' => 'target',
        ]);
        $this->setRule('should', [
            'origin:should' => 'target',
        ]);
        $this->setRule('must', [
            'origin:must' => 'target',
        ]);
        $this->setRule('default', [
            'origin' => 'target',
        ]);
        $this->setRule('others', [
            'origin:must' => 'target1',
        ]);
    }
}
class M2 extends Mapper
{
    protected function setting()
    {
        //单层映射
        $this->setRule('single_multi', [
            'a' => 't,t1', //映射多个
        ]);
        $this->setRule('single_deep', [
            'a' => 't.t1', //映射单个多层
        ]);
        $this->setRule('single_deep_multi', [
            'a' => 't.t1, t2.t3', //映射多个多层
        ]);

        //多层映射
        $this->setRule('deep_single', [
            'a.b' => 't',
            'c.d' => 't2, t3',
        ]);
        $this->setRule('deep_multi', [
            'a.b' => 't.t1',
            'c.d' => 't2.t3, t4.t5',
        ]);

        //测试不同的类规则不会互相污染
        $this->setRule('may', [
            'origin1' => 'target1',
        ]);

    }
}
