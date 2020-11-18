<?php

namespace app\common\service;

use EasyWeChat\Factory;
use think\facade\Log;

class SendMessageService
{
    protected $app;
    protected $appId = '';

    public function __construct()
    {
        $this->app = Factory::officialAccount(config('wechat.official_account'));
    }

    /**
     * 审批通知
     * @param $openid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function applyNotify($openid, $content)
    {
        $data = [
            'touser' => $openid,//openID
            'template_id' => '',//模板ID
            //'url' => 'https://easywechat.org',//跳转网址
            'miniprogram' => [//跳转小程序参数
                'appid' => $this->appId,
                'pagepath' => 'pages/my_Approval/approval_save?id=' . $content['apply_id'] . "&type=".$content['ty'],
            ],
            'data' => [
                'first' => $content['title'],
                'keyword1' => $content['type'],
                'keyword2' => $content['user'],
                'keyword3' => date('Y年m月d日 H:i', $content['time']),
                'remark' => '点击进入小程序查看详细信息。'
            ],
        ];
        $this->app->template_message->send($data);
    }

    /**
     * 审批结果通知
     * @param $openid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function applyResultNotify($openid, $content)
    {
        $data = [
            'touser' => $openid,//openID
            'template_id' => '',//模板ID
            //'url' => 'https://easywechat.org',//跳转网址
            'miniprogram' => [//跳转小程序参数
                'appid' => $this->appId,
                'pagepath' => 'pages/my_Approval/approval_save?id='.$content['apply_id']."&type=".$content['ty'],
            ],
            'data' => [
                'first' => $content['title'],
                'keyword1' => $content['apply_user'],//审批人
                'keyword2' => date('Y年m月d日 H:i', $content['start_time']),
                'keyword3' => date('Y年m月d日 H:i', $content['end_time']),
                'keyword4' => $content['reason'],
                'keyword5' => $content['status'],
                'remark' => '点击进入小程序查看详细信息。'
            ],
        ];
        $this->app->template_message->send($data);
    }


    /**
     * 项目进度提醒
     * @param $openid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function projectNotify($openid, $content)
    {
        $data = [
            'touser' => $openid,//openID
            'template_id' => '',//模板ID
            //'url' => 'https://easywechat.org',//跳转网址
            'miniprogram' => [//跳转小程序参数
                'appid' => $this->appId,
                'pagepath' => 'pages/progress/progress_article?id='.$content['project_id'],
            ],
            'data' => [
                'first' => '项目进度',
                'keyword1' => $content['number'],
                'keyword2' => $content['name'],
                'keyword3' => $content['status'],
                'remark' => "点击进入小程序查看详细信息"
            ],
        ];
        $this->app->template_message->send($data);
    }

    /**
     * 费用申请结果提醒
     * @param $openid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function costResultNotify($openid, $content)
    {
        $data = [
            'touser' => $openid,//openID
            'template_id' => '',//模板ID
            //'url' => 'https://easywechat.org',//跳转网址
            'miniprogram' => [//跳转小程序参数
                'appid' => $this->appId,
                'pagepath' => 'pages/money/money_article?id='.$content['cost_id']."&type=".$content['ty'],
            ],
            'data' => [
                'first' => $content['title'],
                'keyword1' => $content['user'],
                'keyword2' => $content['status'],
                'keyword3' => date('Y年m月d日 H:i', $content['time']),
                'remark' => "点击进入小程序查看详细信息。"
            ],
        ];
        $this->app->template_message->send($data);
    }

    /**
     *费用申请待处理通知
     * @param $openid
     * @param $content
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function costNotify($openid, $content)
    {
        $data = [
            'touser' => $openid,//openID
            'template_id' => '',//模板ID
            //'url' => 'https://easywechat.org',//跳转网址
            'miniprogram' => [//跳转小程序参数
                'appid' => $this->appId,
                'pagepath' => 'pages/money/money_article?id='.$content['cost_id']."&type=".$content['ty'],
            ],
            'data' => [
                'first' => $content['title'],
                'keyword1' => $content['user'],
                'keyword2' => date('Y年m月d日 H:i', $content['time']),
                'keyword3' => $content['money'] . '元',
                'remark' => "点击进入小程序查看详细信息。"
            ],
        ];
        $this->app->template_message->send($data);
    }
}
