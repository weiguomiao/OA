<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Openid;
use app\common\model\User;
use app\common\service\WeChatService;
use EasyWeChat\Factory;
use think\Request;
use think\Response;
use think\response\Json;

class UserController extends BaseapiController
{
    /**
     * 用户列表
     *
     * @return Response
     * @throws \think\db\exception\DbException
     */
    public function index()
    {
        $list = User::order('id', 'desc')
            ->where('status', '1')
            ->select();
        $belong = User::where('is_top', '1')
            ->where('depart_id', '2')
            ->where('status', '1')
            ->field('id,username')
            ->select();
        //返回数据
        return self::success(compact('list', 'belong'));
    }

    /**
     * 新增员工
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save()
    {
        //判断是否是管理员
        if ($this->request->user->is_admin != 1) {
            return self::error('权限不足！');
        }
        //接收参数
        $params = $this->paramsValidate([
            'username|用户名' => 'require',
            'mobile_phone|电话号码' => 'require|mobile',
            'role_name|职位' => 'require',
            'depart_id|所属部门' => 'require|integer',
            'add_time|入职日期' => 'require',
            'belong_id|所属部门' => ''
        ]);
        //找到最后一个用户，获取他的ID
        $num = User::order('number', 'desc')->value('number');
        $sum = $num + 1;
        $number = str_replace('4', '5', $sum);
        if ($params['depart_id'] != 2) {
            $params['belong_id'] = '';
        }
        $data = [
            'number' => $number,
            'username' => $params['username'],
            'password' => md5('123456'),
            'role_name' => $params['role_name'],
            'depart_id' => $params['depart_id'],
            'mobile_phone' => $params['mobile_phone'],
            'add_time' => strtotime($params['add_time']),
            'belong_id' => $params['belong_id']
        ];
        $info = User::create($data);
        //返回数据
        return self::success($info);
    }


    /**
     * 个人信息展示
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function personCenter(Request $request)
    {
        $userInfo = User::where('id', $request->user->id)
            ->field('id,username,user_image,depart_id,role_name,email,mobile_phone')
            ->find();
        return self::success($userInfo);
    }


    /**
     * 个人修改用户信息
     * @return Json
     */
    public function update(Request $request)
    {
        $user = $request->user;
        //接收参数
        $params = $this->paramsValidate([
            'type' => 'require',
            'value' => 'require'
        ]);
        switch ($params['type']) {
            case 'user_image':
                $validate = [
                    'type|用户头像' => 'require'
                ];
                $user->user_image = $params['value'];
                break;
            case 'username':
                $validate = [
                    'type|用户名称' => 'require|length:2,12'
                ];
                $user->username = $params['value'];
                break;
            case 'mobile_phone':
                $validate = [
                    'type|手机号' => 'mobile'
                ];
                $user->mobile_phone = $params['value'];
                break;
            case 'email':
                $validate = [
                    'type|邮箱' => 'email'
                ];
                $user->email = $params['value'];
                break;
            default:
                return self::error('不支持的type');
        }
        //检测
        $message = [];
        $this->verifyData($validate, $message, ['type' => $params['value']]);
        //修改参数
        $user->save();
        return self::success($params['value']);
    }

    /**设置新密码
     * @return Json
     */
    public function setPassword()
    {
        $params = $this->paramsValidate([
            'password|原密码' => 'require',
            'newpassword|新密码' => 'require|length:3,12',
            'conpassword|确认密码' => 'require|length:3,12'
        ]);
        $user = $this->request->user;
        if ($params['newpassword'] != $params['conpassword']) {
            return self::error('新密码与确认密码不一致！');
        }
        if ($user->password != md5($params['password'])) {
            return self::error('原密码错误,请重新输入！');
        }
        $user->password = md5($params['newpassword']);
        $result = $user->save();
        return self::success($result);
    }

    /**
     * 禁用用户
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function disable(Request $request)
    {
        //接收参数删除成员id
        $params = $this->paramsValidate([
            'number' => 'require'
        ]);
        //判断是否是管理员
        if ($request->user->is_admin != 1) {
            return self::error('权限不足！');
        }
        $userInfo = User::where('number', $params['number'])->find();
        if($userInfo->is_admin==1){
            return self::error('管理员不能删除');
        }
        $userInfo->status = 2;
        $result = $userInfo->save();
        return self::success($result);
    }

    /**
     * 重置密码
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function resetPass(Request $request){
        $params=$this->paramsValidate([
            'number'=>'require'
        ]);
        //判断是否是管理员
        if ($request->user->is_admin != 1) {
            return self::error('权限不足！');
        }
        $userInfo = User::where('number', $params['number'])->find();
        $userInfo->password=md5('123456');
        $userInfo->save();
        return self::success('');
    }

    /**
     * 获取openID
     * @return Json
     */
    public function getOpenid()
    {
        $code = $this->request->post('code');
        if (empty($code)) return self::error('缺少参数');
        $app = Factory::miniProgram(config('wechat.mini_program'));
        try {
            $oauth = $app->auth->session($code);
        } catch (\Exception $e) {
            return self::error($e->getMessage());
        }
        if (empty($oauth['openid'])) {
            return self::error('发生错误，请重试！');
        }
        //用户标识
        Openid::create([
            'openid' => $oauth['openid'],
            'plan' => 'weixin',
            'session_key' => $oauth['session_key']
        ], [], true);

        return self::success(['openid' => $oauth['openid']]);
    }

    /**
     * 获取openid
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
//    public function authSession(Request $request)
//    {
//        $user = $request->user;
//        $code = $this->request->post('code');
//        if (empty($code)) return self::error('缺少参数');
//        $app = Factory::miniProgram(config('wechat.mini_program'));
//        try {
//            $oauth = $app->auth->session($code);
//        } catch (\Exception $e) {
//            return self::error($e->getMessage());
//        }
//        if (empty($oauth['openid'])) {
//            return self::error('发生错误，请重试！');
//        }
//        //用户标识
//        Openid::create([
//            'openid' => $oauth['openid'],
//            'plan' => 'weixin',
//            'session_key' => $oauth['session_key']
//        ], [], true);
//
//        //判断解绑或者绑定
//        $user->open_id = $oauth['openid'];
//        $user->save();
//        return self::success($oauth['openid']);
//    }

    /**
     * 获取用户信息
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function decryptData(Request $request)
    {
        $user = $request->user;
        $openid = $this->request->post('openid', '');
        $encryptedData = $this->request->post('encryptedData');
        $iv = $this->request->post('iv');
        if (empty($openid) || empty($encryptedData) || empty($iv)) return self::error('参数非法');
        $app = Factory::miniProgram(config('wechat.mini_program'));
        $osk = Openid::where('openid', $openid)->find();
        if (empty($osk)) return self::error('未查询到相关信息');
        try {
            $decryptedData = $app->encryptor->decryptData($osk['session_key'], $iv, $encryptedData);
        } catch (\Exception $e) {
            return self::success('解密错误');
        }
        $user->open_id = $openid;
        $user->union_id = $decryptedData['unionId'];
        $user->save();
        return self::success('');
    }

    /**
     * 解绑微信
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function unbound()
    {
        $userInfo = User::where('id', $this->request->user->id)->find();
        $userInfo->open_id = '';
        $userInfo->union_id = '';
        $userInfo->save();
        return self::success('解绑成功');
    }
}
