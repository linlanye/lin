<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-23 16:56:51
 * @Modified time:      2018-11-13 20:28:41
 * @Depends on Linker:  Debug
 * @Description:        路由信息记录
 */
namespace lin\route\structure;

use Closure;
use Linker;

class Debug
{
    private $Debug;
    private static $counts     = ['p' => 0, 'f' => 0]; //记录执行和文件读取次数
    private static $time       = ['p' => 0, 'f' => 0]; //记录执行和文件读取时间
    private static $totalRules = 0; //规则总数
    public function __construct()
    {
        $this->Debug = Linker::Debug(true);
        $this->Debug->setName('ROUTE');
        $this->statistics();
    }

    //记录匹配路由类型
    public function type($type)
    {
        $this->Debug->set('路由类型', $type);
    }

    /**
     * 记录规则加载信息
     * @param array $files 加载的所有路由文件
     * @param array $rules 解析出来的规则数
     * @param float $t     加载使用的时间
     */
    public function parse($files, $rules, $t)
    {
        self::$counts['f'] += count($files);
        self::$time['f'] += $t;
        $this->statistics();

        //文件
        $file_details = [];
        foreach ($files as $file) {
            $file_details[] = $file;
        }

        $this->Debug->append('文件明细', $file_details);

        //规则
        $rule_details = [];
        foreach ($rules as $method => $v) {
            $totalS = count($v['S']);
            $totalD = count($v['D']);
            self::$totalRules += $totalS + $totalD;
            $rule_details[] = "$method; <font style='color:#646464'>(static: $totalS, dynamic: $totalD)</font>";
        }
        $this->Debug->append('规则明细', $rule_details);

    }

    /**
     * 记录执行流程信息
     * @param array $process 当前需执行的流程
     * @param array $params  动态路由匹配到的参数，可空
     * @param int   $index   最后一个执行成功规则的索引
     * @param array $times   每一个执行规则的时间花费
     */
    public function process($process, $params, $index, $times)
    {
        self::$counts['p'] += count($process);
        self::$time['p'] += array_sum($times);
        $this->statistics();

        $hasParams = !empty($params);
        $params    = var_export($params, true);

        $details = [];
        foreach ($process as $_index => $rule) {
            if ($rule[0] instanceof Closure) {
                if ($hasParams) {
                    $detail = "Closure with parameters: $params; ";
                } else {
                    $detail = "Closure; ";
                }

            } else {
                $detail = $rule[0] . '::' . $rule[1] . '()';
            }

            if ($_index <= $index) {
                $detail .= $this->getSubInfo($times[$_index], '; success');
            } else {
                $detail .= $this->getSubInfo(0, '; <font style="color:red">failure</font>');
            }
            $details[$_index] = $detail;
        }
        $this->Debug->set('执行明细', $details);

    }
    private function statistics()
    {
        $this->Debug->set('统计', [
            '文件数' => self::$counts['f'] . $this->getSubInfo(self::$time['f']),
            '规则数' => self::$totalRules,
            '执行数' => self::$counts['p'] . $this->getSubInfo(self::$time['p']),
        ]);
    }

    private function getSubInfo($t, $info = '')
    {
        $t = round($t * 1000, 2);
        return "; <font style='color:#646464'>(${t}ms$info)";
    }
}
