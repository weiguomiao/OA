<?php
declare (strict_types=1);

namespace app\common\model;

use app\common\getAttr\ImageAttr;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class Project extends Model
{
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 设置json类型字段
    protected $json = ['auth'];

    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    //定义一对多关联，一个项目有多个记录
    public function record()
    {
        return $this->hasMany('Record', 'project_id', 'id');
    }

    public function getCreateTimeAttr($v)
    {
        return date('Y-m-d', $v);
    }

    /**
     * 用户信息获取器
     * @param $v
     * @param $data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoAttr($v, $data)
    {
        return User::where('id', $data['person_id'])->field('username,depart_id')->find();
    }

    /**
     * 项目详细信息
     * @param $v
     * @param $data
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordInfoAttr($v, $data)
    {
        return Record::where('project_id', $data['id'])
            ->order('id', 'desc')
            ->field('user_id,depart_id')
            ->find();
    }


    /**
     * 立项人名称
     * @param $v
     * @param $data
     * @return mixed
     */
    public function getUserNameAttr($v, $data)
    {
        return (new User())->getUserName($data['user_id']);
    }

    public function getStatusTextAttr($v, $data)
    {
        return Constraints::find($data['status'])['process_name'];
    }

    public function getBelongIdAttr($v,$d){
        return User::where('id',$d['user_id'])->value('belong_id');
    }

    public function getStateAttr($v,$d){
        $last=Constraints::order('id','desc')->find();
        if($d['status']<$last['id'])
            return 1;
        else
            return 2;
    }
}
