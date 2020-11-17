<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Constraints;
use app\common\model\Project;
use app\common\model\Menu;
use app\common\model\UserNews;
use mytools\resourcesave\ResourceManager;

class IndexController extends BaseapiController
{
    /**
     * 首页
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function index()
    {
        //查询用户信息
        $user = $this->request->user;
        //查询最后一条流程约束记录
        $conInfo=Constraints::order('id','desc')->find();
        //项目进展
        $info = Project::where('id', '>', 0)
            ->field('id,project_name,status,desc,create_time,user_id,person_id,auth')
            ->append(['userInfo', 'userName', 'statusText'])
            ->where('status','<',$conInfo['id'])
            ->order('create_time', 'desc')
            ->paginate(5)
            ->toArray();
        if ($user['is_admin'] == 1) {
            $w[] = ['type', 'in', [1, 2, 3]];
        } elseif ($user->getData('depart_id') == 2) {
            $w[] = ['type', 'in', [1, 2]];
        } else {
            $w[] = ['type', '=', 1];
        }
        $menu = Menu::where($w)->select();
        $doing=Project::where('status','<',$conInfo['id'])->count();
        $news=UserNews::where('user_id',$user->id)->append(['title'])->order('id','desc')->find();
        if(empty($news)){
            $news['title']='暂无消息';
        }
        //定义模板变量
        $dataView = [
            'user' => [
                'is_admin' => $user['is_admin']
            ],
            'menu' => $menu,
            'projectInfo' => $info,
            'doing'=>$doing,
            'news'=>$news
        ];
        //返回数据
        return self::success($dataView);
    }

    /**
     * 文件异步上传
     * @return \think\response\Json
     */
    public function uploadFile()
    {
        return self::success(ResourceManager::staticResource(ResourceManager::saveBase64('file', 'file')));
    }
}
