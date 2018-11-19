<?php
namespace app\block\model;

use lin\orm\model\Model as Base;

class Model extends Base
{

    protected function setting()
    {
        $this->setTable('table')->setPK('id'); //手动指定表明和主键名
    }
}
