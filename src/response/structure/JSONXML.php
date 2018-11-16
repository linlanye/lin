<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-22 11:14:56
 * @Modified time:      2018-08-22 11:19:21
 * @Depends on Linker:  None
 * @Description:        用于json和xml的专用模板输出，防止用户访问内部变量
 */
namespace lin\response\structure;

class JSONXML
{
    public static function load($data, $_template_73120)
    {
        return include $_template_73120;
    }

}
