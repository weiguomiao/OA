<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Constraints;
use app\common\model\News;
use app\common\model\Project;
use app\common\model\Record;
use app\common\model\User;
use app\common\model\UserNews;
use app\common\service\NewsService;
use app\common\service\ProjectService;
use app\common\service\SendMessageService;
use app\common\service\UserService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Request;
use think\Response;
use think\response\Json;


class ProjectController extends BaseapiController
{
    /**
     * 项目进展列表
     * @return Json
     * @throws DbException
     */
    public function projectProcess(){
        //1是申请立项列表，2是项目进展列表
        $user=$this->request->user;
        $params=$this->paramsValidate([
            'keyword'=>'',
            'status'=>'require|in:1,2'
        ]);
        //搜索条件
        $where = [];
        if (!empty($params['keyword'])) {
            $where[] = ['project_name','like',"%".$params['keyword']."%"];
        }
        if($params['status']==1){
            $where[]=['user_id','=',$user->id];
        }
        //项目进展
        $projectInfo = Project::where('id', '>', 0)
            ->where($where)
            ->field('id,project_name,status,desc,create_time,user_id,person_id,auth')
            ->append(['userInfo', 'userName', 'statusText'])
            ->order('create_time', 'desc')
            ->paginate(5,false)
            ->toArray();
        //返回数据
        return self::success($projectInfo);
    }

    /**
     * 添加修改项目信息
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addProject()
    {
        $user=$this->request->user;
        //接收参数+参数检测
        $params = $this->paramsValidate([
            'id' => '',
            'project_name|项目名称' => 'require',
            'project_type|项目类型' => 'require',
            'project_addr|项目地址' => 'require',
            'customer|客户' => 'require',
            'mobile_phone|联系电话' => 'require|mobile',
            'project_budget|项目预算' => 'require',
            'start_time|启动时间' => 'require',
            'pay_method|付款方式' => 'require',
            'period|周期' => 'require',
            'desc|项目详情' => 'require|max:5000'
        ]);
        //判断权限
        if($user->getData('depart_id')!=2){
            return self::error('权限不足！');
        }
        if(empty($params['id'])){
            $params['user_id']=$user->id;
            $params['project_number']=date('YmdHis',time()).rand(1000,9999);
//            if($user->is_top==1){
//                //查询第一条流程
//                $conInfo=Constraints::where('id','101')->find();
//                //查询处理人总经办用户信息
//                $userInfo=User::where(['depart_id'=>$conInfo['depart_id'],'is_top'=>'1'])->find();
//                $params['person_id']=$userInfo['id'];
//                $params['belong_id']=$user->id;
//                $params['status']=101;  //下一步状态
//            }else{
//                //查询第一条流程
//                $conInfo=Constraints::where('id','100')->find();
//                $params['person_id']=$user->belong_id;
//                $params['belong_id']=$user->belong_id;
//                $params['status']=100;  //下一步状态
//            }
            if($user->is_top==1){
              $params['belong_id']=$user->id;
            }else{
              $params['belong_id']=$user->belong_id;
            }
            $params['person_id']=$user->id;
            $params['status']=100;
            if(empty($params['belong_id'])) return self::error('你尚无所属主管，不能操作！');
            //取流程约束表中的部门id后，去重取值
            $pa=Constraints::column('depart_id');
            $params['auth']=array_values(array_unique($pa));
            //
            //添加数据
            $info = Project::create($params);
            //发送消息
            //如果下一个操作人不是本人，则需要消息通知
//            if($params['person_id']!=$user->id){
//                NewsService::sendMsg($user->id, $info['project_name'], $conInfo['send_news'], 1, $info['person_id'],$info['id']);
//                $status=Constraints::where('id',$params['status'])->value('process_name');
//                $content=[
//                    'project_id'=>$info->id,
//                    'number'=>$params['project_number'],
//                    'name'=>$params['project_name'],
//                    'status'=>$status
//                ];
//                ProjectService::projectMsg($info['person_id'],$content);
//            }
        }else{
//            $projectInfo=Project::where('id',$params['id'])->find();
//            if($projectInfo['is_del']!=1){
//                return self::error('该项目已被审批，不可修改！');
//            }
            /*
            $projectInfo['is_del']=['inc',1];
            if($projectInfo['is_del']>3){
                return self::error('该项目已被修改多次，不可修改！');
            }
            $projectInfo->save();
            */
            //修改参数
            $info = Project::update($params);
        }
        //返回数据
        return self::success($info);
    }



    /**
     * 删除项目
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function delProject()
    {
        $user=$this->request->user;
        //接收参数id
        $params = $this->paramsValidate([
            'project_id' => 'require'
        ]);
        //判断权限
        if($user->getData('depart_id')!=2){
        return self::error('权限不足！');
        }
        $projectInfo=Project::where('id',$params['project_id'])->find();
        if($projectInfo['is_del']!=1){
            return self::error('该项目已被审批，不可删除！');
        }
        /*
        if($projectInfo['is_del']>1){
            return self::error('该项目已被审批，不可删除！');
        }
        */
        //删除数据
        Project::destroy($params['project_id']);
        //返回数据
        return self::success('删除成功');
    }

    /**项目进行情况
     * @return Json
     * @throws DbException
     */
    public function projectHandle(){
        //接收项目状态：1全部，2进行中，3已完成
        $params=$this->paramsValidate([
            'status'=>'require|in:1,2,3',
            'keyword'=>''
        ]);
        //搜索条件
        $where=[];
        if(!empty($params['keyword'])){
            $where[]=['project_name','like','%'.$params['keyword'].'%'];
        }
        $con=Constraints::order('id','desc')->find();
        if($params['status']==2){
            $where[]=['status','<',$con['id']];
        }
        if($params['status']==3){
            $where[]=['status','=',$con['id']];
        }
        $list=Project::where($where)
            ->append(['userName','state'])
            ->field('id,project_name,user_id,start_time,project_number,status,create_time')
            ->order('create_time','desc')
            ->paginate(10,false);
        return self::success($list);
    }

    /**项目展示详情
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function projectInfo(){
        $params=$this->paramsValidate([
            'id|项目id'=>'require'
        ]);
        $list=Project::where('user_id',$this->request->user->id)->where('id',$params['id'])->find();
        return self::success($list);
    }
}
