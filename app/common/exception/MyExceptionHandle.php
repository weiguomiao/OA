<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/10/25
 * Time: 11:34
 */

namespace app\common\exception;

use app\BaseController;
use think\exception\Handle;
use think\Response;

/**
 * 自定义全局异常捕获
 * Class MyExceptionHandle
 * @package app\common\exception
 */
class MyExceptionHandle extends Handle
{

    public function render($request, \Throwable $e): Response
    {
        // 参数验证错误
        if ($e instanceof AppRuntimeException) {
            return BaseController::error($e->getMessage(),$e->getCode());
        }
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}