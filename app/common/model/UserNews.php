<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class UserNews extends Model
{
    //关联 一个用户消息属于一个消息
    public function news(){
        return $this->belongsTo('News','news_id','id');
    }

    public function getUserInfoAttr($v,$d){
        return User::where('id',$d['user_id'])->value('username,user_image');
    }

    public function getTitleAttr($v,$d){
        return News::where('id',$d['news_id'])->value('title');
    }
}
