<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-28 10:55:55
 * @Modified time:      2018-09-28 15:46:20
 * @Depends on Linker:  None
 * @Description:
 */
namespace lin\tests\components;

use Exception;
use Linker;
use lin\security\Security;
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    use \lin\tests\traits\CreateDBTrait;

    private $Security;
    public function setUp()
    {
        $this->Security = new Security(1);
    }
    public static function setUpBeforeClass()
    {
        self::createDB('security');
    }

    public function testBuild()
    {
        try {
            Security::build(md5(mt_rand())); //未指定id，为临时客户端，会写header
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        try {
            Security::byImage()->withID(1)->build(md5(mt_rand())); //输出验证码，会写header
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        $this->assertNotEmpty(Security::withID(1)->build(md5(mt_rand()), 1, 1)); //未指定token
        $token  = md5(mt_rand());
        $_token = Security::withToken($token)->withID(1)->build(md5(mt_rand()), 1, 1); //指定token
        $this->assertSame($token, $_token);
    }
    /**
     * @group sleep
     */
    public function testCheck()
    {
        $scenario = md5(mt_rand());
        $token    = Security::withID(1)->build($scenario, 1);
        $r        = $this->Security->check($scenario, $token);
        $this->assertTrue($r);
        $this->assertSame(Security::VALID, $this->Security->getStatus()); //测试token正确
        $this->assertTrue((Security::INVALID & $this->Security->getStatus()) == 0);

        $r = $this->Security->check($scenario, md5(mt_rand()));
        $this->assertFalse($r);
        $this->assertSame(Security::FAILED, $this->Security->getStatus()); //测试token错误
        $this->assertTrue((Security::INVALID & $this->Security->getStatus()) > 0);

        sleep(2);
        $r = $this->Security->check($scenario, $token);
        $this->assertFalse($r);
        $this->assertSame(Security::EXPIRED, $this->Security->getStatus()); //测试token时效
        $this->assertTrue((Security::INVALID & $this->Security->getStatus()) > 0);

        $r = $this->Security->check('none', $token);
        $this->assertFalse($r);
        $this->assertSame(Security::NONE, $this->Security->getStatus()); //测试场景不存在
        $this->assertTrue((Security::INVALID & $this->Security->getStatus()) > 0);

        //测试默认生命期
        $token = Security::withID(1)->build($scenario);
        $r     = $this->Security->check($scenario, $token);
        $this->assertTrue($r);
        sleep(2);
        $r = $this->Security->check($scenario, $token);
        $this->assertFalse($r);

    }
    //测试销毁场景
    public function testDestroy()
    {
        $scenario = md5(mt_rand());
        $token    = Security::withID(1)->build($scenario, 10);
        $r        = $this->Security->check($scenario, $token);
        $this->assertTrue($r);
        Security::withID(1)->destroy($scenario);
        $r = $this->Security->check($scenario, $token);
        $this->assertFalse($r);
        $this->assertSame(Security::NONE, $this->Security->getStatus()); //测试场景已被删除

        $scenario2 = md5(mt_rand());
        $token     = Security::withID(1)->build($scenario, 10);
        $token2    = Security::withID(1)->build($scenario2, 10);
        Security::withID(1)->destroy('*');

        $r = $this->Security->check($scenario, $token);
        $this->assertFalse($r);
        $this->assertSame(Security::NONE, $this->Security->getStatus()); //测试场景已被删除

        $r = $this->Security->check($scenario2, $token2);
        $this->assertFalse($r);
        $this->assertSame(Security::NONE, $this->Security->getStatus()); //测试场景已被删除
    }

}
