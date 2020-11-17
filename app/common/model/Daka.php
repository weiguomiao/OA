<?php
declare (strict_types=1);

namespace app\common\model;

use app\common\getAttr\ImageAttr;
use think\Model;

/**
 * @mixin \think\Model
 */
class Daka extends Model
{
    use ImageAttr;
    protected $pk = ['id'];

    public function getUserInfoAttr($v,$d){
        return User::where('id',$d['user_id'])->field('username,depart_id')->find();
    }
}
