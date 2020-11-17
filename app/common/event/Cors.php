<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/28
 * Time: 17:56
 */

namespace app\common\event;

class Cors
{
    public function handle()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: POST,GET');
        if (request()->isOptions()) {
            exit();
        }
    }
}
