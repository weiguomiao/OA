<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Constraints;
use app\common\model\Project;
use app\common\model\Record;
use app\common\model\RecordInfo;
use app\common\model\User;
use app\common\service\NewsService;
use app\common\service\ProjectService;
use app\common\service\SendMessageService;
use app\common\service\UserService;
use mytools\resourcesave\ResourceManager;
use think\db\exception\DbException;
use think\facade\Db;
use think\facade\Request;
use think\response\Json;

class ProcessController extends BaseapiController
{
    /**项目详情
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function process()
    {
        //用户信息
        $user = $this->request->user;
        //接收参数检测
        $params = $this->paramsValidate([
            'project_id' => 'require'
        ]);
        //查询项目数据
        $projectInfo = Project::where('id', $params['project_id'])
            ->append(['userName'])
            ->find();
        if (empty($projectInfo)) return self::error('项目查询失败！');
        $userInfo = User::where('id', $projectInfo['user_id'])->find();
        if (empty($userInfo)) return self::error('用户查询失败！');
        //查询进度信息
        $processInfo = Constraints::where('id', $projectInfo['status'])->field('is_time,changeable,is_upload')->find();
        if (empty($processInfo)) return self::error('流程查询失败！');
        $processInfo['changeable'] = json_decode($processInfo['changeable'], true);
        if (!empty($processInfo['changeable'])) {
            $processInfo['changeableArr'] = ['key' => array_keys($processInfo['changeable']), 'value' => array_values($processInfo['changeable'])];
        } else {
            $processInfo['changeableArr'] = ['key' => [], 'value' => []];
        }

        $array = [];
        if (!empty($processInfo['changeable'])) {
            foreach ($processInfo['changeable'] as $k => $v) {
                $array[] = [$k => $v];
            }
        }
        $processInfo['changeable'] = $array;

        //是否需要显示设置周期：1是，2否
        if ($processInfo['is_time'] == 1) {
            $processInfo['period'] = 1;
        } else {
            $processInfo['period'] = 2;
        }
        //项目详情
        $recordInfo = Db::view('Record', 'id,project_id,con_id,desc,depart_id,user_id,file,title,create_time')
            ->view('User', 'username', 'User.id=Record.user_id')
            ->view('Department', 'depart_name', 'Department.id=Record.depart_id')
            ->view('Constraints', 'auth,xsb_top', 'Constraints.id=Record.con_id')
            ->where('Record.project_id', $params['project_id'])
            ->order('Record.create_time', 'desc')
            ->paginate(1000, false)
            ->toArray();
        //dump($recordInfo);die;
        $fileArr = [];
        foreach ($recordInfo['data'] as $k => $v) {
            if ($v['user_id'] == $user->id) {
                //如果记录中的用户是当前用户，则返回1显示情况说明
                $recordInfo['data'][$k]['situation'] = 1;
            } else {
                $recordInfo['data'][$k]['situation'] = 2;
            }
            $Info = RecordInfo::where('record_id', $v['id'])->order('create_time', 'desc')->select();
            foreach ($Info as $k1 => $v1) {
                $recordInfo['data'][$k]['record_Info'][$k1] = $v1;
            }
            if (!empty($v['auth'])) {
                if (in_array($user->getData('depart_id'), explode(',', $v['auth']))) {
                    $recordInfo['data'][$k]['download'] = 1;
                } else {
                    $recordInfo['data'][$k]['download'] = 2;
                }
            } else {
                $recordInfo['data'][$k]['download'] = 2;
            }
            if ($user->id == $projectInfo->user_id) {
                $recordInfo['data'][$k]['download'] = 1;
            }
            if ($v['xsb_top'] == 1 && !empty($userInfo->belong_id) && $user->id == $userInfo->belong_id) {
                $recordInfo['data'][$k]['download'] = 1;
            }
            $recordInfo['data'][$k]['create_time'] = date('m-d', $recordInfo['data'][$k]['create_time']);
            $file = json_decode($recordInfo['data'][$k]['file']);
            if (!empty($file)) {
                foreach ($file as $vs) {
                    $fileArr[] = ResourceManager::staticResource($vs);
                }
                $recordInfo['data'][$k]['file'] = $fileArr;
            } else {
                $recordInfo['data'][$k]['file'] = [];
            }
        }
        //指定经办人成员列表
        if ($user->getData('depart_id') == 2) {
            $list['data'] = User::where('belong_id', $user->id)->field('id,username,is_top')->select();
        } else {
            $list['data'] = User::where('depart_id', $user->getData('depart_id'))->field('id,username,is_top')->select();
        }
        //如果是1主管则显示指定经办人，如果不是则不显示
        if ($user->is_top == 1 && $user->getData('depart_id') != 1) {
            $list['agent'] = 1;
        } else {
            $list['agent'] = 2;
        }
        if ($user->id == $projectInfo->person_id) {
            //如果等于当前处理人，则返回1显示项目流程
            $list['dis'] = 1;
        } else {
            $list['dis'] = 2;
        }
        //如果当前流程进度为空，则不显示
        if (empty($processInfo['changeable'])) $list['dis'] = 2;
        //定义模板变量
        $viewData = [
            'projectInfo' => $projectInfo,
            'processInfo' => $processInfo,
            'recordInfo' => $recordInfo,
            'list' => $list
        ];
        //返回数据
        return self::success($viewData);
    }

    /**提交项目流程
     * @return Json|void
     * @throws DbException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setStatus()
    {
        //接收参数 ：项目状态，项目id，要填写的描述，指定经办人
        $params = $this->paramsValidate([
            'status' => 'require',
            'title' => 'require',
            'project_id' => 'require',
            'desc|项目详情' => 'max:5000',
            'file|文件' => '',
            'time|周期' => 'integer',
            'person_id|指定经办人' => ''
        ]);
        //查询当前用户信息
        $user = $this->request->user;
        if($params['status']==101){
            if($user->is_top==1) $params['status']=102;
        }
        if($params['status']==141){
            if($user->is_top==1) $params['status']=142;
        }
        //查询进度信息
        $processInfo = Constraints::where('id', $params['status'])->find();
        //判断是否文件上传
        if ($processInfo['is_file'] == 1 && empty($params['file'])) {
            return self::error('文件为空或异常，请重新上传文件！');
        }
        //查询项目信息
        $projectInfo = Project::where('id', $params['project_id'])->find();
        if (empty($projectInfo)) return self::error('查询失败');
        //新增一个记录
        $data = [
            'project_id' => $params['project_id'],
            'user_id' => $user->id,
            'con_id' => $projectInfo['status'],
            'title' => $params['title'],
            'file' => $params['file'],
            'desc' => $params['desc'],
            'depart_id' => $user->getData('depart_id')
        ];
        //存储记录
        $record = Record::create($data);
        if (empty($record)) return self::error('存储失败！');

        //判断项目是否在本部门
        if ($user->getData('depart_id') == $processInfo['depart_id']) {
            if (!empty($params['person_id'])) {
                $person_id = $params['person_id'];
            } else {
                $person_id = $user->id;
            }
        } else {
            $arr = [1 => 'zjb', 2 => 'xsb_id', 3 => 'jsb_id', 4 => 'swb_id', 5 => 'gcb_id', 6 => 'cwb_id'];
            if (empty($projectInfo[$arr[$processInfo['depart_id']]]) || $processInfo['depart_id'] == 1) {
                //如果没有指定经办人，则是找到下一状态部门主管
                if ($processInfo['depart_id'] == 2) {
                    $top = User::where('id', $projectInfo['user_id'])->find();
                    if ($top->is_top != 1) {
                        $person_id = $top->belong_id;
                    } else {
                        $person_id = $top->id;
                    }
                } else {
                    $person = User::where(['depart_id' => $processInfo['depart_id'], 'is_top' => 1])->find();
                    $person_id = $person['id'];
                }
            } else {
                $person_id = $projectInfo[$arr[$processInfo['depart_id']]];
            }
        }
        if (empty($person_id)) return self::error('该销售成员尚无所属主管！');
        //发送系统消息
        $userInfo = User::where('depart_id', '1')->where('is_top', '1')->find();
        //微信公众号模板消息
        $status = Constraints::where('id', $params['status'])->value('process_name');
        $content = [
            'project_id' => $params['project_id'],
            'number' => $projectInfo['project_number'],
            'name' => $projectInfo['project_name'],
            'status' => $status
        ];
        //发消息给总经办、立项人
        $sendArr = ['is_zjb' => $userInfo['id'], 'is_lxr' => $projectInfo['user_id']];
        foreach ($sendArr as $k => $v) {
            if ($processInfo[$k] == 1) {
                NewsService::sendMsg($user->id, $projectInfo['project_name'], $processInfo['send_news'], 1, $v, $params['project_id']);
                //微信模板消息
                ProjectService::projectMsg($v,$content);
            }
        }
        //给下一负责人发消息
        NewsService::sendMsg($user->id, $projectInfo['project_name'], $processInfo['send_news'], 1, $person_id, $params['project_id']);
        //微信模板消息
        ProjectService::projectMsg($person_id,$content);

        //是否给部门发送消息
        if ($processInfo['is_bm'] == 1) {
            $bm = explode(',', (string)$processInfo['bm_id']);
            foreach ($bm as $k1 => $v1) {
                $userIdArr = User::where('depart_id', $v1)->column('id');
                foreach ($userIdArr as $k2 => $v2) {
                    NewsService::sendMsg($user->id, $projectInfo['project_name'], $processInfo['send_news'], 1, $v2, $params['project_id']);
                    //微信模板消息
                    ProjectService::projectMsg($v2,$content);
                }
            }
        }
        //给销售部主管发送消息
        $xsb = User::where('id', $projectInfo['user_id'])->find();
        if ($processInfo['is_add_fzr'] == 1) {
            NewsService::sendMsg($user->id, $projectInfo['project_name'], $processInfo['send_news'], 1, $xsb->belong_id, $params['project_id']);
            //微信模板消息
            ProjectService::projectMsg($xsb->belong_id,$content);
        }
        //修改项目存储信息
        $projectInfo->status = $params['status'];
        //添加下一步处理人
        $projectInfo->person_id = $person_id;
        $projectInfo->is_del = 2;
        if ($user->is_top == 1 && $user->getData('depart_id') != 1) {
            $arr = [2 => 'xsb_id', 3 => 'jsb_id', 4 => 'swb_id', 5 => 'gcb_id', 6 => 'cwb_id'];
            $projectInfo[$arr[$user->getData('depart_id')]] = $params['person_id'];
        }
        //任务周期
        if ($processInfo['is_time'] == 1) {
            if (!empty($params['time'])) {
                $projectInfo->time = time() + ($params['time'] * 86400);
            } else {
                $projectInfo->time = time() + ($processInfo['time'] * 86400);
            }
        }
        $result = $projectInfo->save();
        //返回数据
        return self::success($result);
    }

    /**提交项目记录详情
     * @param Request $request
     * @return Json
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addInfo()
    {
        $params = $this->paramsValidate([
            'record_id' => 'require',
            'record_content' => 'require'
        ]);
        $record = Record::where('id', $params['record_id'])->find();
        if (empty($record)) return self::error('记录查询失败！');
        $result = RecordInfo::create($params);
        return self::success($result);
    }

    /**任务周期到期提醒
     * @throws DbException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function remind()
    {
        $conInfo = Constraints::order('id', 'desc')->find();
        $projectInfo = Project::where('status', '<', $conInfo['id'])->select();
        foreach ($projectInfo as $k => $v) {
            if (time() > $v['time']) {
                //发送到期消息
                NewsService::sendMsg('', '系统消息！', '你的项目处理到期了，请尽快处理！', '1', $v['person_id'], $v['id']);
                $status = Constraints::where('id', $v['status'])->value('process_name');
                $content = [
                    'project_id' => $v['id'],
                    'title' => '你的项目处理到期了，请尽快处理！',
                    'number' => $v['project_number'],
                    'name' => $v['project_name'],
                    'status' => $status
                ];
                ProjectService::projectMsg($v['person_id'],$content);
            }
        }
    }
}
