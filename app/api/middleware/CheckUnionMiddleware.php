<?php
declare (strict_types=1);

namespace app\api\middleware;

use app\BaseController;
use app\common\enum\HttpCode;
use app\common\model\User;
use mytools\lib\Token;
use think\Request;

/**
 * 校验token
 * Class ApiTokenMiddleware
 * @package app\middleware
 */
class CheckUnionMiddleware
{
    /**
     * 校验是否授权
     * @param Request $request
     * @param \Closure $next
     * @return mixed|\think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function handle(Request $request, \Closure $next)
    {
        //建表后取消注释
        $param = $request->param();
        if (empty($param['token'])) {
            return BaseController::error('请先登录！', HttpCode::RETURN_LOGIN);
        }
        //校验token
        $token = Token::read($param['token']);
        $userInfo = User::where('id', $token['uid'])->find();
        if (empty($userInfo['union_id'])) {
            return BaseController::error('请先授权！', HttpCode::USER_AUTH);
        }
//        if($param['openid']!=$userInfo->open_id){
//            return BaseController::error('该账号已被使用！',HttpCode::RETURN_LOGIN);
//        }
        return $next($request);
    }
}
