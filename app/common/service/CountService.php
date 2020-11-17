<?php
declare (strict_types=1);

namespace app\common\service;

use app\common\model\News;
use app\common\model\UserCount;
use app\common\model\UserNews;

class CountService
{

    /**
     * 考勤统计
     * @param $uid -用户ID
     * @param $depart_id -标题
     * @param $type -类型
     * @param $num -数量
     * @param bool $extend -扩展
     */
    public static function userCount($uid, $depart_id, $type, $num)
    {
        switch ($type) {
            case 1:
                $data['leave'] = ['inc', $num];
                break;
            case 2:
                $data['travel'] = ['inc', $num];
                break;
            case 3:
                $data['late'] = ['inc', $num];
                $data['day'] = ['inc', 1];
                break;
        }
        $time = date('Y-m');
        $data['time'] = $time;
        $data['user_id'] = $uid;
        $data['depart_id'] = $depart_id;
        $check = UserCount::where('time', $time)->where('user_id', $uid)->find();
        if (empty($check)) {
            UserCount::create($data);
        } else {
            UserCount::where('time', $time)->where('user_id', $uid)->save($data);
        }

    }
}
