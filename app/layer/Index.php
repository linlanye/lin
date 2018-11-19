<?php

namespace app\layer;

use lin\layer\Layer;

class Index extends Layer
{
    protected function setting()
    {
        $this->use('http');
    }

    public function index()
    {
        $this->Response->view('welcome');
    }

}
