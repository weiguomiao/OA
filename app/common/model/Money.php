<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/28
 * Time: 10:56
 */

namespace app\common\model;


use app\common\getAttr\ImageAttr;
use think\Model;
use think\model\concern\SoftDelete;

class Money extends Model
{
    use ImageAttr;
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //关联 费用-记录  一对多
    public function costRecord()
    {
        return $this->hasMany('CostRecord', 'status', 'id');
    }

    public function getCostProcessAttr($v, $data)
    {
        $re['cost_process'] = Cost::where('id', $data['status'])->value('cost_process');
        return $re;
    }
}