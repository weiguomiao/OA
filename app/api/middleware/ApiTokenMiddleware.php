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
class ApiTokenMiddleware
{
    /**
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
        if ($token['typ'] != Token::TYPE_USER) {
            return BaseController::error('身份错误！', HttpCode::RETURN_LOGIN);
        }

        // 自动续期
        if ($token['gqt'] - time() < 86400) {
            response()->header([
                'Access-Control-Expose-Headers' => 'token',
                'token' => Token::make((int)$token['uid'], Token::TYPE_USER)
            ]);
        }
        $userInfo = User::where('id', $token['uid'])->find();
        if (empty($userInfo) || $userInfo['status'] != 1) {
            return BaseController::error('用户异常！', HttpCode::RETURN_LOGIN);
        }
        $request->user_id = $token['uid'];
        $request->user = $userInfo;
        session('identity', $userInfo['is_admin']);
        return $next($request);
    }
}
