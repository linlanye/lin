<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-18 16:24:42
 * @Modified time:      2018-11-05 16:30:15
 * @Depends on Linker:  Debug
 * @Description:
 */
namespace lin\processor\structure;

use Linker;

class Debug
{
    private $class;
    private $Debug;
    private static $time  = ['m' => 0, 'f' => 0];
    private static $count = ['m' => 0, 'f' => 0];
    public function __construct($class)
    {
        $this->class = $class;
        $this->Debug = Linker::Debug(true);
        $this->Debug->setName('PROCESSOR');
    }

    public function handle($rule, $fields, $t, $type)
    {
        if (!$fields) {
            return; //未执行有效操作跳过
        }

        self::$time[$type] += $t;
        self::$count[$type] += count($fields);
        $this->statistics();
        $t    = round($t * 1000, 2);
        $info = 'fields: ' . implode(', ', $fields) . "; <font style='color:rgb(150,150,150)'> --- [by $this->class, rule: $rule]</font>" . $this->getSubInfo($t);
        if ($type == 'f') {
            $this->Debug->append('格式化明细', $info);
        } else {
            $this->Debug->append('映射明细', $info);
        }
    }

    private function statistics()
    {
        $data = [
            '格式化数' => self::$count['f'] . ';' . $this->getSubInfo(self::$time['f']),
            '映射数'  => self::$count['m'] . ';' . $this->getSubInfo(self::$time['m']),
        ];
        $this->Debug->set('统计', $data);
    }
    private function getSubInfo($t, $info = '')
    {
        $t = round($t * 1000, 2);
        return " <font style='color:#646464'>(${t}ms$info)";
    }

}
