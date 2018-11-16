<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-07-20 19:33:57
 * @Modified time:      2018-11-08 10:36:28
 * @Depends on Linker:  Debug
 * @Description:        输出日志的记录信息
 */

namespace lin\basement\log\structure;

use Linker;

class Debug
{
    private static $counts = ['l' => [], 'r' => 0];
    public static function handle($fileName, $type)
    {
        self::$counts['r']++;
        self::$counts['l'][$fileName] = 1;

        $backtrace  = debug_backtrace();
        $whoTrigger = $backtrace[3]; //再上上一层即为触发事件的地方
        $info       = $whoTrigger['file'] . '; line ' . $whoTrigger['line'];

        $Debug = Linker::Debug(true);
        $Debug->setName('LOG');
        $info = "[$type] $fileName; <font style='color:rgb(150,150,150)''> --- [$info]</font>";

        $statis = [
            '日志数' => count(self::$counts['l']),
            '记录数' => self::$counts['r'],
        ];

        $Debug->set('统计', $statis);
        $logs = $Debug->get('日志明细') ?: [];
        $logs = array_merge($logs, [$fileName]);
        $Debug->set('日志明细', $logs);
        $Debug->append('记录明细', $info);
    }
}
