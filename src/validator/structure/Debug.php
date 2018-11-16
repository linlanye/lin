<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-14 09:55:01
 * @Modified time:      2018-11-05 16:27:30
 * @Depends on Linker:  Debug
 * @Description:
 */
namespace lin\validator\structure;

use Linker;

class Debug
{
    private $Debug;
    private static $time   = 0;
    private static $counts = 0;

    public function __construct($class)
    {
        $this->Debug = Linker::Debug(true);
        $this->Debug->setName('VALIDATOR');
        $this->class = $class;
    }

    public function validate($field, $rule, $r, $t)
    {
        self::$counts += 1; //累计字段验证数
        self::$time += $t; //累计时间
        $t = round($t * 1000, 2);

        $this->Debug->set('统计', self::$counts . '; ' . $this->getSubInfo(self::$time));

        $status = $r ? '; success' : '; <font style="color:red">failed</font>';
        $info   = "fields: $field; <font style='color:rgb(150,150,150)'> --- [by $this->class, rule: $rule] </font>" . $this->getSubInfo($t, $status);
        $this->Debug->append('执行明细', $info);
    }
    private function getSubInfo($t, $info = '')
    {
        $t = round($t * 1000, 2);
        return "<font style='color:#646464'>(${t}ms$info)";
    }
}
