<?php

namespace app\layer;

use lin\layer\Layer;

class Index extends Layer
{
    //设置
    protected function setting()
    {
        $this->use('http'); //使用http对象，Response和Request
    }

    public function index()
    {
        $this->Response->view('welcome');
    }

}
