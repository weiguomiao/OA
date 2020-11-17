<?php


namespace app\common\service;


use app\common\model\Applets;
use app\common\model\User;

/**
 * 获取用户公众号openid
 * Class UserService
 * @package app\common\service
 */
class UserService
{
    public static function getOpenid($uid){
        $union=User::find($uid)['union_id'];
        $applets=Applets::where('union_id',$union)->find();
        if($applets['is_event']==1){
            return $applets->open_id;
        }else{
            return '';
        }
    }

}