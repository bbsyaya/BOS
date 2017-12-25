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

class MeetRoomController extends BaseController {

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
        $this->assign("uid",UID);
        $this->alluser=M('user')->where("status=1")->select();
        $this->display();
    }

    public function checkhuiyishi(){
        $MeetRoom = M('oa_meetroomreservation');
        $id = I('get.id');
        $date = I('get.date');
        if($id){
            $where = "id = '".$id."'";
        }elseif($date){
            $where = "the_date = '".$date."'";
        }else{
            $where = "the_date = '".date('Y-m-d')."'";
        }

        $Data = $MeetRoom->field('*')->where($where)->order("start_time")->select();
        foreach($Data as $key=>$val){
            $user = M('user')->field('id,real_name')->where('id='.$val['create_id'])->find();
            $Data[$key]['create_id'] = $user['real_name'];
            $Data[$key]['uid'] = $user['id'];

            $users = M('user')->field('real_name')->where("id IN (".$val['userid'].")")->select();
            $username = '';
            foreach($users as $val2){
                $username .= $val2['real_name'].',';
            }
            $username = rtrim($username,",");
            $Data[$key]['username'] = $username;
        }
        $user = M('user')->field('real_name')->where('id='.UID)->find();
        $this->ajaxReturn(array('res'=>$Data,'real_name'=>$user['real_name']));exit;
    }

    /*新增会议室申请*/
    public function inhuiyishi(){

        $MeetRoom = M('oa_meetroomreservation');

        $date = date('Y-').str_replace(array("月",'日'), array("-",''), I('post.the_date'));

        //判断会议室是否预定
        $Data = $MeetRoom->field('id')->where("end_time>'".I('post.start_time')."' && start_time<'".I('post.end_time')."' && the_date='".$date."' && meetroom_name='".I('post.meetroom_name')."'")->find();
        if($Data['id']){

            $this->ajaxReturn(array('status'=>0,'msg'=>"会议室".$date.' '.I('post.start_time') ."到" .I('post.end_time')."已预定"));
        }else{

            /*if ($MeetRoom->create() === false) {
                $this->ajaxReturn(array('msg'=>$MeetRoom->getError()));
            }*/
            $addData = array();
            $addData['meetroom_name'] = I('post.meetroom_name');
            $addData['start_time'] = I('post.start_time');
            $addData['end_time'] = I('post.end_time');
            $addData['title'] = I('post.title');
            $addData['content'] = I('post.content');
            $addData['userid'] = I('post.userid');
            $addData['the_date'] = $date;//预定日期
            $addData['whether'] = I('post.whether');//1.是否需要行政协助 2.发送会议提醒'
            $addData['create_time'] = date('Y-m-d H:i:s');
            $addData['create_id'] = UID;
            $insertId = $MeetRoom->add($addData);

            if ($insertId) {
                $prompt_information = M('prompt_information');
                if (I('post.whether') == '2') {
                    //发送会议提醒
                    $addData = array();
                    $addData['date_time'] = date('Y-m-d H:i:s');
                    $addData['send_user'] =I('post.userid');
                    $addData['content'] = "会议提醒：请在" . $date . ' ' . I('post.start_time') . ' 至 ' . I('post.end_time') . I('post.meetroom_name').'会议室参与'.I('post.content').'会议';
                    $addData['a_link'] = "/OA/MeetRoom/index?id=" . $insertId;
                    $addData['oa_number'] = UID;

                    $addData['end_time'] = $date.I('post.end_time').':00';

                    $prompt_information->add($addData);
                }else{
                    //都提醒

                    $addData = array();
                    $addData['date_time'] = date('Y-m-d H:i:s');
                    $addData['send_user'] = '798,'.I('post.userid');
                    $addData['content'] = "您有一个会，时间为" . $date . ' ' . I('post.start_time') . ' 至 ' . I('post.end_time') . ',请准时参加';
                    $addData['a_link'] = "/OA/MeetRoom/index?id=" . $insertId;
                    $addData['oa_number'] = 0;
                    $prompt_information->add($addData);

                }
                $this->ajaxReturn(array('status'=>1,'msg'=>"会议室预定成功"));
            }
        }


    }

    /*取消会议室*/
        public function DelMeet(){
            $id = I('get.id');
            if($id){

                $MeetRoom = M('oa_meetroomreservation');
                //判断提前至少半小时才能取消
                $res = $MeetRoom->field("the_date,start_time")->where("id=".$id)->find();
                $date = $res['the_date'].' '.$res['start_time'].':00';
                if($date>date("Y-m-d H:i:00",time())){
                    $data = array();
                    $data['id'] = $id;
                    if($MeetRoom->delete($id) === false){
                        $this->ajaxReturn(array('status'=>0,'msg'=>"会议室取消失败"));
                    }else{
                        $this->ajaxReturn(array('status'=>1,'msg'=>"会议室取消成功"));
                    }
                }else{
                    $this->ajaxReturn(array('status'=>0,'msg'=>"亲爱的话粉：会议室已不能取消，请合理利用资源"));
                }

            }else{
                $this->ajaxReturn(array('status'=>1,'msg'=>"请先选择会议室，然后取消"));
            }
        }

}