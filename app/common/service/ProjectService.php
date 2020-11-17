<?php


namespace app\common\service;

/**
 * 发送公众号消息
 * Class ProjectService
 * @package app\common\service
 */
class ProjectService
{
    /**
     * 给公众号发送项目消息
     * @param $uid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function projectMsg($uid,$content){
        $openid=UserService::getOpenid($uid);
        if(!empty($openid)){
            (new SendMessageService())->projectNotify($openid,$content);
        }
    }

    /**
     * 给公众号发送请假出差审批消息
     * @param $uid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function applyMsg($uid,$content){
        $openid=UserService::getOpenid($uid);
        if(!empty($openid)){
            (new SendMessageService())->applyNotify($openid,$content);
        }
    }

    /**
     * 给公众号发送请假出差审批结果消息
     * @param $uid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function applyResultMsg($uid,$content){
        $openid=UserService::getOpenid($uid);
        if(!empty($openid)){
            (new SendMessageService())->applyResultNotify($openid,$content);
        }
    }

    /**
     * 给公众号发送费用审批消息
     * @param $uid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function costMsg($uid,$content){
        $openid=UserService::getOpenid($uid);
        if(!empty($openid)){
            (new SendMessageService())->costNotify($openid,$content);
        }
    }

    /**
     * 给公众号发送费用审批结果消息
     * @param $uid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function costResultMsg($uid,$content){
        $openid=UserService::getOpenid($uid);
        if(!empty($openid)){
            (new SendMessageService())->costResultNotify($openid,$content);
        }
    }
}