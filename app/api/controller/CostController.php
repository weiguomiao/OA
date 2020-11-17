<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/26
 * Time: 17:59
 */

namespace app\api\controller;


use app\common\model\ApplySetup;
use app\common\model\Cost;
use app\common\model\CostRecord;
use app\common\model\Send;
use app\common\model\User;
use app\common\service\NewsService;
use app\common\service\ProjectService;
use app\common\service\SendMessageService;
use app\common\service\UserService;
use app\Request;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\response\Json;

class CostController extends BaseapiController
{

    /**
     * 获取申请列表
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function costList(Request $request)
    {
        //1我提交的 2待我审批 3已审批 4抄送我
        $type = $this->request->post('type', 1);
        //搜索条件
        $w = [];
        $uid = $request->user->id;
        switch ($type) {
            case 1:
                $w['user_id'] = $uid;
                break;
            case 2:
                $arr = CostRecord::where('status', '1')->where('user_id', $uid)->column('cost_id');
                $w[] = ['id', 'in', $arr];
                break;
            case 3:
                $arr = CostRecord::where('status', 'in', [2, 3])->where('user_id', $uid)->column('cost_id');
                $w[] = ['id', 'in', $arr];
                break;
            case 4:
                $arr=Send::where('type','2')->where('user_id',$uid)->column('extend_id');
                $w[]=['id','in',$arr];
                break;
            default:
                return self::error('参数错误！');
        }
        $list = Cost::where($w)
            ->order('create_time', 'desc')
            ->paginate(10);
        $count = CostRecord::where('user_id', $request->user->id)->where('status', 1)->count();
        $arr = ApplySetup::where('type', 3)->column('verify_user');
        $auth = in_array('user_id', $arr) == true ? 1 : 2;
        return self::success(compact('list', 'count', 'auth'));
    }

    /**
     * 提交费用审批
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function applyCost()
    {
        //接收参数
        $params = $this->paramsValidate([
            'apply_money|申请金额' => 'require',
            'apply_type|申请类别' => 'require',
            'payee|收款人' => 'require',
            'pay_num|收款账号' => 'require',
            'account|开户行' => 'require',
            'apply_reason|申请理由' => '',
            'add_file|文件附件' => '',
            'add_img|图片附件' => ''
        ]);
        //保存数据
        $user = $this->request->user;
        $params['user_id'] = $user->id;
        if ($user->is_top == 1) {
            $step = 2;
        } else {
            $step = 1;
        }
        $params['step'] = $step;
        $params['type']=3;
        $cost = Cost::create($params);
        //判断是否部门主管
        $w['type'] = 3;
        $w['step'] = $step;
        $applySetup = ApplySetup::where($w)->find();
        if($applySetup['verify_user']==-1||empty($applySetup['verify_user'])){
            if($user->getData('depart_id')!=2){
                $userInfo=User::where('is_top','1')->where('depart_id',$user->getData('depart_id'))->find();
                $applySetup['verify_user']=$userInfo['id'];
            }else{
                $applySetup['verify_user']=$user->belong_id;
            }
        }
        //添加审核记录
        CostRecord::create([
            'user_id' => $applySetup['verify_user'],
            'cost_id' => $cost->id,
        ]);
        //向审核人发送消息
        NewsService::sendMsg($user->id, '费用申请', '您有一条申请待审核', 2, $applySetup['verify_user'], $cost->id);
        //发送微信模板消息
        $apicontent = [
            'cost_id'=>$cost->id,
            'title'=>'有一条费用申请待你审批',
            'ty'=>2,
            'user' => $user->username,
            'status' => '待处理',
            'money'=>$params['apply_money'],
            'time' => time(),
        ];
        ProjectService::costMsg($applySetup['verify_user'],$apicontent);
        //如果有抄送人，向抄送人发布消息
        if (!empty($applySetup['msg_user'])) {
            Send::create(['user_id'=>$applySetup['msg_user'],'extend_id'=>$cost->id,'type'=>2]);
            NewsService::sendMsg($user->id, '费用申请', '您有一条申请待审核', 2, $applySetup['msg_user'], $cost->id);
            $content = [
                'cost_id'=>$cost->id,
                'title'=>'有一条费用申请被提交',
                'ty'=>4,
                'user' => $user->username,
                'status' => '待处理',
                'money'=>$params['apply_money'],
                'time' => time(),
            ];
            ProjectService::costMsg($applySetup['msg_user'],$content);
        }
        //返回数据
        return self::success('成功');
    }


    /**
     * 费用审批详情
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function costInfo()
    {
        $params = $this->paramsValidate([
            'cost_id|申请费用ID' => 'require'
        ]);
        $info = Cost::where('id', $params['cost_id'])->append(['userName'])->find();
        $list = CostRecord::where('cost_id', $params['cost_id'])
            ->order('id', 'asc')
            ->field('user_id,status,create_time,update_time')
            ->append(['headImage', 'statusText'])
            ->select();
        $record=CostRecord::where(['user_id'=>$this->request->user->id,'cost_id'=>$params['cost_id'],'status'=>1])->find();
        $display=2;
        if(!empty($record)){
            $display=1;
        }
        return self::success(compact('info', 'list','display'));
    }

    /**
     * 审批
     * @param Request $request
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function costStatus(Request $request)
    {
        $user = $request->user;
        $params = $this->paramsValidate([
            'cost_id|ID' => 'require',
            'status|状态' => 'require|in:2,3',
        ]);
        $record = CostRecord::where('cost_id', $params['cost_id'])
            ->where('user_id', $user->id)
            ->find();
        if (empty($record)) return self::error('非法操作');
        $record->status = $params['status'];
        $record->save();

        //查询当前审核进行步骤
        $applyInfo = Cost::where('id', $params['cost_id'])->find();
        $applyUser = ApplySetup::where('step', $applyInfo['step'])->where('type', 3)->find();

        //发送消息
        $content = $params['status'] == 2 ? '您的申请已通过审核' : '您的提交已被驳回';
        $status=[2=>'通过',3=>'拒绝'];
        $result_content = [
            'cost_id'=>$params['cost_id'],
            'title'=>'您的费用申请已被处理',
            'ty'=>1,
            'user' =>$user->username,
            'status' =>$status[$params['status']],
            'time' =>time(),
        ];
        //发送微信模板消息
        ProjectService::costResultMsg($applyInfo->getData('user_id'),$result_content);
        NewsService::sendMsg($user->id, '审核通知', $content, 2, $applyInfo->getData('user_id'),$applyInfo->id);
        if (!empty($applyUser['msg_user'])) {
            Send::create(['user_id'=>$applyUser['msg_user'],'extend_id'=>$applyInfo->id,'type'=>2]);
            NewsService::sendMsg($user->id, '审核通知', $content, 2, $applyUser['msg_user'],$applyInfo->id);
            $send_content = [
                'cost_id'=>$params['cost_id'],
                'title'=>'有一条费用申请已被处理',
                'ty'=>4,
                'user' =>$user->username,
                'status' =>$status[$params['status']],
                'time' =>time(),
            ];
            ProjectService::costResultMsg($applyUser['msg_user'],$send_content);
        }

        //查询当前步骤是否最后一步
        $step = ApplySetup::where('step', '>', $applyInfo['step'])
            ->where('type', $applyInfo['type'])
            ->find();
        if (!empty($step)) {
            CostRecord::create([
                'user_id' => $step['verify_user'],
                'cost_id' => $params['cost_id']
            ]);
            NewsService::sendMsg($user->id, '费用申请', '您有一条申请待审核', 2, $step['msg_user'], $applyInfo->id);
            $apicontent = [
                'cost_id'=>$params['cost_id'],
                'title'=>'有一条费用申请待您审批',
                'ty'=>2,
                'user' => $user->username,
                'status' => '待处理',
                'money'=>$applyInfo->apply_money,
                'time' => time(),
            ];
            //发送微信模板消息
            ProjectService::costMsg($step['msg_user'],$apicontent);
            //更改申请状态
            $applyInfo->status=1;
            $applyInfo->step=['inc',1];
        } else {
            $applyInfo->status = $params['status'];
        }
        $applyInfo->save();
        return self::success('操作成功');
    }

}