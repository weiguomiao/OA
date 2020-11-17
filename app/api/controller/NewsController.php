<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Department;
use app\common\model\News;
use app\common\model\Project;
use app\common\model\User;
use app\common\model\UserNews;

class NewsController extends BaseapiController
{
    /**
     * 查看消息列表
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function looknews()
    {
        $params=$this->paramsValidate([
            'status'=>'require|in:1,2'
        ]);
        switch ($params['status']){
            case 1:
                if (session('identity') == 1) {
                    //查询所有新闻
                    $list = News::order('create_time', 'desc')
                        ->where('user_id', $this->request->user->id)
                        ->where('type','3')
                        ->field('id,title,content,create_time')
                        ->append(['amount'])
                        ->paginate(10);
                } else {
                    return self::error('权限不足！');
                }
                break;
            case 2:
                //用户查询消息
                $list = (new UserNews)->with(['News'])
                    ->order('create_time', 'desc')
                    ->where('user_id', $this->request->user->id)
                    ->paginate(10);
                break;
            default:
                return self::error('参数错误!');
        }
        return self::success($list);
    }

    /**
     * 管理员发送消息
     *
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addNews()
    {
        //接收参数
        $params = $this->paramsValidate([
            'id'=>'',
            'title|标题' => 'require|max:1000',
            'content|内容' => 'require|max:5000',
            'user_id' => 'require'
        ]);
        if (session('identity') != 1) {
            return self::error('权限不足！');
        }

        $lastNew=News::where('type','3')->order('id','desc')->find();
        if(time()-(int)strtotime($lastNew['create_time'])<20)return;

        if(empty($params['id'])){
            $news = [
                'title' => $params['title'],
                'content' => $params['content'],
                'user_id' => $this->request->user->id,
                'type'=>3
            ];
            $newsInfo = News::create($news);

        }else{
            $news = [
                'id'=>$params['id'],
                'title' => $params['title'],
                'content' => $params['content'],
                'user_id' => $this->request->user->id,
                'type'=>3
            ];
            $del=UserNews::where('news_id',$params['id'])->delete();
            if(empty($del)) return self::error('删除不成功！');
            $newsInfo = News::update($news);
        }
        //指定人员发送
        $new = [];
        foreach ($params['user_id'] as $k => $v) {
            $data = [
                'news_id' => $newsInfo['id'],
                'user_id' => $v
            ];
            $new[] = $data;
        }
        //返回数据
        $res = new UserNews();
        $res->saveAll($new);
        return self::success('消息发布成功！');
    }


    /**
     * 查看新闻详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail()
    {
        $params = $this->paramsValidate([
            'id' => 'require',
            'status'=>'require|in:1,2',  //1是消息管理详情，2是用户消息详情
            'read'=>'in:1,2'
        ]);
        switch ($params['status']){
            case 1:
                //查询消息详情
                if (session('identity') == 1) {
                    $detail = News::where('id', $params['id'])
                        ->where('user_id',$this->request->user->id)
                        ->append(['amount'])
                        ->find();
                    //已读消息成员
                    if(empty($params['read'])) return self::error('read不能为空！');
                    $looked = UserNews::where('news_id', $params['id'])
                        ->where('status', $params['read'])
                        ->column('user_id');
                    $look = [];
                    foreach ($looked as $k => $v) {
                        $user = User::find($v);
                        $look[$k]['username'] = $user['username'];
                        $look[$k]['user_image'] = $user['user_image'];
                    }
                } else {
                    return self::error('权限不足！');
                }
                break;
            case 2:
                //查询用户消息
                $userNews = UserNews::where('id', $params['id'])
                    ->where('user_id',$this->request->user->id)->find();
//                if(empty($userNews)) return self::error('');
                $userNews->status = 1;
                $userNews->save();
                //查询消息详情
                $detail = News::where('id', $userNews['news_id'])->find();
//                if(empty($detail)) return self::error('');
                if($detail['type']==1){
                    $projectInfo=Project::where('id',$detail['extend'])->find();
                    if(empty($projectInfo)){
                        return self::error('该项目已删除！');
                    }
                }
                $look=[];
                break;
            default:
                return self::error('参数错误！');
        }
        $viewData = [
            'detail' => $detail,
            'look' => $look,
        ];
        return self::success($viewData);

    }


    /**
     * 删除消息
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delNews()
    {
        //接收参数
        $params = $this->paramsValidate([
            'id' => 'require'
        ]);
        if (session('identity') != 1) {
            return self::error('权限不足！');
        }
        $newsInfo = (new News())->with(['UserNews'])
            ->where('user_id', $this->request->user->id)
            ->where('id', $params['id'])
            ->find();
        $result = $newsInfo->together(['UserNews'])->delete();
        return self::success($result);
    }

    /**选择部门用户列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function departUser(){
        $depart=(new Department())->field('id,depart_name')->select();
        foreach ($depart as $k1=>$v1){
            $user=User::where('depart_id',$v1['id'])->select();
            $userInfo=[];
            foreach ($user as $k2=>$v2){
                $userInfo[$k2]['user_id']=$v2['id'];
                $userInfo[$k2]['username']=$v2['username'];
                $userInfo[$k2]['user_image']=$v2['user_image'];
            }
            $depart[$k1]['userList']=$userInfo;
        }
        $viewData=[
            'depart'=>$depart
        ];
        return self::success($viewData);
    }

}
