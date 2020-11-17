<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\common\model\Constraints;
use app\common\model\Department;
use app\common\model\Project;
use app\common\model\User;

class DepartController extends BaseapiController
{
    /**
     * 组织管理列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(){
        $list=Department::select()->append(['num']);
        return self::success($list);
    }


    /**
     * 部门成员列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function departMember(){
        //获取参数
        $params=$this->paramsValidate([
            'depart_id'=>'require'
        ]);
        //搜索条件
        $where = [];
        if (!empty($params['depart_id'])) {
            $where[] = ['depart_id', '=', $params['depart_id']];
        }
        $list = User::where($where)
            ->field('id,username,role_name,user_image,is_top')
            ->where('status','1')
            ->order('is_top', 'asc')
            ->select();
        //返回数据
        return self::success($list);
    }

    /**
     * 部门成员信息
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userInfo(){
        $params=$this->paramsValidate([
            'user_id'=>'require'
        ]);
        //查询用户信息
        $userInfo=User::where('id',$params['user_id'])
            ->field('username,user_image,depart_id,role_name,number,mobile_phone,email,is_top,belong_id')
            ->find();
        $userInfo['depart']=$userInfo->getData('depart_id');
        //判断不是主管的销售部成员
        if($userInfo['depart']==2&&$userInfo['is_top']!=1){
            $userInfo['show']=1;
        }else{
            $userInfo['show']=2;
        }
        $belong = User::where('is_top', '1')
            ->where('depart_id', '2')
            ->where('status', '1')
            ->field('id,username')
            ->select();
        $viewData=[
            'userInfo'=>$userInfo,
            'belong'=>$belong
        ];
        //返回数据
        return self::success($viewData);
    }

    /**修改成员信息
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateMember(){
        //1设置主管、2修改部门、3销售部人员所属主管ID、4职位
        $params=$this->paramsValidate([
            'user_id'=>'require',
            'depart_id'=>'integer',
            'belong_id'=>'integer',
            'role_name'=>'',
            'status'=>'in:1,2,3,4'
        ]);
        if (session('identity') != 1) {
            return self::error('权限不足！');
        }
        $userInfo=User::where('id',$params['user_id'])->find();
        switch ($params['status']){
            case 1:
                if($userInfo['is_top']==1){
                    $userInfo->is_top=2;
                }else{
                    if($userInfo->getData('depart_id')!=2){
                        $updateDe=User::where('depart_id',$userInfo->getData('depart_id'))->select();
                        foreach ($updateDe as $k=>$v){
                            $v['is_top']=2;
                            $v->save();
                        }
                    }
                    $userInfo->is_top=1;
                    $userInfo->belong_id='';
                }
                break;
            case 2:
                if(empty($params['depart_id'])){
                    return self::error('数值不能为空！');
                }
                if($userInfo['is_top']==1){
                    return self::error('主管不能调动部门！');
                }
                $arr = [ 2 => 'xsb_id', 3 => 'jsb_id', 4 => 'swb_id', 5 => 'gcb_id', 6 => 'cwb_id'];
                $con=Constraints::order('id','desc')->find();
                foreach ($arr as $k=>$v){
                    if($userInfo['depart_id']==$k){
                        $count=Project::where($v,$params['user_id'])->where('status','<',$con['id'])->count();
                        if($count>0){
                            return self::error('该成员已被指定为项目负责人，不能调动！');
                        }
                    }
                }
                $userInfo->depart_id=$params['depart_id'];
                break;
            case 3:
                if(empty($params['belong_id'])){
                    return self::error('数值不能为空！');
                }
                $userInfo->belong_id=$params['belong_id'];
                break;
            case 4:
                if(empty($params['role_name'])){
                    return self::error('数值不能为空！');
                }
                $userInfo->role_name=$params['role_name'];
                break;
            default:
                return self::error('参数错误！');
        }
        $result=$userInfo->save();
        return self::success($result);
    }

    /**新增修改部门
     * @return \think\response\Json
     */
    public function addDepart(){
        $params=$this->paramsValidate([
            'id|部门id'=>'',
            'depart_name|部门名称'=>'require'
        ]);
        if (session('identity') != 1) {
            return self::error('权限不足！');
        }
        if(empty($params['id'])){
            $result=Department::create($params);
        }else{
            $result=Department::update($params);
        }
        return self::success($result);
    }



    /**删除部门
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delDepart(){
        $params=$this->paramsValidate([
            'id'=>'require'
        ]);
        if (session('identity') != 1) {
            return self::error('权限不足！');
        }
        $departInfo=(new Department())->find($params['id']);
        if($departInfo['id']<=7){
            return self::error('该部门不可删除！');
        }
        //如果该部门有人，不能删除
        $userInfo=User::where('depart_id',$params['id'])->count();
        if($userInfo>0){
            return self::error('该部门还有成员，不可删除！');
        }
        $result=Department::destroy($params['id']);
        return self::success($result);
    }


}
