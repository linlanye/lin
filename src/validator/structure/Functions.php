<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-09-14 19:57:51
 * @Modified time:      2018-09-13 16:22:12
 * @Depends on Linker:  none
 * @Description:        验证函数类，提供常见的验证方法
 */
namespace lin\validator\structure;

class Functions
{
    /******数据类型******/

    public static function isInt($v): bool
    {
        return is_int($v);
    }
    public static function isFloat($v): bool
    {
        return is_float($v);
    }
    public static function isNumeric($v): bool
    {
        return is_numeric($v);
    }
    public static function isBool($v): bool
    {
        return is_bool($v);
    }
    public static function isString($v): bool
    {
        return is_string($v);
    }
    public static function isNull($v): bool
    {
        return is_null($v);
    }
    public static function isScalar($v): bool
    {
        return is_scalar($v);
    }
    public static function isArray($v): bool
    {
        return is_array($v);
    }
    public static function isObject($v): bool
    {
        return is_object($v);
    }
    public static function isResource($v): bool
    {
        return is_resource($v);
    }
    /********************/

    /****常见数字类型****/
    //整数
    public static function isZNum($v): bool
    {
        if (!is_numeric($v)) {
            return false;
        }
        if (is_int($v)) {
            return true;
        }
        if (ceil($v) == floor($v)) {
            return true; //对数字字符串或浮点型进行判断
        }
        return false;
    }
    //自然数
    public static function isNatNum($v): bool
    {
        return self::isZNum($v) && $v >= 0;
    }
    //正整数
    public static function isPNum($v): bool
    {
        return self::isZNum($v) && $v > 0;
    }
    //负整数
    public static function isNNum($v): bool
    {
        return self::isZNum($v) && $v < 0;
    }
    //奇数
    public static function isOdd($v): bool
    {
        return self::isZNum($v) && abs($v) % 2 == 1;
    }
    //偶数
    public static function isEven($v): bool
    {
        return self::isZNum($v) && abs($v) % 2 == 0;
    }
    //小数
    public static function isDecimal($v): bool
    {
        if (is_numeric($v) && !is_int($v)) {
            if (preg_match('/[eE]/', $v)) {
                $v = (float) $v; //科学计数型如1e-1
            }
            return preg_match('/^[-+]?[\d]+\.[\d]+$/', $v) > 0;
        }
        return false;
    }
    //正小数
    public static function isPDecimal($v): bool
    {
        return self::isDecimal($v) && $v > 0;
    }
    //负小数
    public static function isNDecimal($v): bool
    {
        return self::isDecimal($v) && $v < 0;
    }
    /********************/

    /****常见字符串类型****/

    //email
    public static function isEmail($v): bool
    {
        return filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
    }
    //ip地址
    public static function isIP($v): bool
    {
        return filter_var($v, FILTER_VALIDATE_IP) !== false;
    }
    //url地址
    public static function isURL($v): bool
    {
        return filter_var($v, FILTER_VALIDATE_URL) !== false;
    }
    //纯英文字符
    public static function isAlpha($v): bool
    {
        return preg_match('/^[a-zA-z]+$/', $v);
    }
    //纯中文字符
    public static function isZH($v): bool
    {
        return preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $v) > 0;
    }
    //手机
    public static function isMobile($v): bool
    {
        return preg_match('/^1[2-9][0-9]{9}$/', $v) > 0;
    }
    //邮编
    public static function isZIP($v): bool
    {
        return preg_match('/^[1-9][0-9]{5}$/', $v) > 0;
    }
    //座机
    public static function isTel($v): bool
    {
        if (preg_match('/^(0[\d^0]{2}[-\s]?[\d^0]{8})|(0[\d^0]{3}[-\s]?[\d^0]{7})$/', $v)) {
            return true;
        }
        if (preg_match('/^(\(0[\d^0]{2}\)\s?[\d^0]{8})|(\(0[\d^0]{3}\)\s?[\d^0]{7})$/', $v)) {
            return true;
        }
        if (preg_match('/^[\d^0]{7,8}$/', $v)) {
            return true;
        }
        return false;
    }
    //日期
    public static function isDate($v): bool
    {
        if (!is_string($v) && !is_numeric($v)) {
            return false;
        }

        return strtotime($v) !== false;
    }

    //常见勾选用字符
    public static function isActive($v): bool
    {
        if (is_string($v)) {
            $v = trim(strtolower($v));
            return $v === 'yes' || $v === 'on' || $v === 'accepted' || $v === 'agree' || $v === 'accept';
        }
        return $v == 1;
    }
    //18位身份证
    public static function isID($v): bool
    {
        $pattern = '/^[1-9]\d{5}(19|20)\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/';
        return preg_match($pattern, $v) > 0;
    }
    //16-19位银行卡号
    public static function isBC($v): bool
    {
        return preg_match('/^[1-9]{1}[0-9]{15,18}$/', $v) > 0;
    }
    //账号常见字符，中英文、数字、横线、下划线、点、@字符
    public static function isAccount($v): bool
    {
        return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-z0-9_\-\.@]+$/u', $v) > 0;
    }
    /********************/

    /***二值比较,支持多种数据类型***/
    public static function checkPwd($pwd, $hash): bool
    {
        return password_verify($pwd, $hash);
    }
    public static function gt($a, $b): bool
    {
        if (is_object($a) && is_numeric($b)) {
            return $a > (string) $b; //对象和数字不可直接比较
        }
        if (is_object($b) && is_numeric($a)) {
            return (string) $a > $b;
        }
        return $a > $b;
    }
    public static function lt($a, $b): bool
    {
        if (self::gt($a, $b)) {
            return false;
        }
        if (is_object($a) && is_numeric($b)) {
            return $a != (string) $b;
        }
        if (is_object($b) && is_numeric($a)) {
            return (string) $a != $b;
        }
        return $a != $b;
    }
    public static function ge($a, $b): bool
    {
        return !self::lt($a, $b);
    }
    public static function le($a, $b): bool
    {
        return !self::gt($a, $b);
    }
    public static function eq($a, $b): bool
    {
        return self::ge($a, $b) && self::le($a, $b);
    }
    public static function ne($a, $b): bool
    {
        return !self::eq($a, $b);
    }
    /********************/
}
