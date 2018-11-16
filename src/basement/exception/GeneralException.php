<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-21 15:53:59
 * @Modified time:      2018-08-29 16:58:21
 * @Depends on Linker:  None
 * @Description:        强化后的异常类
 */
namespace lin\basement\exception;

use Exception as BaseException;

class GeneralException extends BaseException
{
    /*****basement*****/
    use \basement\Exception;
}
