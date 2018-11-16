<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-12-25 20:53:28
 * @Modified time:      2018-11-06 22:45:24
 * @Depends on Linker:  Config Log Lang
 * @Description:        异常和错误处理
 */
namespace lin\exception\structure;

use Linker;

class Parser
{
    private static $status = 0;
    //自定义异常处理
    public static function setException($Exception)
    {
        $config = Linker::Config()::get('lin')['exception']['exception'];

        //获取参数
        $log_msg = $msg = $Exception->getMessage();
        $sub_msg = method_exists($Exception, 'getSecondMessage') ? $Exception->getSecondMessage() : '';
        $file    = $Exception->getFile() . ', line ' . $Exception->getLine();
        $code    = $Exception->getCode();
        $class   = get_class($Exception);
        if (method_exists($Exception, 'getType')) {
            $type = $Exception->getType();
        } else {
            $type = $config['default']['type'];
        }

        //是否多语言
        if ($config['lang'] && $config['lang'] !== 'none') {
            $Lang = Linker::Lang(true);
            $Lang->setLabel('lin');
            $msg = $Lang->map($msg); //获得翻译后的消息，只翻译主消息
        }
        if ($sub_msg) {
            $msg     = "$msg ($sub_msg)";
            $log_msg = "$log_msg ($sub_msg)";
        }

        //是否回调
        if (is_callable($config['callback'])) {
            call_user_func($config['callback'], $Exception);
        } else if ($config['show']) {
            self::init(); //显示消息
            //生成trace样式
            foreach ($Exception->getTrace() as $k => $v) {
                if (isset($v['file']) && isset($v['line'])) {
                    $trace[$k] = ['file' => $v['file'] . '  --- line ' . $v['line']];
                } else {
                    $trace[$k] = ['file' => ''];
                }
                $args = '';
                if (empty($v['args'])) {
                    $args = '';
                } else {
                    foreach ($v['args'] as $v2) {
                        if (is_object($v2)) {
                            $args .= '(Object) ' . get_class($v2) . ', ';
                        } elseif (is_array($v2)) {
                            $args .= 'array(...), ';
                        } else {
                            $args .= "'" . $v2 . "', ";
                        }
                    }
                    $args = rtrim($args, ', ');
                }
                if (!empty($args)) {
                    $args = '<br>' . $args . '<br>';
                }
                $trace[$k]['args'] = $args;
                if (isset($v['class'])) {
                    $trace[$k]['function'] = $v['class'] . $v['type'] . $v['function'];
                } else {
                    $trace[$k]['function'] = $v['function'];
                }
            }
            //输送信息
            $msg    = preg_replace('/[\n\r]/', '', $msg); //去掉可能换行符
            $_msg   = addslashes($msg);
            $_class = addslashes($class) . ' code: ' . $code;
            $_file  = addslashes($file);
            $_type  = addslashes($type);
            echo "<script>appendException('$_type','$_msg','$_class','$_file');</script>";

            foreach ($trace as $_no => $v) {
                $_func = addslashes($v['function']);
                $_file = addslashes($v['file']);
                $_args = addslashes($v['args']);
                echo "<script>appendExceptionTrace('$_no','$_func','$_file','$_args');</script>";
            }
        }

        //是否记录日志
        if ($config['log']['on']) {
            if ($config['lang'] === 'both') {
                $log_msg = $msg; //使用翻译后的消息记录日志
            }
            $log_msg = "class: $class; code: $code; type: $type; msg: $log_msg; file: $file";
            self::setLog($log_msg, $config['log']['name']);
        }

    }
    //设置错误并输出
    public static function setError($level, $msg, $file, $line)
    {
        $config = Linker::Config()::get('lin')['exception']['error'];
        switch ($level) {
            //提醒级别
            case E_NOTICE:
            case E_USER_NOTICE:
                $type = 'Notice';
                break;
            //警告级别
            case E_WARNING:
            case E_USER_WARNING:
                $type = 'Warning';
                break;
            //错误级别
            case E_ERROR:
            case E_USER_ERROR:
                $type = 'Fatal Error';
                $EXIT = true;
                break;
            case E_PARSE:
                $type = 'Parse Error';
                $break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $type = 'Deprecated';
            //其他未知错误
            default:
                $type = 'Unknown';
                $EXIT = true;
                break;
        }

        $msg     = preg_replace('/[\n\r]/', '', $msg); //去掉可能换行符
        $log_msg = $msg;

        //是否多语言
        if ($config['lang'] && $config['lang'] !== 'none') {
            $Lang = Linker::Lang(true);
            $Lang->setLabel('lin');
            $msg = $Lang->map($msg); //获得翻译后的消息，只翻译主消息
        }

        //是否回调
        if (is_callable($config['callback'])) {
            call_user_func_array($config['callback'], [$level, $msg, $file, $line]);
        } else if ((error_reporting() & $level) && $config['show']) {
            self::init();
            $_msg  = addslashes($msg);
            $_file = addslashes($file);
            $_line = 'line ' . $line;
            $_type = '[' . $type . ']';
            echo "<script>appendError('$_type','$_msg','$_file','$_line');</script>";
        }

        //记录日记
        if ($config['log']['on']) {
            $type = strtolower($type);
            if ($config['lang'] == 'both') {
                $log_msg = $Lang->map($msg); //获得翻译后的消息，用于记录
            }
            $log_msg = "code: $level; msg: " . $log_msg . "; $file, $line";
            self::setLog($log_msg, $config['log']['name'], $type);
        }

    }
    private static function init()
    {
        if (!self::$status) {
            self::$status = 1;
            include 'view.html';
        }
    }
    private static function setLog($msg, $filename, $type = 'exception')
    {
        $Log = Linker::Log(true);
        $Log->setName($filename);
        $Log->record($msg, $type);
    }
}
