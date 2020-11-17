<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Apply extends Model
{
    protected $type = [
        'start_time' => 'timestamp:Y-m-d',
        'end_time' => 'timestamp:Y-m-d',
        'create_time' => 'timestamp:Y-m-d',
    ];


    public function applyRecord()
    {
        return $this->hasOne('ApplyRecord', 'apply_id', 'id');
    }

    /**获取处理状态和名称
     * @param $v
     * @param $data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserStatusAttr($v, $data)
    {
        $user = ApplyRecord::where('apply_id', $data['id'])->find();
        $re['username'] = User::where('id', $user['user_id'])->value('username');
        $re['status'] = $data['status'];
        return $re;
    }

//    /**
//     * 状态
//     * @param $v
//     * @return mixed
//     */
//    public function getStatusAttr($v)
//    {
//        $status = [1 => '待处理', 2 => '审批通过', '3' => '审批未通过'];
//        return $status[$v];
//    }

    /**
     * 申请类型
     * @param $v
     * @return mixed
     */
    public function getTypeAttr($v)
    {
        $type = [1 => '请假', 2 => '出差'];
        return $type[$v];
    }

    public function getUserIdAttr($v)
    {
        return (new User())->getUserName($v);
    }

    public function getHeadImageAttr($v, $data)
    {
        return User::find($data['user_id'])['user_image'];
    }
}
