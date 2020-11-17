<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/10/25
 * Time: 9:33
 */
declare (strict_types = 1);

namespace app\common\exception;


use think\facade\Db;

/**
 * 运行时异常
 * Class RuntimeException
 * @package app\common
 */
class AppRuntimeException extends \Exception
{
    /**
     * AppRuntimeException constructor.
     * @param string $message
     * @param array|null $save
     * @param int $code
     * @param \Throwable|null $previous
     * @throws \think\db\exception\DbException
     */
    public function __construct(string $message, array $save = null, int $code = 0, \Throwable $previous = null)
    {
        // 是否保存异常记录
        $error_id = is_null($save) ? 0 : $this->save($message, $code, $save);

        parent::__construct(
            $message
            . ($error_id ? ('<b>error_code:' . $error_id . '</b>') : ''),
            $code,
            $previous
        );
    }

    /**
     * 保存异常记录
     * @param string $msg
     * @param int $code
     * @param array $data
     * @return int 异常记录ID
     * @throws \think\db\exception\DbException
     */
    private function save(string $msg, int $code, array $data) : int
    {
        $request_params = request()->param();
        // 获取数据表
        $db = Db::name('error_log');
        // 如果数据表数据大于5000,则删除前三千条记录
        if($db->count('*') >= 5000) {
            $db->order('id asc')->limit(3000)->delete(true);
        }
        $error_data = [
            'code'          => $code,
            'file'          => $this->getFile(),
            'msg'           => $msg,
            'line'          => $this->getLine(),
            'params'        => empty($request_params) ? '' : json_encode($request_params, JSON_UNESCAPED_UNICODE),
            'extend'        => empty($data) ? '' : json_encode($data, JSON_UNESCAPED_UNICODE),
            'create_time'   => time()
        ];
        // 保存错误信息
        return (int)$db->insertGetId($error_data);
    }
}