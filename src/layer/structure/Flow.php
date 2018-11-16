<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-02-02 13:21:08
 * @Modified time:      2018-11-01 11:10:37
 * @Depends on Linker:  None
 * @Description:        提供流程自动化访问各层，并提供一定的中断机制和数据传递机制
 */
namespace lin\layer\structure;

use Linker;

class Flow
{
    public $data; //流携带的数据
    private $terminal; //是否终止标记
    private $step = []; //执行的步骤

    //设置当前执行为终点标记
    public function terminal(): void
    {
        $this->terminal = true;
    }
    //获得执行明细
    public function getDetails(): array
    {
        return $this->step;
    }
    //当前流程是否已为终点
    public function isTerminal(): bool
    {
        return (bool) $this->terminal;
    }
    //重置流，使其可以继续使用
    public function reset(): bool
    {
        $this->terminal = false;
        $this->step     = [];
        return true;
    }

    /****内部友源方法*****/
    public function _setStep_13791($className, $method)
    {
        $this->step[] = [$className, $method];
    }
}
