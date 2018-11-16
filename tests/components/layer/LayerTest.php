<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-11-01 09:51:24
 * @Modified time:      2018-11-01 11:09:38
 * @Depends on Linker:  None
 * @Description:        测试层组件
 */
namespace lin\tests\components\layer;

use Exception;
use lin\layer\Layer;
use lin\tests\components\Layer\classes\Layer2;
use PHPUnit\Framework\TestCase;

class LayerTest extends TestCase
{
    //测试访问层
    public function testLayer()
    {
        //测试更深层次的层访问
        $Layer1   = new Layer1;
        $data     = md5(mt_rand());
        $expected = $Layer1->testLayerMethod($data);
        $this->assertSame($expected, $data);

        //测试访问当前层
        $Layer2   = new Layer2;
        $Layer1   = $Layer2->testLayerMethod($data);
        $expected = $Layer2->testLayerMethod($data);
        $this->assertSame($expected, $data);
    }

    //测试访问块
    public function testBlock()
    {
        $Layer1 = new Layer1;
        $data   = md5(mt_rand());
        $Block  = $Layer1->testBlockMethod($data);
        $this->assertSame($Block->data, $data);

        $blockName = $Layer1->testBlockNameMethod();
        $this->assertSame('lin\\tests\\components\\layer\\classes\\Block', $blockName);
    }

    //测试流程控制
    public function testFlow()
    {
        //完整流程
        $data = md5(mt_rand());
        $Flow = (new Layer1)->testFlow($data);
        $this->assertSame($Flow->data, $data . '12');
        $this->assertSame($Flow->getDetails(), [
            ['lin\\tests\\components\\layer\\Layer1', 'flow1'],
            ['lin\\tests\\components\\layer\\classes\\Layer2', 'flow2'],
        ]);
        $this->assertTrue($Flow->isTerminal()); //已执行完

        //流程执行完后不可再执行
        $data = $Flow->data;
        $Flow = (new Layer1)->testFlow($Flow);
        $this->assertSame($Flow->data, $data); //数据未发生改变

        //中断流程
        $Flow->reset(); //重置使其可以继续执行
        $Flow = (new Layer1)->testFlowTerminal($Flow);
        $this->assertSame($Flow->data, $data . '1terminal');
        $this->assertSame($Flow->getDetails(), [
            ['lin\\tests\\components\\layer\\Layer1', 'flow1'],
            ['lin\\tests\\components\\layer\\Layer1', 'terminal'],
        ]);

    }

    public function testUse()
    {
        //使用全部模块
        $Layer2 = new Layer2;
        $utils  = ['SQL', 'Log', 'Queue', 'KV', 'Local', 'Response', 'Request'];
        $Layer2->testUse('*');
        foreach ($utils as $util) {
            $this->assertTrue(isset($Layer2->$util));
        }

        //使用指定模块
        $Layer2 = new Layer2;
        $Layer2->testUse('SQL');
        $this->assertTrue(isset($Layer2->SQL));
        $this->assertFalse(isset($Layer2->Log));

        //使用不存在模块
        $Layer2 = new Layer2;
        try {
            $Layer2->testUse('none');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

}

class Layer1 extends Layer
{
    public function data($data)
    {
        return $data;
    }
    public function testLayerMethod($data)
    {
        return self::layer('classes/Layer2')->data($data);
    }
    public function testBlockMethod($data)
    {
        return self::block('classes/Block', $data);
    }

    public function testBlockNameMethod()
    {
        return self::blockName('classes/Block');
    }
    //测试正常流程
    public function testFlow($data)
    {
        return self::flow([
            'Layer1.flow1',
            'classes/Layer2.flow2',
        ], $data);
    }

    //测试中断流程
    public function testFlowTerminal($data)
    {
        return self::flow([
            'Layer1.flow1',
            'Layer1.terminal',
            'classes/Layer2.flow2',
        ], $data);
    }

    public function flow1($Flow)
    {
        $Flow->data .= '1';
    }
    public function terminal($Flow)
    {
        $Flow->data .= 'terminal';
        $Flow->terminal();
    }

}
