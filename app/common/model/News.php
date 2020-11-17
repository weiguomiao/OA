<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class News extends Model
{
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //关联  消息-用户消息 一对多
    public function userNews(){
        return $this->hasMany('UserNews','news_id','id');
    }

    /**获取阅读人阅读数量
     * @param $v
     * @param $d
     * @return mixed
     */
    public function getAmountAttr($v,$d){
        $re['looked']=UserNews::where('news_id',$d['id'])->where('status','1')->count();
        $re['unlook']=UserNews::where('news_id',$d['id'])->where('status','2')->count();
        return $re;
    }
}
