<?php


namespace app\common\enum;

/**
 * http响应码枚举类
 * Class HttpCode
 * @package app\common\enum
 */
class HttpCode
{
    // 成功
    const SUCCESS = 1;

    // 失败
    const ERROR = 0;

    // 返回上一页
    const RETURN_LAST = 1003;

    // 返回登录页
    const RETURN_LOGIN = 1002;

    //用户授权
    const USER_AUTH=1004;
}