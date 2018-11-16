<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-20 11:53:48
 * @Modified time:      2018-11-08 10:08:41
 * @Depends on Linker:  Debug
 * @Description:        配置类，可链式读取和设置
 */

namespace lin\basement\config;

use Linker;

class Config
{
    use \basement\Config;
    /*****basement*****/
    /**
     * 读取配置内容
     * @param  string $configName 配置名
     * @return array|null        读取失败时返回null
     */
    public static function get(string $configName):  ? array
    {
        return self::$data[$configName] ?? null;
    }
    public static function set(string $configName, array $content) : bool
    {
        self::$data[$configName] = $content;
        if (isset(self::$data['lin']['config']['debug'])) {
            $Debug = Linker::Debug(true);
            $Debug->setName('CONFIG');
            $Debug->append('配置明细', $configName);
        }
        return true;
    }
    //配置文件是否已读取
    public static function exists(string $configName): bool
    {
        return array_key_exists($configName, self::$data);
    }
    /******************/

    private static $path; //配置文件路径，用于动态加载配置
    private static $data = [];
    //获取或设置配置文件，获取型如：Config::someConfig('key.key2')； 设置类型如config::someConfig(['key.key2'=>'value'])
    public static function __callStatic($configName, $arg)
    {
        $arg = $arg[0] ?? null; //只接受单参数;

        //写设置，支持批量设置
        if (is_array($arg)) {
            if (!isset(self::$data[$configName])) {
                self::$data[$configName] = [];
            }
            foreach ($arg as $keys => $value) {
                $keys = explode('.', $keys);
                $ref  = &self::$data[$configName];
                foreach ($keys as $key) {
                    if (!isset($ref[$key])) {
                        if (is_array($ref)) {
                            $ref[$key] = [];
                        } else {
                            $ref = [$key => []]; //非数组情况下用数组替代覆写原数据
                        }
                    }
                    $ref = &$ref[$key];
                }
                $ref = $value;
            }
            return true;
        }

        //读取设置，检查是否载入配置文件，没用则直接从配置文件夹加载
        if (!self::exists($configName)) {
            if (!self::$path && self::exists('lin')) {
                self::$path = self::$data['lin']['config']['path']; //未设置全局路径时，加载lin配置文件的全局路径
            }
            $data                    = @include self::$path . $configName . '.php';
            self::$data[$configName] = $data ?: [];
        }
        if (strlen($arg) === 0) {
            return self::$data[$configName]; //没有设置获得具体键，则返回所有
        }
        $keys = explode('.', $arg);
        $conf = self::$data[$configName];
        foreach ($keys as $key) {
            if (isset($conf[$key])) {
                $conf = $conf[$key];
            } else {
                return null;
            }
        }
        return $conf;

    }
    //清空配置数据
    public static function clean(string $configName = ''): bool
    {
        if ($configName) {
            unset(self::$data[$configName]);
        } else {
            self::$data = []; //清空所有
        }
        return true;
    }
    public static function reset(): bool
    {
        self::$data = [];
        self::$path = null;
        return true;
    }
}
