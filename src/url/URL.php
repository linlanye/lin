<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-12 10:03:46
 * @Modified time:      2018-09-04 11:13:47
 * @Depends on Linker:  Config
 * @Description:        生成完整的url地址
 */
namespace lin\url;

use Linker;

class URL
{
    private static $config;
    private static $domin; //域名
    private static $script; //当前脚本名
    private static $status = 0;

    //重新加载配置
    public static function reset(): bool
    {
        self::$status = 0;
        return true;
    }

    /**
     * 获得生成的url
     * @param  string $path   用户输入的标识字符串
     * @param  array  $params 生成get参数
     * @return string         生成的url
     */
    public static function get(string $path, array $params = null): string
    {
        self::init();
        $rule = self::$config['dynamic'];
        if (is_callable($rule['path'])) {
            $path = call_user_func_array($rule['path'], [self::$domin, $path, self::$script]);
        }
        //存在get参数
        if ($params && is_callable($rule['query'])) {
            $path .= call_user_func($rule['query'], $params); //get参数
        }
        return $path;
    }
    //获得生成的静态资源url
    public static function getStatic(string $path): string
    {
        self::init();
        $rule = self::$config['static'];
        if (is_callable($rule['path'])) {
            $path = call_user_func_array($rule['path'], [self::$domin, $path, self::$script]);
        }
        return $path;
    }

    //返回有效域名和脚本名参数
    public static function getParameters(): array
    {
        self::init();
        return ['domin' => self::$domin, 'script' => self::$script];
    }
    private static function init()
    {
        if (self::$status) {
            return;
        }
        self::$config = Linker::Config()::get('lin')['url'];
        $domin        = trim($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost'), '/');
        $script       = basename($_SERVER['SCRIPT_NAME']);
        $pos          = strrpos($_SERVER['PHP_SELF'], $script);
        $url          = trim(substr($_SERVER['PHP_SELF'], 0, $pos), '/');

        self::$domin  = trim("$domin/$url", '/'); //获得入口脚本名前的url,前后不携带'/'
        self::$script = trim($script, '/'); //前后不携带'/'
        self::$status = 1;
    }
}
