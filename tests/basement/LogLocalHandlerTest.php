<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-31 17:25:42
 * @Modified time:      2018-08-29 16:58:54
 * @Depends on Linker:  Config ServerLocal
 * @Description:
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\log\Log;
use lin\basement\log\structure\LocalHandler;
use PHPUnit\Framework\TestCase;

class LogLocalHandlerTest extends TestCase
{
    private $data = [
        ['name0', 'type', '假设文件不存在，长度任意', 1], //日志名，日志类型，内容，记录时间
        ['name1', 'type', '假设文件存在但不必新写文件，长度未超过10', 1],
        ['name2', 'type', '假设文件存在但需新写文件，长度超过10', 1],
        ['name1', 'type', '假设文件存在但不必新写文件，用于追加末尾，长度未超过10', 1],
    ];
    private $config;

    public static function tearDownAfterClass()
    {
        $log_dir = Linker::Config()::lin('log.server.local.path');
        if (is_dir($log_dir)) {
            foreach (scandir($log_dir) as $file) {
                if ($file != '.' && $file != '..') {
                    unlink($log_dir . $file);
                }
            }
            rmdir($log_dir);
        }
    }

    protected function setUp()
    {
        $this->config = Linker::Config()::lin('log.server.local');
    }

    public function testName()
    {
        //实例化时候设置日志名
        $name = md5(mt_rand());
        $Log  = new Log($name);
        $this->assertSame($Log->getName(), $name);

        //设置日志名
        $name = md5(mt_rand());
        $Log->setName($name);
        $this->assertSame($Log->getName(), $name);

        //默认日志名
        $Log = new Log;
        $this->assertSame($Log->getName(), Linker::Config()::lin('log.default.name'));
    }

    //测试文件大小超出限制时是否另起新文件，内容是否按回调方式进行格式化
    public function testFileAndContent()
    {
        //生成数据
        $data = [];
        foreach ($this->data as $key => $value) {
            unset($value[0]);
            $data[] = call_user_func_array($this->config['format'], $value);
        }

        //模拟
        $LocalName = Linker::ServerLocal();
        $Local     = $this->getMockBuilder($LocalName)->setMethods([
            'exists', 'write', 'getSize', 'setPath',
        ])->getMock();
        $Local->method('exists')->will($this->returnCallback(function ($name) {
            if (preg_match("/name1_0/", $name) || preg_match("/name2_0/", $name)) {
                return true; //name1和name2的0标号文件存在，其他都不存在
            };
            return false;
        }));
        $Local->method('getSize')->will($this->returnCallback(function ($name) {
            if (preg_match('/name1_0/', $name)) {
                return $this->config['size'] - 1; //长度未超限制,配置文件
            }
            if (preg_match('/name2_0/', $name)) {
                return $this->config['size'] + 1; //长度超限
            }
            return 0.0;
        }));

        //预言写入参数的文件名和内容是否为预期
        $Local->expects($this->exactly(3))->method('write')->withConsecutive(
            [$this->stringContains('name0_0'), $this->callback(function ($content) use ($data) {
                return $content === $data[0] . PHP_EOL;
            })],
            [$this->stringContains('name1_0'), $this->callback(function ($content) use ($data) {
                return $content === $data[1] . PHP_EOL . $data[3] . PHP_EOL;
            })],
            [$this->stringContains('name2_1'), $this->callback(function ($content) use ($data) {
                return $content === $data[2] . PHP_EOL;
            })]
        );

        //替换
        Linker::register([
            'ServerLocal' => $Local,
        ]);

        //执行
        $Handler = new LocalHandler;
        $Handler->write($this->data, $this->config);

        //复原
        Linker::register([
            'ServerLocal' => $LocalName,
        ]);

    }

    //测试归档路径是否正确
    public function testPath()
    {
        $this->handleTestPath($this->config['path']);

        $this->config['frequency'] = 13;
        $this->handleTestPath($this->config['path'] . '/' . date('YmdH'));

        $this->config['frequency'] = 25;
        $this->handleTestPath($this->config['path'] . '/' . date('Ymd'));

        $this->config['frequency'] = 721;
        $this->handleTestPath($this->config['path'] . '/' . date('Ym'));

    }

    //对归档文件路径进行测试
    private function handleTestPath($expectedPath)
    {
        $LocalName = Linker::ServerLocal();
        $Local     = $this->getMockBuilder($LocalName)->setMethods([
            'exists', 'write', 'getSize', 'setPath',
        ])->getMock();

        //预言
        $Local->expects($this->once())->method('setPath')->with($expectedPath);

        //替换
        Linker::register([
            'ServerLocal' => $Local,
        ]);

        //执行
        $Handler = new LocalHandler;
        $Handler->write($this->data, $this->config);

        //复原
        Linker::register([
            'ServerLocal' => $LocalName,
        ]);
    }

}
