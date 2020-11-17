<?php
declare (strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\exception\AppRuntimeException;
use app\common\model\Applets;
use app\common\model\User;
use EasyWeChat\Factory;
use mytools\resourcesave\ResourceManager;
use think\facade\Log;

class WechatController
{
    protected $app;

    public function __construct()
    {
        $this->app = Factory::officialAccount(config('wechat.official_account'));
    }

    public function getUserList()
    {
        $re = $this->app->user->list();

        $users = $this->app->user->select($re['data']['openid']);
        return $users;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \ReflectionException
     */
    public function msgServer()
    {
        try {
            $this->app->server->push(function ($message) {
                Log::record($message, 'error');
                switch ($message['MsgType']) {
                    case 'event':
                        // openid
                        $openid = $message['FromUserName'];
                        $type = $message['Event'];
                        // 获取unionid
                        $user = $this->app->user->get($openid);

                        switch ($type){
                            case "subscribe":// 订阅
                                // 判断在表里是否存在，存在写入用户信息，不存在则注册用户
                                $u = Applets::where('open_id', $openid)->find();
                                if (empty($u)) {
                                    Applets::create([
                                        'open_id' => $openid,
                                        'union_id' => $user['unionid'],
                                    ]);
                                }else{
                                    $u->is_event=1;
                                    $u->save();
                                }

                                break;
                            case "unsubscribe":// 删除openid
                                Applets::where('open_id', $openid)->update(['is_event' => 2]);
                                break;
                            case "CLICK":
                                switch ($message['EventKey']){
                                    case 'V1001_TODAY_MUSIC':
                                       return '你点击了菜单KEY:V1001_TODAY_MUSIC';
                                        break;

                                }
                                break;
                        }

                        return '您好，欢迎关注汉儒OA官方微信公众号';
                        break;
                    case 'text':

                        return '我没听清你说啥，大声点^_^';
                        break;
                    case 'image':
                        return '收到图片消息';
                        break;
                    case 'voice':
                        return '收到语音消息';
                        break;
                    case 'video':
                        return '收到视频消息';
                        break;
                    case 'location':
                        return '收到坐标消息';
                        break;
                    case 'link':
                        return '收到链接消息';
                        break;
                    case 'file':
                        return '收到文件消息';
                    // ... 其它消息
                    default:
                        return '收到其它消息';
                        break;
                }
            });
        } catch (\Exception $e) {
        }

        // 在 laravel 中：
        $response = $this->app->server->serve();
        // $response 为 `Symfony\Component\HttpFoundation\Response` 实例
        // 对于需要直接输出响应的框架，或者原生 PHP 环境下
        $response->send();
        // 而 laravel 中直接返回即可：
        return $response;
    }

    public function setMenu()
    {
        $buttons = [
            [
                "type" => "click",
                "name" => "今日歌曲",
                "key"  => "V1001_TODAY_MUSIC"
            ],
            [
                "name"       => "菜单",
                "sub_button" => [
                    [
                        "type" => "view",
                        "name" => "搜索",
                        "url"  => "http://www.soso.com/"
                    ],
                    [
                        "type" => "view",
                        "name" => "视频",
                        "url"  => "http://v.qq.com/"
                    ],
                    [
                        "type" => "click",
                        "name" => "赞一下我们",
                        "key" => "V1001_GOOD"
                    ],
                ],
            ],
        ];
        $this->app->menu->create($buttons);
    }
    
}
