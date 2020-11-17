<?php
/**
 * Created by PhpStorm.
 * User: cs
 * Date: 2019/4/29
 * Time: 9:47
 */

namespace app\common\getAttr;

/**
 * Trait IsEnableAttr  火星坐标获取器
 * @package app\common\getAttr
 */
Trait Gcj02Attr
{

    // 火星坐标获取器
    public function getGcj02LatAttr($v)
    {
        return $this->getValue($v);
    }

    public function setGcj02LatAttr($v)
    {
        return $this->setValue($v);
    }

    public function getGcj02LngAttr($v)
    {
        return $this->getValue($v);
    }

    public function setGcj02LngAttr($v)
    {
        return $this->setValue($v);
    }

    private function setValue($v)
    {
        $str = (string)$v;
        if(strpos($str,'.') !== false) {
            return $v * config('conf.location.rate');
        }
        return $v;
    }

    private function getValue($v)
    {
        return $v / config('conf.location.rate');
    }
}