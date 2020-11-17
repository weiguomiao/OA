<?php
declare (strict_types=1);

namespace app;

use app\common\enum\HttpCode;
use think\App;
use think\exception\ValidateException;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 验证数据
     * @access protected
     * @param  array $data 数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array $message 提示信息
     * @param  bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        $v->failException(true)->check($data);
        return $data;
    }


    /**
     * @param $validate
     * @param array $message
     * @param bool $batch 是否批量验证
     * @param string $method
     * @return array|string|true
     */
    protected function paramsValidate($validate, array $message = [], bool $batch = false, string $method = 'param')
    {
        try {
            // 只取验证器中有的数据
            $keys = array_keys($validate);
            $keys = array_map(function ($v) {
                if (is_string($v))
                    return explode('|', $v)[0];
            }, $keys);
            $data = $this->request->$method($keys);
            // 去除验证器中空的验证值
            $validate = array_diff($validate, ['']);
            return $this->validate($data, $validate, $message, $batch);
        } catch (ValidateException $e) {
            exit(json_encode(['code' => 0, 'msg' => $e->getMessage()]));
        }
    }

    /**
     * 自定义验证数据
     * @param $validate //验证规则
     * @param $msg    //提示信息
     * @param $data   //验证数据
     * @return bool
     */
    public function verifyData($validate, $msg, $data)
    {
        $vali = new Validate();
        $res=$vali->message($msg)->check($data,$validate);
        if($res!==true){
            exit(json_encode(['code' => 0, 'msg' =>$vali->getError()]));
        }
        return $res;
    }

    /**
     * 验证get请求参数
     * @param array|string $validate 验证数组或者验证器对象
     * @param array $message 错误消息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     */
    protected function getValidate($validate, array $message = [], bool $batch = false)
    {
        return $this->paramsValidate($validate, $message, $batch, 'get');
    }

    /**
     * 验证post请求参数
     * @param array|string $validate 验证数组或者验证器对象
     * @param array $message 错误消息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     */
    protected function postValidate($validate, array $message = [], bool $batch = false)
    {
        return $this->paramsValidate($validate, $message, $batch, 'post');
    }

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
     * @param array $header
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

    /**
     * 重定向跳转
     * @param string $path
     * @param array $vars
     */
    public function redirect(string $path, $vars = [])
    {
        $url = (string)url($path, $vars);
        return header('location:' . $url);
    }

}
