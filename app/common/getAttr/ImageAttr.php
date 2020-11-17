<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/8/23
 * Time: 17:35
 */

namespace app\common\getAttr;
use mytools\resourcesave\ResourceManager;

trait ImageAttr
{
    public function getImageAttr($v)
    {
        return ResourceManager::staticResource($v);
    }

    public function setImageAttr($v)
    {
        return ResourceManager::net2Path($v);
    }
}