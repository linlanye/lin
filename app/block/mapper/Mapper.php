<?php
namespace app\block\mapper;

use lin\processor\Mapper as Base;

class Mapper extends Base
{

    protected function setting()
    {
        $this->setRule('map_id', [
            'id: must' => 'user_id', //创建map_id规则，将id字段强制映射为user_id
        ]);
    }

}
