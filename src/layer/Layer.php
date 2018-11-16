<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-10-19 10:28:19
 * @Modified time:      2018-11-02 14:17:05
 * @Depends on Linker:  Config Log Request ServerLocal ServerKV ServerQueue ServerSQL
 * @Description:        对lin组件的一些封装和提供分层访问和流式访问的功能
 */
namespace lin\layer;

use Linker;
use lin\layer\structure\Debug;
use lin\layer\structure\Flow;
use lin\orm\query\Query;
use lin\response\Response;
use ReflectionClass;

class Layer
{
    //不允许复写构造函数
    final public function __construct()
    {
        $this->setting();
    }
    final protected function use(string $utils)
    {
        if ($utils == '*') {
            $utils = ['http', 'log', 'local', 'queue', 'kv', 'sql'];
        } else {
            $utils = array_unique(array_map('trim', explode(',', $utils)));
        }

        foreach ($utils as $util) {
            $util = strtolower($util);
            switch ($util) {
                case 'http':
                    $this->Request  = Linker::Request(true);
                    $this->Response = new Response;
                    break;
                case 'log':
                    $this->Log = Linker::Log(true);
                    break;
                case 'local':
                    $this->Local = Linker::ServerLocal(true);
                    break;
                case 'queue':
                    $this->Queue = Linker::ServerQueue(true);
                    break;
                case 'kv':
                    $this->KV = Linker::ServerKV(true);
                    break;
                case 'sql':
                    $this->SQL   = Linker::ServerSQL(true);
                    $this->Query = new Query;
                    break;
            }
        }
    }
    protected function setting()
    {}

    //快速访问不同层
    final protected static function layer(string $layer): object
    {
        $namespace = Linker::Config()::get('lin')['layer']['namespace']['layer'];
        $Class     = self::_getClassName_52570($layer, $namespace);
        return new $Class;
    }
    //快速访问不同块
    final protected static function block(string $block, ...$args): object
    {
        $namespace = Linker::Config()::get('lin')['layer']['namespace']['block'];
        $Class     = self::_getClassName_52570($block, $namespace);
        if ($args) {
            return (new ReflectionClass($Class))->newInstanceArgs($args);
        }
        return new $Class;
    }
    //获得完整的块类名
    final protected static function blockName(string $block): string
    {
        $namespace = Linker::Config()::get('lin')['layer']['namespace']['block'];
        $Class     = self::_getClassName_52570($block, $namespace);
        return $Class;
    }
    final protected static function flow(array $layers, $data = null)
    {
        $config    = Linker::Config()::get('lin')['layer'];
        $namespace = $config['namespace']['layer'];
        $debug     = $config['debug'];
        if ($data instanceof Flow) {
            $Flow = $data; //传入直接是流对象
            if ($Flow->isTerminal()) {
                return $Flow;
            }
        } else {
            $Flow       = new Flow;
            $Flow->data = $data;
        }
        $t = microtime(true);
        foreach ($layers as $layer) {
            $layer     = explode('.', $layer);
            $className = self::_getClassName_52570($layer[0], $namespace);
            $method    = $layer[1];

            if ($debug) {
                $flow_data = $Flow->data;
            }

            call_user_func([new $className, $method], $Flow); //同一个流对象
            $Flow->_setStep_13791($className, $method);

            if ($debug) {
                $t2 = microtime(true);
                Debug::flow($className, $method, $flow_data, $t2 - $t);
                $t = $t2;
            }

            if ($Flow->isTerminal()) {
                break; //当前流程中断
            }
        }
        $Flow->terminal(); //执行完后终止标记
        return $Flow;
    }

    final protected static function _getClassName_52570($name, $namespace)
    {

        $namespace = trim($namespace, '\\');
        $name      = str_replace('/', '\\', $name);
        return $namespace . '\\' . ltrim($name, '\\');
    }

}
