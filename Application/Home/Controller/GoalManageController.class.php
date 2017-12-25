<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 风控管理-控制目标步骤管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-11-13
 * Time: 16:40
 */
class GoalManageController extends BaseController
{
    public function  index(){
        $this->assign('user',M('user')->where("status=1")->getField('id,real_name'));
        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->assign('uid',UID);
        $this->display();
    }

    public function getList($where){
        $ml = M('goal_manage');
        $where = 'state=1';
        $name = I('get.name');
        if($name){
            $where .= " and name like '%".$name."%'";
        }
        $user_name = I('get.user_name');
        if($user_name){
            $userData = M('user')->field('id')->where("real_name like '%".$user_name."%'")->select();
            $uid = '';
            foreach($userData as $val){
                $uid .= $val['id'].",";
            }
            $uid = rtrim($uid,",");
            $where .= " and user in (".$uid.")";
        }
        $status = I('get.status');
        if($status){
            if($status == 2){
                $status = 0;
            }
            $where .= " and status =".$status;
        }

        $this->totalPage = $ml->where($where)->count();
        $resData = $ml->field("DATE_FORMAT(create_time,'%Y-%m-%d') as create_time,id,name,status,user")->where($where)->order("id desc")->page($_GET['p'], C('LIST_ROWS'))->select();
        foreach($resData as $key=>$val){
            if($val['status'] == 1){
                $resData[$key]['status'] = '正常';
            }else{
                $resData[$key]['status'] = '暂停';
            }
            $userData = M('user')->field('real_name')->where("id=".$val['user'])->find();
            $resData[$key]['real_name'] = $userData['real_name'];

            //步骤
            $stepData = M('goal_step')->field('count(id) as count_id')->where("state =1 and gm_id=".$val['id'])->find();
            /*$step = '';
            foreach($stepData as $val2){
                $step .= '步骤'.$val2['sid'].",";
            }
            $step = rtrim($step,",");*/
            $resData[$key]['step'] = $stepData['count_id'].'步';
        }
        return $resData;
    }

    public function detail(){
        $ml = M('goal_manage');
        $id = I('get.id');
        if($id){
            $resData = $ml->where("id=".$id)->find();
            $this->assign("adInfo",$resData);

            //查步骤
            $stepData = M('goal_step')->field("id,sid,name,annex,annex_path")->where("state=1 and gm_id=".$id)->select();
            $this->assign('stepData',$stepData);
        }
        $this->display();
    }

    public function edit(){
        $ml = M('goal_manage');
        $id = I('get.id');
        if($id){
            $resData = $ml->where("id=".$id)->find();
            if($resData['status'] ==0){
                $resData['status'] =2;
            }
            $this->assign("adInfo",$resData);

            //查步骤
            $stepData = M('goal_step')->field("id,sid,name,annex,annex_path")->where("state=1 and gm_id=".$id)->select();
            $this->assign('stepData',$stepData);
        }
        $this->display();
    }

    public function step_sel(){
        //查步骤
        $id = I('get.id');
        $stepData = M('goal_step')->field("id,sid,name,annex,annex_path")->where("state=1 and gm_id=".$id)->order('sid desc')->select();
        $this->ajaxReturn(array('step_list'=>$stepData));
    }

    public function deleteGoal(){
        $id = I('post.id');
        if($id){
            $model = M('goal_step');
            $data = array();
            $data['state'] = 0;
            $res = $model->where('id='.$id)->save($data);
            if($res){
                $this->ajaxReturn(array('msg'=>'步骤删除成功','error'=>'0'));
            }
        }

    }

    public function update(){

        $ml = M('goal_manage');
        $afModel = M('annex_file');
        $id = I('post.id');

        if(empty(I('post.procontacts'))){

            $retMsg = '步骤信息不能为空';
            $this->ajaxReturn(array('status'=>0,'msg'=>$retMsg));
        }

        $ml->create();
        //控制目标步骤管理上传文件
        if(!empty($_FILES['aims_file']['tmp_name'])){ //是否上传操作
            $dir = UPLOAD_CONTROL_OBJ;
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $upfile = $_FILES['aims_file']['name'];
            $qualiInfo = $this->uplaodfile('aims_file', UPLOAD_CONTROL_OBJ);
            if (!is_array($qualiInfo)) {

                $retMsg = '上传文件失败';
                $this->ajaxReturn(array('status'=>0,'msg'=>$retMsg));
            }
            $filepath = UPLOAD_CONTROL_OBJ .$qualiInfo['aims_file']['savepath'].$qualiInfo['aims_file']['savename'];
            $ml->annex = $upfile;
            $ml->annex_path = $filepath;
        }

        if($id){
            //修改
            if ($ml->save() === false) {
                $retMsg = $ml->getError();
            }else{
                $retMsg = '修改成功';
            }
        }else{
            //新增
            $ml->create_time = date('Y-m-d H:i:s',time());
            $ml->user = UID;
            $id = $ml->add();
            if ($id === false) {
                $retMsg = $ml->getError();
            }else{
                $retMsg = '新增成功';
            }
        }

        if(!empty($_FILES['aims_file']['tmp_name'])) {
            //新增到附件表中

            $afData = array();
            $afData['type'] = 1;
            $afData['sid'] = $id;
            $afData['annex'] = $upfile;
            $afData['annex_path'] = $filepath;
            $afModel->add($afData);
        }

        $ctData = I('post.procontacts');
        $contactModel = M('goal_step');
        //检查步骤
        if (!empty($ctData)) {
            foreach ($ctData as $val) {

                if ($contactModel->validate($ml->contactRule)->create($val) === false) {
                    $retMsg = $contactModel->getError();
                    $goUrl = U('edit?id='.$id);
                    $this->success($retMsg,$goUrl);
                }
            }
        }

        //步骤新增
        foreach ($ctData as $key=>$val) {

            if ($val['id'] > 0) {
                $sid = $val['id'];
                $data = array();
                $data['id'] = $val['id'];
                $data['gm_id'] = $id;
                $data['sid'] = $val['sid'];
                $data['name'] = $val['name'];
                $data['annex'] = $val['annex'];
                $data['annex_path'] = $val['annex_path'];
                $r = $contactModel->save($data);

            } else { //添加
                $data = array();
                $data['gm_id'] = $id;
                $data['sid'] = $val['sid'];
                $data['name'] = $val['name'];
                $data['annex'] = $val['annex'];
                $data['annex_path'] = $val['annex_path'];
                //var_dump($data);exit;
                $r = $contactModel->add($data);
                $sid = $r;
            }
            if($val['annex'] && $val['annex_path']){
                $afData = array();
                $afData['type'] = 2;
                $afData['sid'] = $sid;
                $afData['annex'] = $val['annex'];
                $afData['annex_path'] = $val['annex_path'];
                $afModel->add($afData);
            }
            if ($r === false) {
               // $this->ajaxReturn(array('msg'=>$contactModel->getError(),'go'=>$errGo));
                $retMsg = '步骤信息修改失败';
                $this->ajaxReturn(array('status'=>0,'msg'=>$retMsg));
            }
        }
        $this->ajaxReturn(array('status'=>1,'msg'=>$retMsg));
    }

    //步骤附件上传
    public function step_upload(){
        $dir = UPLOAD_CONTROL_OBJ;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $info = $this->uplaodfile("files",$dir);
        $file_path = $dir.$info["files"]["savepath"].$info["files"]["savename"];
        $file_path = ltrim($file_path,".");
        $list = array("msg"=>"上传失败","data"=>$file_path,"status"=>0,'name'=>$info["files"]["name"]);
        if($info){
            $list["msg"] = "上传成功";
            $list["status"] = 1;
        }
        $this->ajaxReturn($list);
    }

    public function uplaodfile($name,$dir){

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 10000000000;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'xlsx', 'zip', 'rar', 'xls', 'pdf', 'txt', 'doc', 'docx');// 设置附件上传类型

        $upload->rootPath = $dir; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录

        // 上传文件
        $upload->__set('saveName', time() . rand(100, 999));
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            return $upload->getError();
        } else {// 上传成功
            return $info;
        }

    }

    public function delete(){//逻辑删除
        $id = I('post.id');
        if($id){
            $model = M('goal_manage');
            $data = array();
            $data['state'] = 0;
            $res = $model->where('id='.$id)->save($data);
            if($res){
                $this->ajaxReturn(array('msg'=>'删除成功','status'=>'1'));
            }else{
                $this->ajaxReturn(array('msg'=>'删除失败','status'=>'0'));
            }
        }
    }
}