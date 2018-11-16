<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-22 14:53:51
 * @Modified time:      2018-10-24 14:38:42
 * @Depends on Linker:  None
 * @Description:
 */
namespace lin\tests\components\orm;

use Exception;
use lin\orm\model\Model;
use lin\orm\model\structure\Params;
use PHPUnit\Framework\TestCase;

class RelationModelTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;

    public function setUp()
    {
        self::createDB('relation_model');
    }

    //测试关联参数
    public function testRelationParams()
    {
        $namespace = 'lin\\tests\\components\\orm\\';
        $Master    = $namespace . 'Master';
        $Slave     = $namespace . 'Slave';

        //手动设置参数
        new $Master;
        $params = Params::getRelation($Master, 'multi_key');
        $this->assertSame($params['mk'], ['mk1', 'mk2']);
        $this->assertSame($params['sk'], ['sk1', 'sk2']);
        $this->assertSame($params['class'], $Slave);
        $this->assertTrue($params['merge']);
        $this->assertSame(count($params), 5); //只有4个参数

        //自动设置参数
        new $Slave;
        $params = Params::getRelation($Slave, 'Slave');
        $this->assertSame($params['mk'], ['pk']);
        $this->assertSame($params['sk'], ['pk']);
        $this->assertSame($params['class'], $Slave);
        $this->assertSame(count($params), 3); //只有3个参数

        //错误参数
        try {
            Params::getRelation($Master, 'error_params');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * 测试模型句柄正确
     * @group database
     */
    public function testGetModel()
    {
        $Master             = new Master(['mk1' => mt_rand()]);
        $Slave              = new Slave();
        $Master->single_key = $Slave;
        $Master->withRelation('single_key')->insert();
        $this->assertSame($Master->getModel(), $Master);
    }

    /**
     * 测试关联读
     * @group database
     */
    public function testSelect()
    {
        //测试属性合并
        $Master = Master::withRelation('multi_key')->find(1);
        $this->assertTrue(!isset($Master['multi_key'])); //单记录属性合并
        $this->assertSame($Master['sk1'], $Master['mk1']);

        $Master = Master::withRelation('single_key')->find(1);
        $this->assertTrue(isset($Master['single_key'])); //多记录属性不合并
        $this->assertTrue(is_object($Master['single_key']));

        //多个关联属性
        $Master = Master::withRelation('single_key, multi_key')->find(1);
        $this->assertTrue(isset($Master['single_key']));
        $this->assertTrue(isset($Master['sk1']));

        //嵌套关联
        $Master = Master::withRelation('deep')->find(1);
        $this->assertTrue(isset($Master['deep']));
        $this->assertTrue(isset($Master['deep'][0]['master']));
    }

    /**
     * 测试关联插入
     * @group database
     */
    public function testInsert()
    {
        //普通关联插入
        $Master             = new Master(['mk1' => mt_rand()]);
        $Slave              = new Slave;
        $Master->single_key = $Slave;
        $this->assertTrue($Master->withRelation('single_key')->insert() > 0);
        $this->assertSame($Master->mk1, $Slave->sk1); //主从关联键一致
        $this->assertEquals(Master::where('mk1', $Master->mk1)->count(), 1); //记录存在
        $this->assertEquals(Slave::where('sk1', $Slave->sk1)->count(), 1);

        //深层关联插入
        $Master              = new Master(['placeholder' => mt_rand()]);
        $Slave               = new Slave;
        $Master->deep_insert = $Slave;
        $Slave->master       = new Master;
        $this->assertTrue($Master->withRelation('deep_insert')->insert() > 0);
        $this->assertSame($Master->pk, $Slave->sk1); //字段同步
        $this->assertSame($Slave->pk, $Slave->master->mk1); //字段同步
        $this->assertEquals(Master::where('pk', $Slave->master->pk)->count(), 1); //记录存在
        $this->assertEquals(Slave::where('pk', $Slave->pk)->count(), 1);
    }

    /**
     * 测试关联更新
     * @group database
     */
    public function testUpdate()
    {
        //普通更新
        $Master             = new Master(['pk' => 1, 'mk1' => 2]);
        $Slave              = new Slave(['pk' => 2, 'sk1' => 1]);
        $Master->single_key = $Slave;
        $this->assertTrue($Master->withRelation('single_key')->update() > 0);
        $this->assertSame($Master->mk1, $Slave->sk1); //主从关联键一致

        //深层更新
        $Master              = new Master(['pk' => 1, 'mk1' => 3]);
        $Slave               = new Slave(['pk' => 2, 'sk1' => 1]);
        $Slave->master       = new Master(['pk' => 3, 'mk1' => 2]);
        $Master->deep_update = $Slave;
        $this->assertTrue($Master->withRelation('deep_update')->update() > 0);
        $this->assertSame($Master->mk1, $Slave->sk1); //主从关联键一致
        $this->assertSame($Slave->master->mk1, $Slave->pk); //主从关联键一致
    }

    /**
     * 测试关联删除
     * @group database
     */
    public function testDelete()
    {
        //普通删除
        $Master             = new Master(['pk' => 1, 'mk1' => 2]);
        $Slave              = new Slave(['pk' => 2, 'sk1' => 1]); //主从关联可以不同步
        $Master->single_key = $Slave;
        $this->assertTrue($Master->withRelation('single_key')->delete() > 0);
        $this->assertFalse(isset($Master->pk)); //主键被消去
        $this->assertFalse(isset($Slave->pk));
        $this->assertNotSame($Master->mk1, $Slave->sk1); //主从关联可以不同步
        $this->assertEquals(Master::where('pk', 1)->count(), 0); //记录不存在
        $this->assertEquals(Slave::where('pk', 2)->count(), 0);

        //深层删除
        $Master              = new Master(['pk' => 2, 'mk1' => 2]);
        $Slave               = new Slave(['pk' => 1, 'sk1' => 1]); //主从关联可以不同步
        $Slave->master       = new Master(['pk' => 3, 'sk1' => 3]);
        $Master->deep_delete = $Slave;
        $this->assertTrue($Master->withRelation('deep_delete')->delete() > 0);

        $this->assertFalse(isset($Master->pk)); //主键被消去
        $this->assertFalse(isset($Slave->pk));
        $this->assertFalse(isset($Slave->master->pk));
        $this->assertNotSame($Master->mk1, $Slave->sk1);

        $this->assertEquals(Master::where('pk', 2)->count(), 0); //记录不存在
        $this->assertEquals(Slave::where('pk', 1)->count(), 0);
        $this->assertEquals(Master::where('pk', 3)->count(), 0);
    }

    /**
     * 测试混合操作，with参数一次性使用和备份还原
     * @group database
     */
    public function testOthers()
    {
        //测测with方法只对当前模型有效
        $Master             = new Master(['mk1' => 1]);
        $Slave              = new Slave(['sk1' => 1]); //主从关联可以不同步
        $Master->single_key = $Slave;
        $this->assertTrue($Master->withRelation('single_key')->withFormatter('write')->insert() > 0);
        $this->assertEquals(Master::where('mk1', md5(1))->count(), 1); //记录存在
        $this->assertEquals(Slave::where('sk1', md5(1))->count(), 0); //记录不存在

        //测试混合关联insert+update
        $Master                = new Master(['mk1' => 1]);
        $Slave                 = new Slave(['pk' => 1, 'sk1' => 2]); //主从关联可以不同步
        $Master->insert_update = $Slave;
        $this->assertTrue($Master->withRelation('insert_update')->insert() > 0);
        $this->assertSame($Master->mk1, $Slave->sk1);

        //测试失败后恢复
        $Master                   = new Master([['mk1' => 1], ['mk1' => 2]]);
        $Slave                    = new Slave([['pk' => 1], ['pk' => 2]]); //主从关联可以不同步
        $Master[0]->insert_delete = $Slave[0];
        $Master[1]->insert_delete = $Slave[1];

        $backup = serialize($Master);
        $backup = unserialize($backup)->toArray();
        $Master->setStrictTrans()->withRelation('insert_delete')->insert();
        $this->assertSame($Master->toArray(), $backup);
    }

}
class Master extends Model
{
    protected function setting()
    {
        $this->setPK('pk');

        //单主从键
        $this->setRelation('single_key', [
            'class' => 'Slave',
            'mk'    => 'mk1',
            'sk'    => 'sk1',
        ]);
        $this->setRelation('multi_key', [
            'class'  => 'Slave',
            'mk'     => 'mk1,mk2',
            'sk'     => 'sk1, sk2',
            'merge'  => true,
            'select' => function ($Query, $mks) {
                return $Query->where(['sk1' => $mks['mk1'], 'sk2' => $mks['mk2']])->one('*');
            },
        ]);
        $this->setRelation('deep', [ //多层关联
            'class'  => 'Slave',
            'mk'     => 'mk1',
            'sk'     => 'sk1',
            'select' => function ($Query, $mks) {
                return $Query->withRelation('master')->where('sk1', $mks['mk1'])->select('*');
            },
        ]);
        $this->setRelation('error_params', [
            'class' => 'Slave',
            'mk'    => 'mk1',
            'sk'    => 'sk1, sk2',
        ]);

        $this->setRelation('deep_insert', [
            'class'  => 'Slave',
            'mk'     => 'pk',
            'sk'     => 'sk1',
            'insert' => function ($Query, $mks) {
                return $Query->withRelation('master')->insert();
            },
        ]);
        $this->setRelation('deep_update', [
            'class'  => 'Slave',
            'mk'     => 'mk1',
            'sk'     => 'sk1',
            'update' => function ($Query, $mks) {
                return $Query->withRelation('master')->update();
            },
        ]);
        $this->setRelation('deep_delete', [
            'class'  => 'Slave',
            'mk'     => 'mk1',
            'sk'     => 'sk1',
            'delete' => function ($Query, $mks) {
                return $Query->withRelation('master')->delete();
            },
        ]);
        $this->setRelation('insert_update', [
            'class'  => 'Slave',
            'mk'     => 'mk1',
            'sk'     => 'sk1',
            'insert' => function ($Query, $mks) {
                return $Query->update();
            },
        ]);

        $this->setRelation('insert_delete', [
            'class'  => 'Slave',
            'mk'     => 'mk1',
            'sk'     => 'sk1',
            'insert' => function ($Query, $mks) {
                $Query->delete();
                //sqlite对未操作成功的行也返回1,人为制造准确结果
                if (current($mks) == 2) {
                    return 0;
                }
                return 1;
            },
        ]);

        $this->setFormatter('write', function ($data) {
            if (isset($data['mk1'])) {
                $data['mk1'] = md5($data['mk1']);
            } else if (isset($data['sk1'])) {
                $data['sk1'] = md5($data['sk1']);
            }
            return $data;
        });

    }
}
class Slave extends Model
{
    protected function setting()
    {
        $this->setPK('pk');
        $this->setRelation('master', [
            'class' => 'Master',
            'sk'    => 'mk1',
        ]);

    }
}
