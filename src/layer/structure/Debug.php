<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-10-31 23:15:42
 * @Modified time:      2018-11-05 16:36:09
 * @Depends on Linker:  Debug
 * @Description:
 */
namespace lin\layer\structure;

use Linker;

class Debug
{
    private static $Debug;
    private static $counts = ['t' => 0, 'c' => 0];

    public static function flow($className, $method, $data, $t)
    {
        if (!self::$Debug) {
            self::$Debug = Linker::Debug(true);
            self::$Debug->setName('LAYER');
        }
        self::$counts['t'] += $t;
        self::$counts['c']++;

        $total_time = round(self::$counts['t'] * 1000, 2);
        $statistics = self::$counts['c'] . "; <font style='color:rgb(100,100,100)'>(${total_time}ms)</font>";
        self::$Debug->set('流程数', $statistics);

        $t    = '<font style="color:rgb(100,100,100)">(' . round($t * 1000, 2) . 'ms)</font>';
        $data = self::formatValue($data);
        $data = "<font style='color:rgb(150,150,150)'> --- [data: $data]</font>";
        self::$Debug->append('流程明细', "$className::$method(); $data $t");
    }
    private static function formatValue($value)
    {
        if (is_object($value)) {
            $value = 'object ( ' . get_class($value) . ' )';
        } else if (is_array($value)) {
            $value = ltrim(var_export($value, true), 'array (');
            $value = rtrim($value, ')');
            if (strlen($value) > 100) {
                $value = substr($value, 0, 100) . ' ... ';
            }
            $value = "array ($value)";
        } else if (is_string($value)) {
            if (strlen($value) > 100) {
                $value = substr($value, 0, 100) . '...';
            }
            $value = "'" . $value . "'";
        } else {
            $value = var_export($value, true);
        }

        return $value;
    }
}
