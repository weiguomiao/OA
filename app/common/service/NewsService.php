<?php
declare (strict_types=1);

namespace app\common\service;

use app\common\model\News;
use app\common\model\UserNews;

class NewsService
{

    /**
     * 发送内部消息
     * @param $uid -发送者ID
     * @param $title -标题
     * @param $content -内容
     * @param $type -类型
     * @param $do_user -接收者ID
     * @param bool $extend -扩展
     */
    public static function sendMsg($uid, $title, $content, $type, $do_user, $extend = false)
    {
        //添加消息记录
        $new = News::create([
            'user_id' => $uid,
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'extend' => $extend
        ]);
        //添加阅读记录
        if (is_array($do_user)) {
            $arr = [];
            foreach ($do_user as $v) {
                $arr[] = [
                    'news_id' => $new->id,
                    'user_id' => $v
                ];
            }
            (new UserNews)->saveAll($arr);
        } else {
            UserNews::create([
                'news_id' => $new->id,
                'user_id' => $do_user
            ]);
        }

    }
}
