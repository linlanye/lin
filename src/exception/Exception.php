<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-12-12 23:04:21
 * @Modified time:      2018-08-29 16:58:33
 * @Depends on Linker:  none
 * @Description:        运行自定义错误异常处理
 */
namespace lin\exception;

use Linker;
use lin\exception\structure\Parser;

class Exception
{
    private static $status = 0;

    public static function run(): void
    {
        if (self::$status) {
            return;
        }
        set_exception_handler('\lin\exception\handleException');
        set_error_handler('\lin\exception\handleError');
        self::$status = 1; //已初始化标记
    }
    public static function reset(): bool
    {
        self::$status = 0;
        return true;
    }

}

//自定义异常错误处理
function handleException($ex)
{
    Parser::setException($ex);
}
function handleError($errNo, $errStr, $errFile, $errLine)
{
    Parser::setError($errNo, $errStr, $errFile, $errLine);
}
