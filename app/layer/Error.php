<?php
/**
 * 路由错误响应
 */
namespace app\layer;

use lin\response\Response;

class Error
{
    public function status404()
    {
        $Response = new Response;
        $Response->withStatus(404)->error('不可访问的地址', 0);
    }
    public function status301()
    {
        $Response = new Response;
        $Response->withStatus(301)->redirect('/');
    }
    public function status302()
    {
        $Response = new Response;
        $Response->withStatus(302)->redirect('/');
    }
}
