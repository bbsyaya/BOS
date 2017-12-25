<?php
/**
 * 人才库
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-11-28
 * Time: 16:23
 */
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class TalentPoolController extends BaseController
{
    public function index(){

        $where = array();
        $this->assign('uid',UID);
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->display();
    }

    public function getList($where)
    {
        $ml = M('talent_pool');
        $where = '1=1';
        $id = I('get.id');
        if ($id) {
            $where .= " and id = " . $id;
        }

        $name = I('get.name');
        if ($name) {
            $where .= " and name like '%".$name."%' ";
        }

        $sex = I('get.sex');
        if ($sex) {
            if($sex == 2){
                $sex = 0;
            }
            $where .= " and sex = " . $sex;
        }

        $talent_level = I('get.talent_level');
        if ($talent_level) {
            $where .= " and talent_level =" . $talent_level;
        }

        $start_time = I('get.start_time');
        if ($start_time) {
            if($start_time == 1){
                $where .= " and years <1 ";
            }elseif($start_time == 2){
                $where .= " and years >=1 and years<3";
            }elseif($start_time == 3){
                $where .= " and years >=3 and years<5";
            }elseif($start_time == 4){
                $where .= " and years >=5 and years<10";
            }elseif($start_time == 5){
                $where .= " and years >=10";
            }
        }

        $order = I('get.order');
        if ($order) {
            if($order == 'talent_level_desc'){
                $orders = "talent_level desc";
            }elseif($order == 'talent_level'){
                $orders = "talent_level";
            }elseif($order == 'years_desc'){
                $orders = "years desc";
            }elseif($order == 'years'){
                $orders = "years";
            }elseif($order == 'education_desc'){
                $orders = "education desc";
            }elseif($order == 'education'){
                $orders = "education";
            }
        }else{
            $orders = 'id desc';
        }

        $education = I('get.education');

        $this->totalPage = $ml->where($where)->count();
        $resData = $ml->field("*")->where($where)->order($orders)->page($_GET['p'], C('LIST_ROWS'))->select();
        foreach($resData as $key=>$val){
            if($val['sex'] == 1){
                $resData[$key]['sex'] = '女';
            }else{
                $resData[$key]['sex'] = '男';
            }
            switch($val['talent_level']){
                case 1:
                    $talent_level = '一般员工';break;
                case 2:
                    $talent_level = '基层管理';break;
                case 3:
                    $talent_level = '中层管理';break;
                case 4:
                    $talent_level = '高层管理';break;
                case 5:
                    $talent_level = '技术专家';break;
            }
            $resData[$key]['talent_level'] = $talent_level;

            switch($val['education']){
                case 1:
                    $education = '高中及以下';break;
                case 2:
                    $education = '大专';break;
                case 3:
                    $education = '本科';break;
                case 4:
                    $education = '研究生及以上';break;
            }
            $resData[$key]['education'] = $education;
        }

        return $resData;
    }

    public function update(){
        $ml = M('talent_pool');
        $id = I('post.id');
        $ml->create();
        $dates = $this->diffBetweenTwo(I('post.start_time'),date('Y-m-d'));
        $ml->years = round($dates,1);
        //头像
        if(!empty($_FILES['avatar_file']['tmp_name'])){ //是否上传操作
            $dir = UPLOAD_TALENT_POOL;//人才库上传文件路径
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $upfile = $_FILES['avatar_file']['name'];
            $qualiInfo = $this->uplaodfile('avatar_file', UPLOAD_TALENT_POOL);
            if (!is_array($qualiInfo)) {

                $retMsg = '上传文件失败';
                $this->ajaxReturn(array('status'=>0,'msg'=>$retMsg));
            }
            $filepath = UPLOAD_TALENT_POOL .$qualiInfo['avatar_file']['savepath'].$qualiInfo['avatar_file']['savename'];
            $ml->avatar = $upfile;
            $ml->avatar_path = $filepath;
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
            $ml->uid = UID;
            $id = $ml->add();
            if ($id === false) {
                $retMsg = $ml->getError();
            }else{
                $retMsg = '新增成功';
            }
            //编码
            $_map['id'] = $id;
            $_map['numbering'] = $this->generalCode($id);
            $ml->save($_map);
        }
        $this->ajaxReturn(array('status'=>1,'msg'=>$retMsg));
    }

    public function talent_upload(){
        //附件
        $dir = UPLOAD_TALENT_POOL;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $info = $this->uplaodfile("files",$dir);
        $file_path = $dir.$info["files"]["savepath"].$info["files"]["savename"];
        $file_path = ltrim($file_path,".");
        $list = array("msg"=>"上传失败","data"=>$file_path,"status"=>0,'name'=>$info['files']['name'],"houzui"=>$this->get_extension($_FILES['files']['name']));
        if($info){
            $list["msg"] = "上传成功";
            $list["status"] = 1;
        }
        $this->ajaxReturn($list);exit;
    }

    function get_extension($file){
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    public function edit(){
        $ml = M('talent_pool');
        $id = I('get.id');
        if($id){
            $resData = $ml->field("*")->where("id=".$id)->order("id desc")->find();
            $info3=substr(strrchr($resData['annex_path'], "."), 1);
            if($info3 == 'pdf'){
                $resData['path'] = 1;
            }else{
                $resData['path'] = 2;
            }
            //$resData['path'] = $info3;
            $this->assign('data',$resData);
        }
        $this->display();
    }

    public function detail(){
        $ml = M('talent_pool');
        $id = I('get.id');
        $resData = $ml->field("*")->where("id=".$id)->order("id desc")->find();
        foreach($resData as $val){
            if($val['sex'] == 1){
                $resData['sex'] = '女';
            }else{
                $resData['sex'] = '男';
            }
            switch($val['talent_level']){
                case 1:
                    $talent_level = '一般员工';break;
                case 2:
                    $talent_level = '基层管理';break;
                case 3:
                    $talent_level = '中层管理';break;
                case 4:
                    $talent_level = '高层管理';break;
                case 5:
                    $talent_level = '技术专家';break;
            }
            $resData['talent_level'] = $talent_level;

            switch($val['education']){
                case 1:
                    $education = '高中及以下';break;
                case 2:
                    $education = '大专';break;
                case 3:
                    $education = '本科';break;
                case 4:
                    $education = '研究生及以上';break;
            }
            $resData['education'] = $education;

            $info3=substr(strrchr($resData['annex_path'], "."), 1);
            if($info3 == 'pdf'){
                $resData['path'] = 1;
            }else{
                $resData['path'] = 2;
            }
        }
        $this->assign('data',$resData);
        $this->display();
    }

    public function uplaodfile($name,$dir){

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 10000000000;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg','JPG', 'GIF', 'PNG', 'JPEG', 'xlsx', 'zip', 'rar', 'xls', 'pdf', 'txt', 'doc', 'docx');// 设置附件上传类型

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

    public function generalCode($id) {
        return 'RCK' . str_pad(intval($id), 7, 0, STR_PAD_LEFT);
    }

    public function diffBetweenTwo ($day1, $day2){
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400/365;
    }
}