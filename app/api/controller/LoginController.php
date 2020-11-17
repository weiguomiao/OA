<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\User;
use mytools\lib\Token;

class LoginController extends BaseapiController
{
    //用户登录
    public function login()
    {
        $params = $this->paramsValidate([
            'number|工号' => 'require',
            'password|密码' => 'require',
            'open_id'=>'require'
        ]);
        $userInfo = User::where('number', $params['number'])->find();
        if (empty($userInfo)) return self::error('工号不存在');
        $params['password'] = md5($params['password']);
        if ($userInfo['password'] != $params['password']) return self::error('密码错误！');
        if($userInfo['status']==2) return self::error('该账号已被禁用！');
        if(!empty($userInfo->open_id)&&$userInfo->open_id!=$params['open_id']){
            return self::error('该账号已被绑定');
        }
        $data['token'] = Token::make($userInfo['id'], 1);
        return self::success($data);
    }
}
