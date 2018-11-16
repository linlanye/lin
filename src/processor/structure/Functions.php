<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-01-17 08:59:22
 * @Modified time:      2018-09-10 16:35:15
 * @Depends on Linker:  None
 * @Description:        格式化数据，对传入数据在某个函数中不符合该函数的入参或处理失败，
 *                      则该函数会返回指定默认值。
 */
namespace lin\processor\structure;

class Functions
{
    /****通用类型转基础类型****/
    public static function toInt($v): int
    {
        if (is_object($v)) {
            $v = get_object_vars($v);
        }
        return (int) $v;
    }
    public static function toFloat($v): float
    {
        if (is_object($v)) {
            $v = get_object_vars($v);
        }
        return (float) $v;
    }
    public static function toString($v): string
    {
        if (is_array($v) || is_object($v)) {
            return serialize($v); //序列化为字符串
        }
        if (is_resource($v)) {
            $v = (int) $v; //资源转为其资源id字符
        }
        return (string) $v;
    }
    public static function toBool($v): bool
    {
        return (bool) $v;
    }
    public static function toArray($v): array
    {
        if (is_object($v)) {
            $v = get_object_vars($v); //避免非公属性泄漏
        }
        return (array) $v;
    }
    public static function toObject($v): object
    {
        return (object) $v;
    }
    /**************************/

    /****通用类型转指定类型****/
    //转为标准日期
    public static function toDate($v, $default = null):  ? string
    {
        if (is_string($v)) {
            $v = strtotime($v); //存在可转为日期的字符
            if ($v === false) {
                return $default;
            }
        }
        if (!is_numeric($v) || $v < -PHP_INT_MAX || $v > PHP_INT_MAX) {
            return $default;
        }
        return date('Y-m-d H:i:s', $v);
    }
    //转为1或默认值
    public static function toActive($v, $default = 0) :  ? int
    {
        if (is_numeric($v)) {
            return round($v) > 0 ? 1 : $default;
        }
        if (is_string($v)) {
            $v = strtolower($v);
            if ($v == 'yes' || $v == 'on' || $v == 'accepted' || $v == 'agree' || $v === 'accept') {
                return 1;
            }
        }
        return $default;
    }
    //转为正整数
    public static function toPNum($v, $default = null) :  ? int
    {
        if (!is_numeric($v)) {
            return $default;
        }
        $v = (int) round($v);
        return $v > 0 ? $v : 1;
    }
    //转为自然数
    public static function toNatNum($v, $default = null) :  ? int
    {
        if (!is_numeric($v)) {
            return $default;
        }
        $v = (int) round($v);
        return $v >= 0 ? $v : 0;
    }
    //转为负整数
    public static function toNNum($v, $default = null) :  ? int
    {
        if (!is_numeric($v)) {
            return $default;
        }
        $v = (int) round($v);
        return $v < 0 ? $v : -1;
    }
    //转为两位小数字符串
    public static function toPrice($v, $default = null) :  ? string
    {
        if (!is_numeric($v)) {
            return $default;
        }
        return sprintf('%.2f', round($v, 2));
    }
    //转换为千分位分割的数字
    public static function toTString($v, $default = null) :  ? string
    {
        if (!is_numeric($v)) {
            return $default;
        }
        return number_format($v);
    }
    //转为过去时间
    public static function toPast($t, $default = null) :  ? string
    {
        if (!is_numeric($t)) {
            return $default;
        }
        $v = time() - round($t);
        if ($v < 0) {
            return self::toFuture($t);
        }
        if ($v == 0) {
            return '现在';
        }
        if ($v < 60) {
            return $v . '秒前';
        }
        if ($v >= 60 && $v < 3600) {
            return round(($v / 60)) . '分钟前';
        }
        if ($v >= 3600 && $v < 3600 * 24) {
            return round(($v / 3600)) . '小时前';
        }
        if ($v >= 3600 * 24 && $v < 3600 * 24 * 30) {
            return round(($v / 3600 / 24)) . '天前';
        }
        if ($v >= 3600 * 24 * 30 && $v < 3600 * 24 * 30 * 12) {
            return round(($v / 3600 / 24 / 30)) . '个月前';
        }
        return round(($v / 3600 / 24 / 30 / 12)) . '年前';
    }
    //时间戳转为未来时间
    public static function toFuture($t, $default = null) :  ? string
    {
        if (!is_numeric($t)) {
            return $default;
        }
        $v = round($t) - time();
        if ($v < 0) {
            return self::toPast($t);
        }
        if ($v == 0) {
            return '现在';
        }
        if ($v < 60) {
            return $v . '秒后';
        }
        if ($v >= 60 && $v < 3600) {
            return ($v / 60) . '分钟后';
        }
        if ($v >= 3600 && $v < 3600 * 24) {
            return ($v / 3600) . '小时后';
        }
        if ($v >= 3600 * 24 && $v < 3600 * 24 * 30) {
            return ($v / 3600 / 24) . '天后';
        }
        if ($v >= 3600 * 24 * 30 && $v < 3600 * 24 * 30 * 12) {
            return ($v / 3600 / 24 / 30) . '个月后';
        }
        return ($v / 3600 / 24 / 30 / 12) . '年后';
    }
    //转为倒计时
    public static function toCountdown($v, $default = null) :  ? string
    {
        if (!is_numeric($v)) {
            return $default;
        }
        $v = round($v) - time();
        if ($v <= 0) {
            return '0秒';
        }

        $day  = floor($v / (24 * 3600));
        $hour = floor($v / 3600) % 24;
        $min  = floor($v / 60) % 60;
        $sec  = $v % 60;
        if ($day > 0) {
            return "${day}天${hour}时${min}分${sec}秒";
        }
        if ($hour > 0) {
            return "${hour}时${min}分${sec}秒";
        }
        if ($min > 0) {
            return "${min}分${sec}秒";
        }
        return "${sec}秒";
    }

    /**************************/

    /***指定类型转另一类指定类型***/
    //整数转IP
    public static function num2IP($v, $default = null) :  ? string
    {
        if (!is_numeric($v) || $v < -0x7fffffff || $v > 0x7fffffff) {
            return $default;
        }
        return long2ip($v);
    }
    //IP转整数,默认值为32位PHP_INT_MAX+1
    public static function ip2Num(string $v, $default = null) :  ? int
    {
        if (filter_var($v, FILTER_VALIDATE_IP) === false) {
            return $default;
        }
        $r = ip2long($v);
        if ($r === false) {
            return $default;
        }
        return $r;
    }
    //日期转时间戳
    public static function date2time($v, $default = null) :  ? int
    {
        if (!is_string($v) && !is_numeric($v)) {
            return $default;
        }
        $v = strtotime($v);
        if ($v === false) {
            return $default;
        }
        return $v;
    }
    /******************************/

    /*********字符专用转换*********/

    //明文字符串转安全密码
    public static function forPwd(string $v, $cost = 10) : string
    {
        return password_hash($v, PASSWORD_DEFAULT, ['cost' => $cost]); //校验时请使用password_verify($pwd,$hash)函数
    }

    //对html字符进行转义
    public static function forHTML(string $v): string
    {
        return htmlspecialchars($v);
    }

    //剔除字符串中的指定符号
    public static function stripSymbol(string $v, string $symbol): string
    {
        if ($symbol === '/') {
            $symbol = preg_quote($symbol, '/');
        }
        return preg_replace('/' . $symbol . '/', '', $v);
    }
    //剔除字符串中的所有逗号
    public static function stripComma(string $v): string
    {
        return self::stripSymbol($v, ',');
    }
    //剔除字符串中的所有空格
    public static function stripSpace(string $v): string
    {
        return self::stripSymbol($v, '\s');
    }
}
