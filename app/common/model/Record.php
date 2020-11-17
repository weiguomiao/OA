<?php
declare (strict_types=1);

namespace app\common\model;

use app\common\getAttr\ImageAttr;
use mytools\resourcesave\ResourceManager;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class Record extends Model
{
    // 设置json类型字段
    protected $json = ['file'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    public function getFileAttr($data)
    {
        $arr=[];
        foreach (json_decode($data,true) as $v){
            $arr[]=ResourceManager::staticResource($v);
        }
        return $arr;
    }

    public function setFileAttr($v)
    {
        return ResourceManager::net2Path($v);
    }

    protected $pk = "id";
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //定义关联 一个记录属于一个项目
    public function project()
    {
        return $this->belongsTo('Project', 'project_id', 'id')
            ->bind(['project_name', 'project_applicant', 'apply_time', 'start_time']);
    }

    public function getUserIdAttr($v)
    {
        return (new User())->getUserName($v);
    }

    public function getDepartIdAttr($v)
    {
        return (new Department())->getDepName($v);
    }
}
