<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-31 17:27:15
 * @Modified time:      2018-08-29 16:58:54
 * @Depends on Linker:  Config ServerQueue
 * @Description:        测试使用队列记录日志
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\log\structure\QueueHandler;
use PHPUnit\Framework\TestCase;

class LogQueueHandlerTest extends TestCase
{
    private $data = [
        ['name0', 'type', '日志内容，长度任意', 1],
        ['name1', 'type', '日志内容，长度任意', 1],
        ['name0', 'type', '日志内容，长度任意', 1],
    ];
    private $config;

    protected function setUp()
    {
        $this->config = Linker::Config()::lin('log.server.queue');
    }

    public function testQueueHandler()
    {
        //替换成仿件
        $QueueName = Linker::ServerQueue();
        $Queue     = $this->getMockBuilder($QueueName)->setMethods([
            'setName', 'multiPush',
        ])->getMock();

        //预言
        $Queue->expects($this->exactly(2))->method('setName')->withConsecutive(
            [$this->config['prefix'] . 'name0'],
            [$this->config['prefix'] . 'name1']
        );

        $Queue->expects($this->exactly(2))->method('multiPush')->withConsecutive(
            [$this->callback(function ($value) {
                $data = $this->data[0];
                unset($data[0]);
                $data = call_user_func_array($this->config['format'], $data);
                return is_array($value) && count($value) === 2 && $value[0] === $data; //同日志名的两个记录
            })],
            [$this->callback(function ($value) {
                $data = $this->data[1];
                unset($data[0]);
                $data = call_user_func_array($this->config['format'], $data);
                return is_array($value) && count($value) === 1 && $value[0] === $data; //同日志名的一个记录
            })]
        );

        //替换
        Linker::register([
            'ServerQueue' => $Queue,
        ]);

        //执行
        $Handler = new QueueHandler;
        $Handler->write($this->data, $this->config);

        //恢复
        Linker::register([
            'ServerQueue' => $QueueName,
        ]);
    }
}
