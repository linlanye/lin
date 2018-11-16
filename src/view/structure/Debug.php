<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-06 11:35:36
 * @Modified time:      2018-11-13 09:49:32
 * @Depends on Linker:  Debug
 * @Description:        视图信息记录
 */
namespace lin\view\structure;

use Linker;

class Debug
{
    private $Debug;
    private static $counts = 0; //记录加载总时间
    private static $time   = [];
    public function __construct()
    {
        $this->Debug = Linker::Debug(true);
        $this->Debug->setName('VIEW');
        $this->Debug->set('缓存文件', '');
    }

    public function show($cache_file)
    {
        $this->Debug->set('缓存文件', $cache_file);
    }
    public function parse($view_file, $t)
    {
        $prev = end(self::$time) ?: 0;
        reset(self::$time);
        self::$time[] = $t;
        ++self::$counts;
        $this->Debug->set('文件数', self::$counts . $this->getSubInfo($t));
        $info = $view_file . $this->getSubInfo($t - $prev); //parse为递归，实际时间需减去上一次
        $this->Debug->append('文件明细', $info);
    }

    private function getSubInfo($t, $info = '')
    {
        $t = round($t * 1000, 2);
        return "; <font style='color:#646464'>(${t}ms$info)";
    }
}
