<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-11-01 13:16:54
 * @Modified time:      2018-09-28 15:45:33
 * @Depends on Linker:  Config
 * @Description:        提供一系列安全相关功能的整合，如登录、授权、认证、权限访问、单点登录、oauth2、防csrf、重复提交、验证码等等
 */
namespace lin\security;

use Linker;
use lin\security\structure\Creator;
use lin\security\structure\Debug;
use lin\security\structure\Params;

class Security
{
    const VALID     = 0; //检查通过
    const UNCHECKED = 1; //未检查
    const NONE      = 2; //场景不存在
    const FAILED    = 4; //场景检查失败
    const EXPIRED   = 8; //场景已过期
    const INVALID   = 15; //场景无效，包含上诉4种情况

    private static $status = 0;
    private $id; //检查的客户端id，null时为临时客户端
    private $code; //检查结果代码
    private $Debug;
    //获得场景构建器
    public static function __callStatic($method, $args)
    {
        $Creator = new Creator;
        return call_user_func_array([$Creator, $method], $args);
    }

    public function __construct(string $id = null)
    {
        $this->id   = $id;
        $this->code = self::UNCHECKED;
        if (Linker::Config()::get('lin')['security']['debug']) {
            $this->Debug = new Debug;
        }
    }

    /**
     * 检查目标场景的token是否正确
     * @param  string $scenario 目标场景
     * @param  string $token    待校验的token
     * @return bool 检查是否通过
     */
    public function check(string $scenario, string $token): bool
    {
        $t    = microtime(true);
        $data = Params::get($this->id, $scenario);
        $this->handle($token, $data);
        if ($this->Debug) {
            $this->Debug->check($this->id, $scenario, $this->code, microtime(true) - $t);
        }
        return $this->code === self::VALID;
    }

    //获得检查结果状态码
    public function getStatus(): int
    {
        return $this->code;
    }

    //检查
    private function handle($token, $data)
    {
        //是否不存在
        if ($data === null) {
            $this->code = self::NONE;
            return;
        }
        //是否过期
        if ($data[2] > 0 && $data[1] + $data[2] < time()) {
            $this->code = self::EXPIRED;
            return;
        }
        //是否正确
        if (!password_verify($token, $data[0])) {
            $this->code = self::FAILED;
            return;
        }

        $this->code = self::VALID;
    }
}
