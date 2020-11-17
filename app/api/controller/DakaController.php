<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Apply;
use app\common\model\Daka;
use app\common\model\DakaTime;
use app\common\model\User;
use app\common\model\UserCount;
use app\common\service\CountService;
use app\Request;
use mytools\office\MyExcel;
use think\response\Json;

class DakaController extends BaseapiController
{
    /**
     * 用户打卡
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkDaka(Request $request)
    {
        $user = $request->user;
        $set = DakaTime::find(1)->toArray();
        $time = strtotime(date('Y-m-d ') . $set['start_time']);
        $params = $this->paramsValidate([
            'lat|纬度' => 'require',
            'lng|经度' => 'require',
            'remark|备注' => '',
            'image|拍照图片' => 'require',
            'address|打卡地址' => 'require'
        ]);
        $check = Daka::where('user_id', $user->id)
            ->where('create_time', '>', strtotime(date('Y-m-d')))
            ->find();
        if (time() > $time && empty($check)) {
            $params['is_late'] = 1;
            CountService::userCount($user->id, $user->getData('depart_id'), 3, 1);
        } else {
            $params['is_late'] = 2;
        }
        $params['user_id'] = $request->user->id;
        Daka::create($params);
        return self::success('成功');
    }

    /**
     * 打卡页面
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function daKaPage(Request $request)
    {
        $user = $request->user;
        $set = DakaTime::find(1)->toArray();
        $data['info'] = [
            'user_image' => $user->user_image,
            'username' => $user->username,
            'start_time' => $set['start_time'],
            'end_time' => $set['end_time'],
        ];
        $day = strtotime(date('Y-m-d'));
        $data['list'] = Daka::where('user_id', $user->id)
            ->where('create_time', 'between', [$day, $day + 86399])
            ->withAttr('create_time', function ($v) {
                return date('H:i', $v);
            })
            ->order('create_time', 'desc')
            ->select();

        return self::success($data);
    }

    /**
     * 管理员统计
     * @throws \think\db\exception\DbException
     */
    public function adminCount(Request $request)
    {
        $params = $this->paramsValidate([
            'month|月份' => 'require',
            'depart_id|部门ID' => 'require',
        ]);
        if ($request->user->is_admin != 1) {
            return self::error('权限不足');
        }
        $leave = self::DaKaCount($params, 'leave', 1);
        $travel = self::DaKaCount($params, 'travel', 1);
        $late = self::DaKaCount($params, 'late', 1);
        $arr = UserCount::where('depart_id', $params['depart_id'])
            ->where('time', $params['month'])
            ->column('user_id');
        $list = User::where('id', 'in', $arr)
            ->field('id,number,user_image,depart_id,username')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['late'] = self::DaKaCount($params, 'late', 2, $v['id']);
            $list[$k]['leave'] = self::DaKaCount($params, 'leave', 2, $v['id']);
            $list[$k]['travel'] = self::DaKaCount($params, 'travel', 2, $v['id']);
        }
        return self::success(compact('leave', 'travel', 'late', 'list'));
    }

    /**
     *我的统计
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function myCount(Request $request)
    {
        $params = $this->paramsValidate([
            'time|日期' => 'require',
        ]);
        $count = UserCount::where('user_id', $request->user->id)
            ->where('time', '=', date('Y-m'))
            ->field('late,leave,travel,day')
            ->find();
        $list = Daka::where('user_id', $request->user->id)
            ->whereBetweenTime('create_time', strtotime($params['time']), strtotime($params['time']) + 86399)
            ->withAttr('create_time', function ($v) {
                return date('H:i', $v);
            })->field('address,image,create_time,is_late,remark')->select();
        return self::success(compact('count', 'list'));
    }

    /**
     * 统计
     * @param $data -数据
     * @param $field -统计字段
     * @param $type -1总计 2个人统计
     * @param $uid
     * @return int
     */
    public static function DaKaCount($data, $field, $type, $uid = false)
    {
        $w[] = ['time', '=', $data['month']];
        if ($type == 1) {
            $w[] = ['depart_id', '=', $data['depart_id']];
            $w[] = [$field, '>', 0];
            return UserCount::where($w)->count();
        } else {
            $w[] = ['user_id', '=', $uid];
            return UserCount::where($w)->value($field) ?? 0;
        }

    }

    /**
     * 打卡设置页面
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setTimePage(Request $request)
    {
        if ($request->user->is_admin != 1) {
            return self::error('权限不足');
        }
        $data = DakaTime::find(1);
        return self::success($data);
    }

    /**设置打卡时间
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setTime(Request $request)
    {
        if ($request->user->is_admin != 1) {
            return self::error('权限不足');
        }
        //接收参数
        $params = $this->paramsValidate([
            'id|ID' => 'number',
            'start_time|上班时间' => 'require',
            'end_time|下班时间' => 'require',
            'address|地址' => 'require',
            'lat|纬度' => 'require',
            'lng|经度' => 'require',
        ]);
        DakaTime::update($params);
        return self::success('设置成功');
    }

    /**
     * 导出excel表
     * @return Json
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function export()
    {
        $params=$this->paramsValidate([
            'month'=>'require',
            'depart_id'=>'require'
        ]);
        $ym=explode('-',$params['month']);
        $days = cal_days_in_month(CAL_GREGORIAN,(int)$ym[1],(int)$ym[0]);
        $start_time=$params['month'].'-'.'01';
        $end_time=$params['month'].'-'.$days;
        $user_id=User::where('depart_id',$params['depart_id'])->column('id');
        $daka= Daka::whereTime('create_time','between',[$start_time,$end_time])
            ->field('user_id,address,create_time,is_late')
            ->where('user_id','in',$user_id)
            ->append(['userInfo'])
            ->select()
            ->toArray();
        $data=[];
        foreach ($daka as $k=>$v){
            $late=$v['is_late']==1 ? '迟到':'';
            $data[]=[
                'username'=>$v['userInfo']['username'],
                'time'=>$v['create_time'],
                'depart_id'=>$v['userInfo']['depart_id'],
                'address'=>$v['address'],
                'late'=>$late
            ];
        }
        $arr = [];
        foreach ($data as $k => $v) {
            $arr[] = array_values($v);
        }
        $excel = [
            'save_name' => date('Y-m-d') . '考勤记录',
            'table' => [
                // 表格1
                'sheet1' => [
                    // 工作表标题
                    'title' => date('Y-m-d') . '考勤记录',
                    // 表格标题
                    'table_captain' => date('Y-m-d') . '考勤记录',
                    // 边框
                    'border' => true,
                    // 字段
                    'field' => [
                        [
                            '姓名',
                            ['width' => 20]
                        ],
                        [
                            '时间',
                            ['width' => 60]
                        ],
                        [
                            '部门',
                            ['width' => 20]
                        ],
                        [
                            '打卡地点',
                            ['width' => 80]
                        ],
                        [
                            '是否迟到',
                            ['width' => 20]
                        ],
                    ],
                    // 数据
                    'content' => $arr,
                ],
            ],

        ];
        $re = (new MyExcel())->create($excel,'xls','storage/excel/');
        return self::success($re);
    }
}
