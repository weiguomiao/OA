<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class ApplyRecord extends Model
{
    //
    public function apply()
    {
        return $this->belongsTo('Apply', 'apply_id', 'id');
    }


    public function getHeadImageAttr($v, $data)
    {
        return User::find($data['user_id'])['user_image'];
    }

    public function getUserIdAttr($v)
    {
        return (new User())->getUserName($v);
    }
}
