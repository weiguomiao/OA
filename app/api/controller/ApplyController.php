<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Apply;
use app\common\model\ApplyRecord;
use app\common\model\ApplySetup;
use app\common\model\ApplyStatus;
use app\common\model\News;
use app\common\model\Send;
use app\common\model\User;
use app\common\model\UserCount;
use app\common\model\UserNews;
use app\common\service\CountService;
use app\common\service\NewsService;
use app\common\service\ProjectService;
use app\common\service\SendMessageService;
use app\common\service\UserService;
use mytools\lib\ToolBag;
use think\App;
use think\facade\Log;
use think\Request;

class ApplyController extends BaseapiController
{
    /**
     * 获取申请列表
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function index(Request $request)
    {
        $type = $this->request->post('type', 1);
        //搜索条件
        $w = [];
        $uid = $request->user->id;
        switch ($type) {
            case 1:
                $w['user_id'] = $uid;
                break;
            case 2:
                $arr = ApplyRecord::where('status', 1)->where('user_id', $uid)->column('apply_id');
                $w[] = ['id', 'in', $arr];
                break;
            case 3:
                $arr = ApplyRecord::where('status', 'in', [2, 3])->where('user_id', $uid)->column('apply_id');
                $w[] = ['id', 'in', $arr];
                break;
            case 4:
                $arr = Send::where('type', '1')->where('user_id', $uid)->column('extend_id');
                $w[] = ['id', 'in', $arr];
                break;
            default:
                return self::error('参数错误！');
        }
        $applyList = Apply::where($w)
            ->order('create_time', 'desc')
            ->paginate(10);
        return self::success($applyList);
    }

    /**提交申请
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addApply()
    {
        $user = $this->request->user;
        $params = $this->paramsValidate([
            'type|申请类型' => 'require|in:1,2',
            'start_time|开始时间' => 'require',
            'end_time|结束时间' => 'require',
            'remark|申请备注' => 'require'
        ]);
        $params['user_id'] = $user->id;
        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);
        $Apply = Apply::create($params);
        //判断是否部门主管
        if ($user->is_top == 1) {
            $Apply->step = 2;
            $Apply->save();
            $step = 2;
        } else {
            $step = 1;
        }
        $w['type'] = $params['type'];
        $w['step'] = $step;
        $applySetup = ApplySetup::where($w)->find();
        if ($applySetup['verify_user'] == -1 || empty($applySetup['verify_user'])) {
            if ($user->getData('depart_id') != 2) {
                $userInfo = User::where('is_top', '1')->where('depart_id', $user->getData('depart_id'))->find();
                $applySetup['verify_user'] = $userInfo['id'];
            } else {
                $applySetup['verify_user'] = $user->belong_id;
            }
        }
        $day = ($params['end_time'] - $params['start_time']) / 86400;
        $next = ApplySetup::where('type', $params['type'])
            ->select();
        foreach ($next as $k => $v) {
            if (!empty($v['extend']) && $day >= $v['extend'])
                $applySetup['verify_user'] = $v['verify_user'];
        }
        //添加审核记录
        ApplyRecord::create([
            'user_id' => $applySetup['verify_user'],
            'apply_id' => $Apply->id,
        ]);
        //向审核人发送消息
        $title = $params['type'] == 1 ? '请假申请' : '出差申请';
        NewsService::sendMsg($user->id, $title, '您有一条申请待审核', 2, $applySetup['verify_user'], $Apply->id);
        //如果有抄送人，向抄送人发布消息
        if (!empty($applySetup['msg_user'])) {
            Send::create(['user_id' => $applySetup['msg_user'], 'extend_id' => $Apply->id, 'type' => 1]);
            NewsService::sendMsg($user->id, $title, '您有一条申请待审核', 2, $applySetup['msg_user'], $Apply->id);
            $content = [
                'apply_id'=>$Apply->id,
                'ty'=>4,
                'title'=>'有一条'.$title.'等待审批',
                'status'=>4,
                'type' => $title,
                'user' => $user->username,
                'time' => time(),
            ];
            ProjectService::applyMsg($applySetup['msg_user'],$content);
        }
        //发送微信模板消息
        $apicontent = [
            'apply_id'=>$Apply->id,
            'ty'=>2,
            'title'=>'有一条'.$title.'待您审批',
            'status'=>2,
            'type' => $title,
            'user' => $user->username,
            'time' => time(),
        ];
        ProjectService::applyMsg($applySetup['verify_user'],$apicontent);
        return self::success('成功');
    }


    /**申请详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function display()
    {
        $params = $this->paramsValidate([
            'apply_id|申请ID' => 'require'
        ]);
        $data['info'] = Apply::where('id', $params['apply_id'])->append(['headImage'])->find();
        $data['list'] = ApplyRecord::where('apply_id', $params['apply_id'])
            ->field('user_id,status,create_time,update_time')
            ->append(['headImage'])
            ->select();
        $record=ApplyRecord::where(['user_id'=>$this->request->user->id,'status'=>1,'apply_id'=>$params['apply_id']])->find();
        $data['display']=2;
        if(!empty($record)){
            $data['display']=1;
        }
        return self::success($data);
    }

    /**
     * 申请审批
     * @param Request $request
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function applyStatus(Request $request)
    {
        $user = $request->user;
        $params = $this->paramsValidate([
            'apply_id|申请ID' => 'require',
            'status|状态' => 'require|in:2,3',
        ]);
        $record = ApplyRecord::where('apply_id', $params['apply_id'])
            ->where('user_id', $user->id)
            ->find();
        if (empty($record)) return self::error('非法操作');
        $record->status = $params['status'];
        $record->save();

        //查询当前审核进行步骤
        $applyInfo = Apply::where('id', $params['apply_id'])->find();
        $applyUser = ApplySetup::where('step', $applyInfo['step'])
            ->where('type', $applyInfo->getData('type'))
            ->find();

        //发送消息
        $content = $params['status'] == 2 ? '您的申请已通过审核' : '您的提交已被驳回';
        $title = $applyInfo->getData('type') == 1 ? '请假申请' : '出差申请';
        $status=[2=>'通过',3=>'拒绝'];
        $result_content = [
            'apply_id'=>$params['apply_id'],
            'title'=>'您的'.$title.'已被审批',
            'ty'=>1,
            'apply_user' =>$user->username,
            'start_time' =>$applyInfo->start_time,
            'end_time' =>$applyInfo->end_time,
            'reason'=>$applyInfo->remark,
            'status'=>$status[$params['status']]
        ];
        NewsService::sendMsg($user->id, '审核通知', $content, 2, $applyInfo->getData('user_id'), $applyInfo->id);
        //发送微信模板消息
        //ProjectService::applyResultMsg($applyInfo->getData('user_id'),$result_content);
        if (!empty($applyUser['msg_user'])) {
            Send::create(['user_id' => $applyUser['msg_user'], 'extend_id' => $applyInfo->id, 'type' => 1]);
            NewsService::sendMsg($user->id, '审核通知', $content, 2, $applyUser['msg_user'], $applyInfo->id);
            //发送微信模板消息
            $send_content = [
                'apply_id'=>$params['apply_id'],
                'title'=>'有一条'.$title.'已被处理',
                'ty'=>3,
                'apply_user' =>$user->username,
                'start_time' =>$applyInfo->start_time,
                'end_time' =>$applyInfo->end_time,
                'reason'=>$applyInfo->remark,
                'status'=>$status[$params['status']]
            ];
            ProjectService::applyResultMsg($applyUser['msg_user'],$send_content);
        }

        //查询当前步骤是否最后一步
        $step = ApplySetup::where('step', '>', $applyInfo['step'])
            ->where('type', $applyInfo->getData('type'))
            ->find();
        $day = (strtotime($applyInfo['end_time']) - strtotime($applyInfo['start_time'])) / 86400;
        if (!empty($step)) {
            if (($applyInfo->getData('type') == 1 || $applyInfo->getData('type') == 2) && $day <= $step['extend']) {
                $applyInfo->status = $params['status'];
                CountService::userCount($applyInfo->getData('user_id'), $user->getData('depart_id'), $applyInfo->getData('type'), $day);
            } else {
                ApplyRecord::create([
                    'user_id' => $step['verify_user'],
                    'apply_id' => $params['apply_id']
                ]);
                //发送公告
                NewsService::sendMsg($user->id, $title, '您有一条申请待审核', 2, $step['msg_user'], $applyInfo->id);
                //发送微信模板消息
                $apicontent = [
                    'apply_id'=>$params['apply_id'],
                    'title'=>'有一条'.$title.'待您审批',
                    'ty'=>2,
                    'type' => $title,
                    'user' => $user->username,
                    'time' => time(),
                ];
                ProjectService::applyMsg( $step['verify_user'],$apicontent);
                $applyInfo->status = 1;
            }
            $applyInfo->step = ['inc', 1];
        } else {
            $applyInfo->status = $params['status'];
            CountService::userCount($applyInfo->getData('user_id'), $user->getData('depart_id'), $applyInfo->getData('type'), $day);
        }
        $applyInfo->save();
        return self::success('修改成功');
    }


    /**
     * 流程配置
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function applyStep(Request $request)
    {
        $param = $this->paramsValidate([
            'type|类型' => 'require',
            'data|步骤' => 'require'
        ]);
        if ($request->user->is_admin != 1) {
            return self::error('您无权操作此功能');
        }
        $verify = ApplySetup::where('type', $param['type'])->find();
        if (!empty($verify)) {
            ApplySetup::where('type', $param['type'])->delete();
        }
        $arr = [];
        foreach ($param['data'] as $v) {
            $arr[] = [
                'type' => $param['type'],
                'step' => $v['step'],
                'verify_user' => $v['verify_user'],
                'msg_user' => $v['msg_user'],
                'extend' => $v['extend']
            ];
        }
        (new ApplySetup())->insertAll($arr);
        return self::success('操作成功');
    }

    /**
     * 步骤详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function stepInfo()
    {
        $type = $this->request->post('type');
        $list = ApplySetup::where('type', $type)->append(['verify_name', 'msg_name'])->select();
        return self::success($list);
    }
}
