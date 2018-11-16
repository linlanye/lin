<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-04-24 14:36:59
 * @Modified time:      2018-09-28 17:23:34
 * @Depends on Linker:  Config
 * @Description:        安全场景创建器
 */
namespace lin\security\structure;

use Linker;
use lin\security\structure\captcha\Img;
use lin\security\structure\Params;

class Creator
{
    private $id; //客户端id, null时为临时客户端
    private $token; //指定创建的token
    private $config;
    private $Img; //图片验证码

    private $Debug;
    public function __construct()
    {
        $config = Linker::Config()::get('lin')['security'];
        if ($config['debug']) {
            $this->Debug = new Debug;
        }
        $this->config = $config['default'];
    }

    /**
     * 创建场景
     * @param  string $scenario 场景名
     * @param  int    $life     时效
     * @param  int    $cost     安全保存token的性能耗费，见password_hash
     * @return string|null      成功后返回设置的token，否则返回null
     */
    public function build(string $scenario, int $life = null, int $cost = null):  ? string
    {
        $t = microtime(true);
        //获得token
        if ($this->Img) {
            $token = $this->Img->show($this->token);
        } else {
            if ($this->token) {
                $token = $this->token;
            } else {
                $token = $this->createToken();
            }
        }

        //默认时效
        if ($life === null) {
            $life = $this->config['life'];
        }
        $cost              = $cost === null ? $this->config['cost'] : $cost;
        $cost < 4 && $cost = 4; //cost最小为4
        $_token            = password_hash($token, PASSWORD_BCRYPT, ['cost' => $cost]); //实际存储的token
        //记录数据并重置当前状态
        if ($_token === false) {
            $token = null;
        } else {
            Params::set($this->id, [$scenario => [$_token, time(), $life]]); //life为0则永不过期
        }
        if ($this->Debug) {
            $this->Debug->build($this->id, $scenario, $token, $life, microtime(true) - $t);
        }
        $this->reset();
        return $token;
    }

    //销毁场景
    public function destroy(string $scenario) : bool
    {
        $r = Params::delete($this->id, $scenario);
        $this->reset();
        return $r;
    }

    //指定客户端id，未调用则为临时客户端
    public function withID(string $id): object
    {
        $this->id = $id;
        return $this;
    }
    //使用指定token
    public function withToken(string $token): object
    {
        $this->token = $token;
        return $this;
    }
    //通过图片输出
    public function byImage(array $config = []): object
    {
        $this->Img = new Img($config);
        return $this;
    }

    private function reset()
    {
        $this->Img = $this->id = $this->token = null;
    }

    //创建token，未指定时用
    private function createToken()
    {
        $token = hash('sha256', mt_rand() . '-' . uniqid(mt_rand(), true) . '-' . mt_rand(), true); //二进制数据

        //将token转为转换为64进制压缩存储
        $sym = '0123456789-abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = 32; //strlen($token);
        $n   = 11; //ceil($len / 3); //64进制需要6个位表示，3个字节24位刚好可以完整表示4次，
        $token .= ord(0);
        // $rest = $len % 3;
        // while ($rest--) {
        //     $token .= ord(0); //补字节为3的倍数
        // }

        $output = '';
        $mask   = 63;
        for ($i = 0; $i < $n; $i++) {
            $c1 = ord($token[$i * 3]); //第一个字节
            $c2 = ord($token[$i * 3 + 1]); //第二个字节
            $c3 = ord($token[$i * 3 + 2]); //第三个字节

            $a = $c1 & $mask;
            $output .= $sym[$a]; //c1前6位

            $a = ($c1 >> 6 | $c2 << 2) & $mask; //c1后2位+c2前4位
            $output .= $sym[$a];

            $a = ($c2 >> 4 | $c3 << 4) & $mask; //c2后4位+c3前2位
            $output .= $sym[$a];

            $a = $c3 >> 2 & $mask; //c3后6位
            $output .= $sym[$a];
        }

        return $output;
    }

}
