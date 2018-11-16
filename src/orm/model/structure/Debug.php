<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-11-01 11:28:10
 * @Modified time:      2018-11-05 16:49:24
 * @Depends on Linker:  Debug
 * @Description:
 */
namespace lin\orm\model\structure;

use Linker;

class Debug
{
    private $Debug;
    private static $model   = [];
    private static $operate = [];
    private static $color   = ['read' => 'green', 'write' => 'orange'];
    public function __construct()
    {
        $this->Debug = Linker::Debug(true);
        $this->Debug->setName('ORM');
    }
    public function read($class, $rows, $t)
    {
        $this->handle($class, $rows, $t, 'select');
    }
    public function write($class, $rows, $t, $method)
    {
        $this->handle($class, $rows, $t, $method);
    }

    private function handle($class, $rows, $t, $operate)
    {
        if ($operate == 'select') {
            $type = 'read';
        } else {
            $type = 'write';
        }
        $this->statistics($t, $type);
        $info = "$class::$operate()<font style='color:rgb(150,150,150)'> --- [$rows rows]</font>" . $this->getTime($t);
        $this->Debug->append('执行明细', $info);
    }

    private function statistics($t, $operate)
    {
        if (!isset(self::$operate[$operate])) {
            self::$operate[$operate] = ['c' => 0, 't' => 0];
        }

        self::$operate[$operate]['c']++;
        self::$operate[$operate]['t'] += $t;

        $info       = ['执行数' => ''];
        $total_time = 0;
        $counts     = 0;
        foreach (self::$operate as $operate => $v) {
            $color          = self::$color[$operate];
            $operate        = "operator <font style='color:$color'>$operate</font>";
            $info[$operate] = $v['c'] . ';' . $this->getTime($v['t']);
            $total_time += $v['t'];
            $counts += $v['c'];
        }
        $info['执行数'] = $counts . ';' . $this->getTime($total_time);
        $this->Debug->set('统计', $info);
    }
    private function getTime($t)
    {
        $t = round($t * 1000, 2);
        return " <font style='color:#646464'>(${t}ms)</font>";
    }
}
