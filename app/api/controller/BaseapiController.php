<?php
declare (strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\enum\HttpCode;

class BaseapiController extends BaseController
{

    /**
     * 返回json数据
     * @param mixed $data 响应数据
     * @param string $msg 错误消息
     * @param int $code 响应码
     * @param int $status_code 响应状态码
     * @param array $header 响应头
     * @return \think\response\Json
     */
    protected static function returnJson($data, $msg, int $code, int $status_code, array $header = [])
    {
        return json(['data' => $data, 'msg' => $msg, 'code' => $code])->code($status_code)->header($header);
    }

    /**
     * 返回成功json
     * @param $data
     * @param int $code
     * @param int $status_code
     * @param array $header 响应头
     * @return \think\response\Json
     */
    public static function success($data, int $code = HttpCode::SUCCESS, int $status_code = 200, array $header = [])
    {
        return self::returnJson($data, '', $code, $status_code, $header);
    }

    /**
     * 返回错误json
     * @param string $msg 错误消息
     * @param int $code
     * @param int $status_code
     * @param array $header 响应头
     * @return \think\response\Json
     */
    public static function error(string $msg, int $code = HttpCode::ERROR, int $status_code = 200, array $header = [])
    {
        return self::returnJson(null, $msg, $code, $status_code, $header);
    }


}
