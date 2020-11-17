<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/28
 * Time: 10:56
 */

namespace app\common\model;


use app\common\getAttr\ImageAttr;
use mytools\resourcesave\ResourceManager;
use think\Model;
use think\model\concern\SoftDelete;

class Cost extends Model
{
    // 设置json类型字段
    protected $json = ['add_img','add_file'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    //关联 费用-记录  一对多
    public function costRecord()
    {
        return $this->hasMany('CostRecord', 'status', 'id');
    }

    public function getCostProcessAttr($v, $data)
    {
        $re['cost_process'] = Cost::where('id', $data['status'])->value('cost_process');
        return $re;
    }

    public function getUserNameAttr($v, $data)
    {
        return (new User())->getUserName($data['user_id']);
    }

    public function getAddImgAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::staticResource($v);
        return $re;
    }

    public function setAddImgAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::net2Path($v);
        return $re;
    }

    public function getAddFileAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::staticResource($v);
        return $re;
    }

    public function setAddFileAttr($d)
    {
        $re=[];
        if(empty($d)) return $re;
        foreach ($d as $v)
            $re[]=ResourceManager::net2Path($v);
        return $re;
    }
}