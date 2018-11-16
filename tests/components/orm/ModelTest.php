<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-10 10:48:29
 * @Modified time:      2018-10-24 10:08:19
 * @Depends on Linker:  None
 * @Description:        测试模型基础功能
 */
namespace lin\tests\components\orm;

use Exception;
use lin\orm\model\Model;
use lin\orm\model\structure\Params;
use PHPUnit\Framework\TestCase;
use stdclass;

class ModelTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;

    public function setUp()
    {
        self::createDB('model');
    }

    //测试外部指定驱动
    public function testSetDriver()
    {
        $Driver = new stdclass;
        $Users  = Users::setDriver($Driver);
        $this->assertSame($Users->getDriver(), $Driver);

        $Users = new Users;
        $Users = $Users->setDriver($Driver);
        $this->assertSame($Users->getDriver(), $Driver);
    }

    //测试表名和主键
    public function testTableAndPK()
    {
        $Users   = 'lin\\tests\\components\\orm\\Users';
        $Address = 'lin\\tests\\components\\orm\\Address';
        new $Users;
        new $Address;
        $this->assertSame(Params::get($Users)['table'], 'users'); //自动设置
        $this->assertSame(Params::get($Users)['pk'], ['user_id']);
        $this->assertSame(Params::get($Address)['table'], 'area'); //手动设置
        $this->assertSame(Params::get($Address)['pk'], ['city', 'country']);
    }

    public function testAttr()
    {
        //两种不同赋值形式，单记录
        $info        = md5(mt_rand());
        $Users       = new Users;
        $Users->info = $info;
        $this->assertSame($Users->info, $info);
        $Address = new Address(['city' => 'Yaan', 'country' => 'China']);
        $this->assertSame($Address->city, 'Yaan');
        $this->assertSame($Address->country, 'China');
        $this->assertFalse($Address->isMulti());

        //多记录
        $Child          = new Users;
        $Users          = new Users([$Child]);
        $Users[0]->info = $info;
        $this->assertSame($Users->{0}['info'], $info); //对象和数组形式混合访问
        $this->assertTrue($Users[0] instanceof Users); //类型一致
        $this->assertSame($Child->getParent(), $Users);
        $this->assertNull($Users->getParent());

        $Address = new Address([['city' => 'Yaan', 'country' => 'China'], new Address]); //混合型
        $this->assertSame($Address[0]['city'], 'Yaan'); //数组形式访问
        $this->assertSame($Address[0]->country, 'China'); //数组和对象形式混合访问
        $this->assertSame($Address->{0}->country, 'China'); //对象形式访问
        $this->assertTrue($Address[0] instanceof Address); //类型一致
        $this->assertTrue($Address->isMulti());
        $this->assertSame($Address[0]->getParent(), $Address);
        $this->assertSame($Address[1]->getParent(), $Address);
        $this->assertNull($Address->getParent());
    }

    /**
     * 测试基本读
     * @group database
     */
    public function testRead()
    {
        //读
        $Users = Users::select();
        $this->assertEquals(count($Users), 2);
        $this->assertEquals($Users->{0}->user_id, 1);
        $this->assertTrue($Users->{0} instanceof Users); //类型一致

        $User = Users::find(1);
        $this->assertEquals($User->user_id, 1);
        $Address = Address::find('Yaan', 'China');
        $this->assertEquals($Address->city, 'Yaan');
        $this->assertEquals($Address->country, 'China');
    }

    /**
     * 测试基本写
     * @group database
     */
    public function testWrite()
    {
        $Users          = Users::select('*');
        $Users[0]->info = md5(mt_rand());
        $this->assertTrue($Users->update() > 0); //存在影响记录
        $this->assertSame($Users->{0}->info, Users::select('*')[0]->info); //更新后一致

        $this->assertSame($Users->delete(), 2);
        $this->assertNull(Users::select()); //删除后一致
        foreach ($Users as $v) {
            $this->assertFalse(isset($v->user_id)); //删除后主键不存在
        }

        $this->assertSame($Users->insert(), 2); //插入后一致
        $this->assertEquals(count(Users::select()), 2); //插入后一致
        foreach ($Users as $v) {
            $this->assertTrue(isset($v->user_id)); //插入后主键存在
        }

        //无主键更新和删除报错
        $Users = new Users;
        try {
            $Users->update();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $Users->delete();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        //空更新
        try {
            $Users->user_id = 1;
            $Users->update();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        //空操作
        try {
            $Users = new Users;
            $Users->insert();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * 测试宏
     * @group database
     */
    public function testMacro()
    {
        //读宏
        $Users  = Users::onlyOne();
        $Users_ = Users::limit(1)->select('*');
        $this->assertSame($Users->toArray(), $Users_->toArray()); //转化为数组一致
        $this->assertSame($Users[0]->info, $Users_->{0}->info); //访问形式一致

        //写宏
        $this->assertNull(Users::where('info:isNull')->select());
        $Users->softDelete(); //软删除后
        $this->assertEquals(Users::where('info:isNull')->count(), 1);
        $info = md5(mt_rand());

        $Users->{0}->info = $info;
        $Users->update(); //恢复
        $this->assertEquals(Users::where('info', $info)->count(), 1);

        //原方法映射为宏
        $Address = Address::one();
        $this->assertTrue($Address->delete() > 0); //执行宏删除实则为更新

        $this->assertEquals(count(Address::select()), 1);
        $this->assertEquals(Address::where('status', 1)->count(), 0);
        $Address->status = 1;
        $this->assertTrue($Address->update() > 0); //恢复
        $this->assertEquals(Address::where('status', 1)->count(), 1);

        //不使用宏
        $this->assertTrue($Address->withoutMacro('delete')->delete() > 0);
        $this->assertNull(Address::select());

        //一次调用只能使用一次
        try {
            $Users->withoutMacro('softDelete')->softDelete();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $Users->softDelete();
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->assertTrue(false);
        }
    }

    /**
     * 测试格式化
     * @group database
     */
    public function testFormatter()
    {
        $Users            = new Users;
        $Users->serialize = [mt_rand()];
        $Users->user_id   = 1;
        $Users->withFormatter('write')->update(); //入库格式化

        // Users::wherePK([],[],[]);
        $Users_ = Users::where('user_id', 1)->withFormatter('read')->one('*'); //出库格式化
        $this->assertSame($Users->serialize, $Users_->serialize);

        //不使用格式化
        $Users_ = Users::where('user_id', 1)->one('*'); //关闭读取时
        $this->assertNotSame($Users->serialize, $Users_->serialize);

        //写入时不使用
        try {
            $Users            = new Users;
            $Users->serialize = [mt_rand()];
            $Users->user_id   = 1;
            $Users->update(); //写入数组抛出异常
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        //同时不使用
        $Users_ = Users::where('user_id', 1)->one('*'); //读写不使用
        $this->assertNotSame($Users->serialize, $Users_->serialize);
        try {
            $Users_->serialize = [mt_rand()];
            $Users_->update; //写入数组抛出异常
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        //一次调用只能使用一次
        try {
            $Users->withFormatter('write')->update();
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->assertTrue(false);
        }
        try {
            $Users->update();
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

}

class Users extends Model
{

    protected function setting()
    {
        //设置读写宏
        $this->setMacro('onlyOne', function ($Query) {
            return $Query->limit(1)->select('*');
        });
        $this->setMacro('softDelete', function ($Query) {
            return $Query->update('info', null);
        });

        //设置格式化
        $this->setFormatter('write', function ($Model) {
            if (!empty($Model['serialize'])) {
                $Model['serialize'] = serialize($Model['serialize']);
            }
            return $Model;
        });
        $this->setFormatter('read', function ($Model) {
            if (!empty($Model['serialize'])) {
                $Model['serialize'] = unserialize($Model['serialize']);
            }
            return $Model;
        });
    }

}
class Address extends Model
{

    protected function setting()
    {
        //设置表名和多主键
        $this->setTable('area')->setPK('city, country');

        //设置读写宏，覆盖原有方法
        $this->setMacro('delete', function ($Query) {
            return $Query->update('status', 0); //补充数据不对模型属性值产生影响
        });
        $this->setMacro('', function ($Query) {
            return $Query->update('status', 0); //补充数据不对模型属性值产生影响
        });
    }

}
