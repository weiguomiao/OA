<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class Department extends Model
{
    protected $pk = "id";

    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /**获取器
     * @param $v
     * @param $d
     * @return mixed
     */
    public function getNumAttr($v, $d)
    {
        return User::where('depart_id', $d['id'])->where('status',1)->count();
    }

    public function getDepName($id)
    {
        return $this->find($id)['depart_name'];
    }


}
