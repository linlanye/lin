<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-07-10 20:46:54
 * @Modified time:      2018-11-08 10:22:56
 * @Depends on Linker:  Debug
 * @Description:        输出事件的执行信息
 */
namespace lin\basement\event\structure;

use Linker;

class Debug
{
    private static $Debug;
    private static $counts = 0; //执行次数
    private static $time   = 0; //执行时间

    public static function run($files, $totalRules, $time)
    {
        self::init();
        $data = [
            '文件数' => count($files) . ';' . self::getSubInfo(array_sum($time)),
            '执行数' => 0,
            '事件数' => $totalRules,
        ];

        self::$Debug->set('统计', $data);
        foreach ($files as $key => &$value) {
            $value .= ';' . self::getSubInfo($time[$key]);
        }

        self::$Debug->set('文件明细', $files);
    }

    public static function trigger($event, $params, $time)
    {
        self::init();
        self::$counts++;
        self::$time += $time;

        $time = round($time * 1000, 2);
        if (!empty($params)) {
            $p = ' with parameters: (';
            foreach ($params as $param) {
                $p .= var_export($param, true) . ', ';
            }
            $p = rtrim($p, ', ') . ')';
        } else {
            $p = null;
        }
        $whoTrigger = debug_backtrace()[1]; //再上上一层即为触发事件的地方

        $info = $event . $p . '; <font style="color:rgb(150,150,150)"> --- [' . $whoTrigger['file'] . '; line ' . $whoTrigger['line'] . ']' . self::getSubInfo($time) . '</font>';
        self::$Debug->append('事件明细', $info);

        $statistics              = self::$Debug->get('统计');
        $statistics['执行数'] = self::$counts . ';' . self::getSubInfo(self::$time); //累计执行时间
        self::$Debug->set('统计', $statistics);
    }

    private static function init()
    {
        if (!self::$Debug) {
            self::$Debug = Linker::Debug(true);
            self::$Debug->setName('EVENT');
        }
    }

    private static function getSubInfo($t, $info = '')
    {
        $t = round($t * 1000, 2);
        return " <font style='color:#646464'>(${t}ms$info)</font>";
    }
}
