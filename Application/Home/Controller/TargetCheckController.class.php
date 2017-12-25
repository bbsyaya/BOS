<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 风控管理-目标检查管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-11-13
 * Time: 16:45
 */
class TargetCheckController extends BaseController
{
    //初始化查询
    public function index(){

        $where = array();

        //目标
        $fm = M('goal_manage')->where("status=1")->getField('id,name');
        $this->assign('goal_manage',$fm);
        //主体
        $mb = M('main_body')->where("status=1")->getField('id,name');
        $this->assign('main_body',$mb);
        $this->assign('uid',UID);
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->display();
    }

    public function getList($where){
        $ml = M('target_check');
        $where = '1=1';
        $id = I('get.id');
        if($id){
            $where .= " and id = ".$id;
        }
        $goal_manage = I('get.goal_manages');
        if($goal_manage){
            $where .= " and mid = ".$goal_manage;
        }
        $main_body = I('get.main_body');
        if($main_body){
            $where .= " and bid = ".$main_body;
        }
        $status = I('get.status');
        if($status){
            if($status == 7){
                $status = 0;
            }
            $where .= " and status =".$status;
        }

        $str_time = I('get.str_time');
        if($str_time){
            $where .= " and DATE_FORMAT(start_time,'%Y-%m-%d') like '%".$str_time."%'";
        }

        $this->totalPage = $ml->where($where)->count();
        $resData = $ml->field("*,DATE_FORMAT(create_time,'%Y-%m') as create_time")->where($where)->order("id desc")->page($_GET['p'], C('LIST_ROWS'))->select();
        foreach($resData as $key=>$val){
            switch($val['status']){
                case 0;
                    $st = '创建';break;
                case 1;
                    $st = '控制中';break;
                case 2;
                    $st = '红灯';break;
                case 3;
                    $st = '黄灯';break;
                case 4;
                    $st = '完成待确认';break;
                case 5;
                    $st = '完成';break;
                case 6;
                    $st = '拒绝';break;
            }
            $resData[$key]['st'] = $st;
            $userData = M('user')->field('real_name')->where("id=".$val['responsible'])->find();
            $resData[$key]['real_name'] = $userData['real_name'];

            //步骤
            $stepData = M('step_status')->field('a.user,b.sid,b.name')->join("a join boss_goal_step b on a.sid=b.id")->where("a.status=1 and a.tid=".$val['id'])->find();
            if($stepData){
                $resData[$key]['step'] = '步骤'.$stepData['sid'].'：'.$stepData['name'];
                $resData[$key]['step_user'] = $stepData['user'];
            }else{
                $resData[$key]['step'] = '步骤已完成';
            }

            //控制目标
            $goal = M('goal_manage')->field('name')->where("id=".$val['mid'])->find();
            $resData[$key]['goal'] = $goal['name'];
            //主体
            $main_body = M('main_body')->field('name')->where("id=".$val['bid'])->find();
            $resData[$key]['main_body'] = $main_body['name'];
        }
        return $resData;
    }

    //修改页面
    public function edit(){

        $ml = M('target_check');
        $id = I('get.id');
        //目标
        $fm = M('goal_manage')->where("status=1")->getField('id,name');
        $this->assign('goal_manage',$fm);
        //主体
        $mb = M('main_body')->where("status=1")->getField('id,name');
        $this->assign('main_body',$mb);
        //责任人
        $this->assign('user',M('user')->field('id,real_name')->where("status=1")->select());
        $this->assign('users',M('user')->where("status=1")->getField('id,real_name'));
        if($id){
            $resData = $ml->where("id=".$id)->find();
            $this->assign("adInfo",$resData);
            //附件
            $check_file = M('annex_file')->field('id,annex,annex_path')->where("type=3 and sid=".$id)->select();
            $this->assign("check_file",$check_file);
            //步骤
            $stepData = M('step_status')->field("a.id as tid,a.user,b.id,b.sid,b.name,b.annex,b.annex_path")->join("a join boss_goal_step b on a.sid=b.id")->where("a.tid=".$id."")->select();
            $this->assign("stepData",$stepData);
            $this->assign("counts",count($stepData));
        }
        $this->display();
    }

    //修改或者新增 操作库
    public function update(){

        $id = I('post.id');
        if(empty(I('post.procontacts'))){

            $retMsg = '步骤信息不能为空';
            $this->ajaxReturn(array('status'=>0,'msg'=>$retMsg));
        }

        $ml = M('target_check');
        $afModel = M('annex_file');
        $contactModel = M('goal_step');
        $stepModel = M('step_status');
        $ml->create();

        if($id){//修改
            if ($ml->save() === false) {
                $retMsg = $ml->getError();
            }else{
                $retMsg = '修改成功';
            }
        }else{//新增
            $ml->create_time = date('Y-m-d H:i:s',time());
            $ml->create_id = UID;
            $id = $ml->add();
            if ($id === false) {
                $retMsg = $ml->getError();
            }else{
                $retMsg = '新增成功';
            }

        }
        $count_id = $contactModel->field('id')->where("state=1 and sid=1 and gm_id=".I('post.mid'))->find();

        //控制目标管理上传文件
        if(!empty($_FILES['tc_file']['tmp_name'])){ //是否上传操作
            $dir = UPLOAD_CONTROL_OBJ;
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $upfile = $_FILES['tc_file']['name'];
            $qualiInfo = $this->uplaodfile('tc_file', UPLOAD_CONTROL_OBJ);
            if (!is_array($qualiInfo)) {

                $retMsg = '上传文件失败';
                $this->ajaxReturn(array('status'=>0,'msg'=>$retMsg));
            }
            $filepath = UPLOAD_CONTROL_OBJ .$qualiInfo['tc_file']['savepath'].$qualiInfo['tc_file']['savename'];

            $afData = array();
            $afData['type'] = 3;
            $afData['sid'] = $id;
            $afData['annex'] = $upfile;
            $afData['annex_path'] = $filepath;
            $afModel->add($afData);
        }

        $ctData = I('post.procontacts');
        foreach ($ctData as $key=>$val) {

            if ($val['tid'] > 0) {//修改(tid->boss_step_status.id)

                $stepData = array();
                $stepData['id'] = $val['tid'];
                $stepData['tid'] = $id;
                $stepData['gid'] = I('post.mid');
                $stepData['sid'] = $val['id'];
                $stepData['user'] = $val['user'];
                $stepModel->save($stepData);

            }else{//新增

                $step_s = M('step_status')->field('id')->where("tid='".$id."' and sid='".$val['id']."'")->find();
                if($step_s['id']>0){

                    $stepData = array();
                    $stepData['id'] = $step_s['id'];
                    $stepData['tid'] = $id;
                    $stepData['gid'] = I('post.mid');
                    $stepData['sid'] = $val['id'];
                    $stepData['user'] = $val['user'];
                    $stepModel->save($stepData);
                }else{

                    $stepData = array();
                    $stepData['tid'] = $id;
                    $stepData['gid'] = I('post.mid');
                    $stepData['sid'] = $val['id'];
                    $stepData['user'] = $val['user'];
                    if($val['id'] == $count_id['id']){
                        $stepData['status'] = 1;
                    }
                    $stepModel->add($stepData);
                }
            }
        }

        $this->ajaxReturn(array('status'=>1,'msg'=>$retMsg));
    }

    //根据控制目标查询对应步骤
    public function step_sel(){
        $goal_id = I('get.goal_id');
        if($goal_id){
            $stepData = M('goal_step')->field("id,sid,name,annex,annex_path")->where("state=1 and gm_id=".$goal_id)->order('sid desc')->select();
            $userData = M('user')->field('id,real_name')->where("status=1")->select();
            $this->ajaxReturn(array('step_list'=>$stepData,'userdata'=>$userData,'count_data'=>count($stepData)));
        }
    }

    public function detail(){

        $ml = M('target_check');
        $id = I('get.id');
        if($id) {
            $resData = $ml->field("a.*,b.name as goal_name,c.name as main_name,d.real_name,e.real_name as dock_user,DATE_FORMAT(a.start_time,'%Y-%m') as start_time")->join("a join boss_goal_manage b on a.mid=b.id join boss_main_body c on c.id=a.bid join boss_user d on a.responsible=d.id join boss_user e on e.id=c.butt")->where("a.id=" . $id)->find();
            $this->assign("adInfo", $resData);

            //全部步骤信息
            $stepDatas = M('step_status')->field("a.id as ssid,a.tid,a.sid as gsid,a.user,a.status,b.id,b.sid,b.name,b.annex,b.annex_path")->join("a join boss_goal_step b on a.sid=b.id")->where("a.tid=".$id."")->select();
            $this->assign("allStepData",$stepDatas);
            $this->assign("allStepData_num",count($stepDatas));

            //已完成步骤信息和当前步骤信息
            $step_Data = M('step_status')->field("a.id as ssid,a.tid,a.sid as gsid,a.user,a.status,b.id,b.sid,b.name,b.annex,b.annex_path")->join("a join boss_goal_step b on a.sid=b.id")->where("a.tid=".$id." and a.status>0")->select();
            $this->assign("section_stepData",$step_Data);

            //当前步骤信息
            $stepData = M('step_status')->field("a.id as ssid,a.tid,a.sid as gsid,a.user,a.status,b.id,b.sid,b.name,b.annex,b.annex_path")->join("a join boss_goal_step b on a.sid=b.id")->where("a.tid=".$id." and a.status=1")->find();
            $this->assign("stepData",$stepData);

            /*if(empty($stepData)){
                $sid = '';
                $ssData = M('step_status')->field('sid')->where("tid=".$id."")->select();
                foreach($ssData as $val){
                    $sid .= $val['sid'].",";
                }
                $sid = rtrim($sid,",");
            }else{
                $sid = $stepData['gsid'];
            }*/

            $sid = '';
            $ssData = M('step_status')->field('sid')->where("tid=".$id."")->select();
            foreach($ssData as $val){
                $sid .= $val['sid'].",";
            }
            $sid = rtrim($sid,",");
            
            //跟进信息
            $follow_manage = M('follow_manage')->field("a.id,a.traffic_light,a.follow_desc,a.follow_path,a.follow_annex,DATE_FORMAT(a.add_time,'%Y-%m-%d') as start_time,b.real_name")->join("a join boss_user b on a.uid=b.id")->where("a.sid in (".$sid.") and a.tc_id=".$id)->select();
            foreach($follow_manage as $key=>$val){
                switch($val['traffic_light']){
                    case 1:
                        $traffic_light = '绿灯';break;
                    case 2:
                        $traffic_light = '红灯';break;
                    case 3:
                        $traffic_light = '黄灯';break;
                }
                $follow_manage[$key]['traffic_light'] = $traffic_light;
                //查跟进信息对应的回复信息
                $follow_reply = M('follow_reply')->field("id,fm_id,uid,content,annex,annex_path,DATE_FORMAT(add_time,'%Y-%m-%d') as add_time")->where("fm_id=".$val['id'])->order('id desc')->select();
                foreach($follow_reply as $key2=>$val2){
                    $userData = M('user')->field('real_name')->where("id=".$val2['uid'])->find();
                    $follow_reply[$key2]['real_name'] = $userData['real_name'];
                }
                $follow_manage[$key]['follow_reply'] = $follow_reply;
            }
            $this->assign("follow_manage",$follow_manage);

            //最新跟进信息
            $maxData = M('follow_manage')->field('traffic_light')->where("tc_id=".$id)->order("id desc")->find();
            $this->assign("traffic_light",$maxData['traffic_light']);

            $this->assign('uid',UID);
        }
        $this->display();
    }

    //修改targetCheck 状态
    public function change_status(){
        $id = I('get.id');
        $status = I('get.status');
        $Model = M('target_check');
        if($id){
            $data = array();
            $data['id'] = $id;
            $data['status'] = $status;
            if($Model->save($data) === false){
                $this->ajaxReturn(array('status'=>0,'msg'=>$Model->getError()));
            }else{
                $this->ajaxReturn(array('status'=>1,'msg'=>'操作成功'));
            }
        }
    }

    //修改步骤状态
    public function change_step_status(){
        $bss_id = I('get.bss_id');
        $btc_id = I('get.btc_id');
        $Model = M('step_status');
        if($bss_id && $btc_id){
            $data = array();
            $data['id'] = $bss_id;
            $data['status'] = 2;
            if($Model->save($data) === false){
                $this->ajaxReturn(array('status'=>0,'msg'=>$Model->getError()));
            }else{
                $ids = $Model->field("id")->where("status =0 and tid=".$btc_id)->order('id')->find();
                $idData = array();
                $idData['id'] = $ids['id'];
                $idData['status'] = 1;
                if($Model->save($idData) === false){
                    $this->ajaxReturn(array('status'=>0,'msg'=>$Model->getError()));
                }else{
                    $this->ajaxReturn(array('status'=>1,'msg'=>'操作成功'));
                }
            }
        }
    }

    //跟进回复
    public function follow_reply(){
        $ml = M('follow_reply');
        $tc_id = I('post.tc_id');
        if(!empty($_FILES['follow_file']['tmp_name'])){ //是否上传操作
            $upfile = $_FILES['follow_file']['name'];
            $qualiInfo = $this->uplaodfile('follow_file', UPLOAD_CONTROL_OBJ);
            if (!is_array($qualiInfo)) {

                $retMsg = $qualiInfo;
                $goUrl = U('detail?id='.$tc_id);
                $this->success($retMsg,$goUrl);
            }
            $filepath = UPLOAD_CONTROL_OBJ .$qualiInfo['follow_file']['savepath'].$qualiInfo['follow_file']['savename'];

        }
        $fm_id = I('post.fm_id');
        $content = I('post.content');
        $data = array();
        $data['fm_id'] = $fm_id;
        $data['content'] = $content;
        $data['uid'] = UID;
        $data['add_time'] = date('Y-m-d H:i:s',time());
        $data['annex'] = $upfile;
        $data['annex_path'] = $filepath;
        if($ml->add($data) === false){

            $retMsg = $ml->getError();
            $this->ajaxReturn(array('status'=>0,'msg'=>$retMsg));
        }else{
            $retMsg = '回复成功';
            $this->ajaxReturn(array('status'=>1,'msg'=>$retMsg));
        }
    }

    //弹出框数据
    public function targetCheck_sel(){
        $ml = M('target_check');
        $id = I('get.id');
        if($id) {

            $resData = $ml->field("a.*,b.id as goal_id,b.name as goal_name,c.name as main_name,c.butt,d.real_name,e.real_name as dock_user,DATE_FORMAT(a.start_time,'%Y-%m') as start_time")->join("a join boss_goal_manage b on a.mid=b.id join boss_main_body c on c.id=a.bid join boss_user d on a.responsible=d.id join boss_user e on e.id=c.butt")->where("a.id=" . $id)->find();

            //步骤信息
            $stepData = M('step_status')->field("b.id,b.name")->join("a join boss_goal_step b on a.sid=b.id")->where("a.tid=" . $id . " and a.status=1")->find();

            $this->ajaxReturn(array('target'=>$resData,'step_status'=>$stepData));
        }
    }

    //添加跟进信息
    public function add_follow_manage(){
        if(!empty($_FILES['follow_manage_file']['tmp_name'])){ //是否上传操作
            $upfile = $_FILES['follow_manage_file']['name'];
            $qualiInfo = $this->uplaodfile('follow_manage_file', UPLOAD_CONTROL_OBJ);
            if (!is_array($qualiInfo)) {

                $this->ajaxReturn(array('status'=>0,'msg'=>'上传失败'));
            }
            $filepath = UPLOAD_CONTROL_OBJ .$qualiInfo['follow_manage_file']['savepath'].$qualiInfo['follow_manage_file']['savename'];

        }
        $follow_manage = M('follow_manage');
        $addData = array();
        $addData['tc_id'] = I('post.tc_id');
        $addData['mid'] = I('post.mid');
        $addData['sid'] = I('post.sid');
        $addData['traffic_light'] = I('post.traffic_light');
        $addData['follow_desc'] = I('post.follow_desc');
        $addData['follow_path'] = $filepath;
        $addData['follow_annex'] = $upfile;
        $addData['uid'] = UID;
        $addData['add_time'] = date('Y-m-d H:i:s',time());
        $addData['remind'] = I('post.remind');

        if($follow_manage->add($addData) === false){
            $this->ajaxReturn(array('status'=>0,'msg'=>$follow_manage->getError()));
        }else{

            if(I('post.remind') == 1){//提醒对接人
                $prompt_information = M('prompt_information');
                $piData = array();
                $piData['date_time'] = date('Y-m-d H:i:s');
                $piData['send_user'] = I('post.dock');
                $piData['content'] = "你有风险控制目标'".I('post.mubiao')."'，请及时处理";
                $piData['a_link'] = "/TargetCheck/index?id=".I('post.tc_id')."";
                $piData['oa_number'] = '';
                $prompt_information->add($piData);
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>'跟进成功','go'=>U('index')));
        }
    }

    /*复制功能*/
    public function copy_control(){
        $ml = M('target_check');
        $afModel = M('annex_file');
        $stepModel = M('step_status');
        $id = I('post.id');
        if($id){
            foreach($id as $val){
                //查询id对应的目标数据
                $tcData = $ml->where("id=".$val)->find();
                $data = array();
                $data['mid'] = $tcData['mid'];
                $data['bid'] = $tcData['bid'];
                $data['responsible'] = $tcData['responsible'];
                $data['target_desc'] = $tcData['target_desc'];
                $data['cycle'] = $tcData['cycle'];
                $data['start_time'] = date('Y-m-d');
                $data['end_time'] = date('Y-m-d',strtotime('+'.$tcData['cycle'].' day'));
                $data['create_time'] = date('Y-m-d H:i:s',time());
                $data['create_id'] = UID;
                $new_id = $ml->add($data);
                //目标附件
                $fileData = $afModel->where("type=3 and sid=".$val)->find();
                if($fileData){
                    $afData = array();
                    $afData['type'] = 3;
                    $afData['sid'] = $new_id;
                    $afData['annex'] = $fileData['annex'];
                    $afData['annex_path'] = $fileData['annex_path'];
                    $afModel->add($afData);
                }
                //步骤
                $stepData = $stepModel->where("tid=".$val)->select();
                foreach($stepData as $key=>$val){
                    $asData = array();
                    $asData['tid'] = $new_id;
                    $asData['gid'] = $val['gid'];
                    $asData['sid'] = $val['sid'];
                    $asData['user'] = $val['user'];
                    if($key == 0){
                        $asData['status'] = 1;
                    }
                    $stepModel->add($asData);
                }
            }
        }
        $this->ajaxReturn(array('status'=>1,'msg'=>'复制成功'));
    }
}