<?php

namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;

/**
 * 风控管理-控制主体管理
 * Created by PhpStorm.
 * User: owq
 * Date: 2017-11-13
 * Time: 15:20
 */
class MainBodyController extends BaseController
{
    public function  index(){
        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->assign('uid',UID);
        $this->display();
    }

    public function getList($where)
    {

        $ml = M('main_body');
        $where = 'state=1';
        $name = I('get.name');
        if($name){
            $where .= " and name like '%".$name."%'";
        }
        $butt = I('get.butt');
        if($butt){
            $userData = M('user')->field('id')->where("real_name like '%".$butt."%'")->select();
            $uid = '';
            foreach($userData as $val){
                $uid .= $val['id'].",";
            }
            $uid = rtrim($uid,",");
            if($uid){
                $where .= " and butt in (".$uid.")";
            }

        }
        $status = I('get.status');
        if($status){
            if($status ==2){
                $status = 0;
            }
            $where .= " and status =".$status;
        }

        $this->totalPage = $ml->where($where)->count();
        $resData = $ml->field("DATE_FORMAT(create_time,'%Y-%m-%d') as create_time,id,name,status,butt")->where($where)->order("id desc")->page($_GET['p'], C('LIST_ROWS'))->select();
        foreach($resData as $key=>$val){
            if($val['status'] == 1){
                $resData[$key]['status'] = '正常';
            }else{
                $resData[$key]['status'] = '暂停';
            }
            $userData = M('user')->field('real_name')->where("id=".$val['butt'])->find();
            $resData[$key]['real_name'] =$userData['real_name'];
        }
        return $resData;
    }

    public function edit(){
        $ml = M('main_body');
        $id = I('get.id');
        //$this->assign('user',M('user')->where("status=1")->getField('id,real_name'));
        $this->assign('user',M('user')->field('id,real_name')->where("status=1")->select());
        if ($id > 0) {
            //修改
            $resData = $ml->where("id=".$id)->find();
            if($resData['status'] == 0){
                $resData['status'] =2;
            }
            $this->assign("adInfo",$resData);
        }
        $this->display();
    }

    public function update(){

        $advModel = M('main_body');
        $nid = $_POST['id'];
        $advModel->create();

        if($nid){//修改
            if ($advModel->save() === false) {
                $retMsg = $advModel->getError();
            }else{
                $retMsg = '修改成功';
            }

        }else{//新增
            $advModel->create_time = date('Y-m-d H:i:s',time());
            $advModel->create_id = UID;
            $nid = $advModel->add();
            if ($nid === false) {
                $retMsg = $advModel->getError();
            }else{
                $retMsg = '新增成功';
            }
        }
        //$goUrl = U('index');
        //$this->success($retMsg,$goUrl);
        $this->ajaxReturn(array('status'=>1,'msg'=>$retMsg));
    }

    public function detail(){
        $id = I('get.id');
        $ml = M('main_body');
        if($id){
            $resData = $ml->field("DATE_FORMAT(a.create_time,'%Y-%m-%d') as create_time,a.id,a.name,a.status,a.butt,a.remark,b.real_name")->join("a left join boss_user b on a.butt=b.id")->where("a.id=".$id)->find();
            if($resData['status'] == 1){
                $resData['s'] = '正常';
            }else{
                $resData['s'] = '暂停';
            }

            $this->assign("adInfo",$resData);
        }
        $this->display();
    }

    public function deleteMain(){//逻辑删除
        $id = I('post.id');
        if($id){
            $model = M('main_body');
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