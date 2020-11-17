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
class User extends Model
{
    protected $pk = 'id';

    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //关联模型
    public function getDepartIdAttr($v)
    {
        return (new Department())->getDepName($v);
    }

    public function getUserName($uid)
    {
        return $this->find($uid)['username'];
    }

    /**
     * 获取当前用户部门主管ID
     * @param $uid
     * @return mixed
     */
    public function getDepartTop($uid)
    {
        $id = $this->where('uid', $uid)->value('depart_id');
        return $this->where('depart_id', $id)->where('is_top', 1)->value('id');
    }
    
    public function getUserImageAttr($v)
    {
        return ResourceManager::staticResource($v);
    }

    public function setUserImageAttr($v)
    {
        return ResourceManager::net2Path($v);
    }
}
