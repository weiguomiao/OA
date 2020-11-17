<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

// 关注公众号回调
Route::rule('msgServer', 'wechat/msgServer');
//登录
Route::rule('login', 'login/login');
//getOpenid
Route::rule('getOpenid', 'user/getOpenid');
Route::rule('authSession', 'user/authSession')->middleware(\app\api\middleware\ApiTokenMiddleware::class);
Route::rule('decryptData', 'user/decryptData')->middleware(\app\api\middleware\ApiTokenMiddleware::class);

Route::group('', function () {
    //首页接口
    Route::rule('home', 'index/index');
    //文件上传
    Route::rule('uploadFile', 'index/uploadFile');

    //用户管理接口
    Route::rule('user/index', 'user/index');
    Route::rule('user/save', 'user/save');
    Route::rule('user/update', 'user/update');
    Route::rule('disable', 'user/disable');
    Route::rule('person', 'user/personCenter');
    Route::rule('setPassword', 'user/setPassword');
    Route::rule('unbound','user/unbound');
    Route::rule('resetPass','user/resetPass');
    //项目管理接口
    Route::rule('projectProcess', 'project/projectProcess');
    Route::rule('addProject', 'project/addProject');
    Route::rule('delProject', 'project/delProject');
    Route::rule('projectHandle', 'project/projectHandle');
    Route::rule('projectInfo', 'project/projectInfo');

    //部门管理
    Route::rule('department', 'depart/index');
    Route::rule('departMember', 'depart/departMember');
    Route::rule('userInfo', 'depart/userInfo');
    Route::rule('updateMember', 'depart/updateMember');
    Route::rule('addDepart', 'depart/addDepart');
    Route::rule('delDepart', 'depart/delDepart');
    //进度管理
    Route::rule('process', 'process/process');
    Route::rule('setStatus', 'process/setStatus');
    Route::rule('remind', 'process/remind');
    Route::rule('addInfo', 'process/addInfo');

    //消息管理
    Route::rule('looknews', 'news/looknews');
    Route::rule('addNews', 'news/addNews');
    Route::rule('detail', 'news/detail');
    Route::rule('updateNews', 'news/updateNews');
    Route::rule('delNews', 'news/delNews');
    Route::rule('departUser', 'news/departUser');

    //费用审批
    Route::rule('costList', 'cost/costList');
    Route::rule('applyCost', 'cost/applyCost');
    Route::rule('costInfo', 'cost/costInfo');
    Route::rule('applyDel', 'cost/applyDel');
    Route::rule('applyProcess', 'cost/applyProcess');
    Route::rule('costStatus', 'cost/costStatus');

    //考勤打卡
    Route::rule('checkDaka', 'daka/checkDaka');
    //打卡页面数据
    Route::rule('daKaPage', 'daka/daKaPage');
    //打卡设置
    Route::rule('setTime', 'daka/setTime');
    //设置页面数据
    Route::rule('setTimePage', 'daka/setTimePage');
    //管理员统计
    Route::rule('adminCount', 'daka/adminCount');
    //导出
    Route::rule('export', 'daka/export');
    //我的统计
    Route::rule('myCount', 'daka/myCount');

    //审批列表
    Route::rule('applyList', 'apply/index');
    //申请
    Route::rule('addApply', 'apply/addApply');
    Route::rule('updateApply', 'apply/updateApply');
    Route::rule('delApply', 'apply/delApply');
    Route::rule('display', 'apply/display');
    Route::rule('applyStatus', 'apply/applyStatus');

    // 流程控制
    Route::rule('applyStep', 'apply/applyStep');
    Route::rule('stepInfo', 'apply/stepInfo');

})->middleware([\app\api\middleware\ApiTokenMiddleware::class, \app\api\middleware\CheckUnionMiddleware::class]);
