<?php
/**
 * Created by PhpStorm.
 * User: owq
 * Date: 2017-09-06
 * Time: 9:36
 * 会议室申请
 * Class MeetRoom
 * @package OA\Controller
 */
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;

class UseroutController extends BaseController {

    public function index(){

        /*$MeetRoom = M('oa_meetroomreservation');
        $date = I('get.date');
        if($date){
            $where = "the_date = '".$date."'";
        }else{
            $where = "the_date = '".date('Y-m-d')."'";
        }

        $Data = $MeetRoom->field('*')->where($where)->select();
        $this->assign("list",$Data);
        $this->assign("time",date('Y-m-d H:i'));*/
        $this->alluser=M('user')->where("status=1")->select();
        $this->display();
    }
    public function jjdo(){
        $data=M('oa_liuchen')->where("ststus=1 && adduser=".$_SESSION['userinfo']['uid'])->find();
        if($data){
            exit('你还有发起的流程没有结束，不能转交工作');
        }
        $rule=M('user')->field("a.fun_per as uf,a.data_per as ud,c.fun_per as bf,c.data_per as bd,b.rules as jf,b.data_per as jd")->join("a join boss_auth_group b on a.group_id=b.id join boss_user_department c on a.dept_id=c.id")->where("a.id = ".$_SESSION['userinfo']['uid'])->find();
        var_dump($rule);
    }

}