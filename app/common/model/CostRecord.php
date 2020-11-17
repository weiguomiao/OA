<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class CostRecord extends Model
{
    public function getHeadImageAttr($v, $data)
    {
        return User::find($data['user_id'])['user_image'];
    }

    public function getUserIdAttr($v)
    {
        return (new User())->getUserName($v);
    }

    /**
     * 状态
     * @param $v
     * @return mixed
     */
    public function getStatusAttr($v)
    {
        $status = [1 => '待处理', 2 => '审批通过', '3' => '审批未通过'];
        return $status[$v];
    }
}
