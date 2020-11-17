<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\api\controller\WechatController;
use app\BaseController;
use app\common\model\Applets;
use app\common\model\Apply;
use app\common\model\ApplyRecord;
use app\common\model\ApplySetup;
use app\common\model\Constraints;
use app\common\model\Send;
use app\common\model\User;
use app\common\service\NewsService;
use app\common\service\ProjectService;
use app\common\service\SendMessageService;
use app\common\service\UserService;
use mytools\lib\Token;
use think\Request;

class IndexController extends BaseController
{
    public function index(){
        //$re=(new WechatController())->getUserList();
        //发送微信模板消息
//        $apicontent = [
//            'apply_id'=>10,
//            'title'=>'你有一个申请待处理',
//            'ty'=>2,
//            'status'=>2,
//            'type' => '请假申请',
//            'user' => '张三',
//            'time' => time(),
//        ];
//        $content=[
//            'apply_id'=>,
//            'title'=>'你有一个申请已被处理',
//            'ty'=>3,
//            'apply_user'=>'张三',
//            'start_time'=>11111,
//            'end_time'=>22222,
//            'reason'=>'hhhhh',
//            'status'=>'通过'
//        ];
//        $content=[
//            'cost_id'=>4,
//            'title'=>'有一条费用申请已被处理',
//            'ty'=>2,
//            'user' =>'kkkk',
//            'status' =>'通过',
//            'time' =>time(),
////        ];
//        $status=Constraints::where('id',100)->value('process_name');
//        $content=[
//            'project_id'=>100060,
//            'number'=>14562668562,
//            'name'=>'测试项目',
//            'status'=>$status
//        ];
//        ProjectService::projectMsg(2000,$content);
        //(new SendMessageService())->applyNotify(UserService::getOpenid(1042), $apicontent);
        //ProjectService::costMsg(1043,$content);
        //ProjectService::costResultMsg(1043,$content);
        //return '111';
        $data=Token::make(1024,1);
        dump($data);die;
//        $arr = [ 2 => 'xsb_id', 3 => 'jsb_id', 4 => 'swb_id', 5 => 'gcb_id', 6 => 'cwb_id'];
//        $projectInfo['xsb_id']=999;
//        if (empty($projectInfo[$arr[2]])) {
//            $person_id = 1;
//        } else {
//            $person_id = $projectInfo[$arr[2]];
//        }
//        dump($person_id);die;
//        $pa=Constraints::order('id','desc')->find();
//        dump($pa['id']);die;
//        $array=[];
//        foreach ($pa as $k=>$v){
//            $array[]=$v;
//        }
//        $params['auth']=array_unique($array);
//        dump($params);die;
    }
}
