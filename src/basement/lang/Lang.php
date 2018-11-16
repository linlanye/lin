<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-20 11:53:48
 * @Modified time:      2018-11-06 22:41:40
 * @Depends on Linker:  Config
 * @Description:        语言类，提供语言映射
 */
namespace lin\basement\lang;

use Closure;
use Linker;

class Lang
{
    /*****basement*****/
    use \basement\Lang;

    /**
     * 获取当前源字符集的标识名
     * @return string 源字符集的标识名
     */
    public function getLabel(): string
    {
        return $this->__label;
    }

    /**
     * 映射源字符为目标字符
     * @param  string $chars 源字符
     * @return string        目标语言字符
     */
    public function map(string $chars): string
    {
        //无目标语言时，返回自身
        if (self::$__i18n) {
            //自动加载
            if (!isset(self::$data[self::$__i18n][$this->__label])) {
                if (!isset(self::$data[self::$__i18n])) {
                    self::$data[self::$__i18n] = [];
                }
                self::$data[self::$__i18n][$this->__label] = call_user_func_array(self::$autoload, [$this->__label, self::$__i18n]);
            }

            //是否存在目标字符
            if (isset(self::$data[self::$__i18n][$this->__label][$chars])) {
                $chars = self::$data[self::$__i18n][$this->__label][$chars];
            } elseif (is_callable($this->map)) {
                $chars = call_user_func($this->map, $chars);
            }
        }

        return $chars;
    }

    /**
     * 设置全局目标语言，使得被包裹的源字符可转换为目标语言字符
     * @param  string $i18n 各国语言缩写代码，需存在于self::$__i18nLists中
     * @return bool         是否设置成功
     */
    public static function i18n(string $i18n): bool
    {
        if (in_array($i18n, self::$__i18nLists)) {
            self::$__i18n = $i18n;
            return true;
        }
        return false;
    }
    public static function autoload(Closure $Closure): bool
    {
        self::$autoload = $Closure;
        return true;
    }
    /******************/

    private static $data = [];
    private static $autoload;
    private $map;

    public function __construct(string $label = '')
    {
        $config = Linker::Config()::get('lin')['lang']['default'];
        if (!self::$autoload) {
            self::$autoload = $config['autoload'];
        }
        if (!self::$__i18n) {
            self::$__i18n = $config['i18n'];
        }
        $this->map     = $config['map'];
        $this->__label = $label ?: $config['label'];
    }
    public static function clean(string $i18n = ''): bool
    {
        if ($i18n) {
            unset(self::$data[$i18n]);
        } else {
            self::$data = [];
        }
        return true;
    }

    public static function reset(): bool
    {
        self::$data     = [];
        self::$autoload = self::$__i18n = null;
        return true;
    }
}
