<?php
/**
 * Created by PhpStorm.
 * User: owq
 * Date: 2017/4/24
 * Time: 11:27
 */
namespace Home\Controller;
use Common\Controller\BaseController;
/**
 * 公司、制度、新闻管理
 * Class AttendController
 * @package Home\Controller
 */
class NotifyController extends BaseController {
    public $totalPage = 0;
    public function index(){
        $where = array();
        $where[] = 'state=1';
        $name = I('get.name');
        if($name){
            $where[] = "SUBJECT like '%".$name."%'";
        }
        $notify_type = I('get.notify_type');
        if($notify_type){
            $where['TYPE_ID'] =$notify_type;
        }
        $status_type = I('get.status_type');
        if($status_type){
            if($status_type == 1){
                $where[] = 'END_DATE <=0';
            }elseif($status_type == 2){
                $where[] = 'END_DATE >0';
            }
        }
        $notify_type = C('OPTION.notify_type');
        $status_type = C('OPTION.status_type');
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->assign('notify_type',$notify_type);
        $this->assign('status_type',$status_type);
        $this->display();
    }

    public function getList($where) {

        $notify_type = C('OPTION.notify_type');
        $notify = M('notify');
        $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name')->join('a join boss_user b on a.FROM_ID=b.id')->where($where)->order('TOP desc')->page($_GET['p'],10)->select();
        foreach($notifyData as $key=>$val){
            $notifyData[$key]['TYPE_ID'] = $notify_type[$val['type_id']];
            if($val['to_id']){
                $dept = M('user_department')->field('name')->where("id in (".rtrim($val['to_id'],",").")")->select();
                $name = '';
                foreach($dept as $v){
                    $name .= $v['name'].",";
                }
                $notifyData[$key]['user'] = rtrim($name,",");
            }
            if($val['user_id']){
                $us = M('user')->field('real_name')->where("id in (".rtrim($val['user_id'],",").")")->select();
                $name = '';
                foreach($us as $v2){
                    $name .= $v2['real_name'].",";
                }
                $notifyData[$key]['user'] = rtrim($name,",");
            }
            if($val['end_date'] >0){//结束日期小于等于0表示未终止
                $notifyData[$key]['status'] = '终止';
            }else{
                $notifyData[$key]['status'] = '生效';
            }
            /*if($val['begin_date']){
                $notifyData[$key]['begin_date'] = date('Y-m-d',$val['begin_date']);
            }
            if($val['end_date']){
                $notifyData[$key]['end_date'] = date('Y-m-d',$val['end_date']);
            }*/

        }
        $Row = $notify->where($where)->count();
        $this->totalPage =$Row;

        return $notifyData;
    }

    //新增或修改
    public function update(){

        $advModel = M('notify');
        $nid = $_POST['NOTIFY_ID'];
        $advModel->create();

        if($nid){//修改
            $advModel->LAST_EDITOR = UID;
            $advModel->LAST_EDIT_TIME = date('Y-m-d H:i:s',time());
            if ($advModel->save() === false) {
                $retMsg = $advModel->getError();
            }else{
                $retMsg = '修改成功';
            }

        }else{//新增
            $advModel->SEND_TIME = date('Y-m-d H:i:s',time());
            $nid = $advModel->add();
            if ($nid === false) {
                $retMsg = $advModel->getError();
            }else{
                $retMsg = '新增成功';
            }
        }
        if(!empty($_FILES[notifyFile]['tmp_name'])){ //是否上传操作
            $qualiInfo = $this->uplaodfile('notifyFile', UPLOAD_CONTRACT);
            if (!is_array($qualiInfo)) {
                $this->ajaxReturn($qualiInfo);
            }

            $filepath = UPLOAD_CONTRACT .$qualiInfo['notifyFile']['savepath'].$qualiInfo['notifyFile']['savename'];
            //$this->ajaxReturn($filepath);

            //保存路径
            $map = array();
            $map['NOTIFY_ID']= $nid;
            $map['ATTACHMENT_ID']= $nid;
            $map['ATTACHMENT_NAME'] = $filepath;
            if ($advModel->save($map) === false) {
                $retMsg = $advModel->getError();//错误信息
            }
        }
        $goUrl = U('index');
        $this->success($retMsg,$goUrl);
    }

    public function delete(){//删除(目前是逻辑删除)
        $nid = I('get.nid');
        $adModel = M('notify');
        if($nid){
            $_map['state'] = 0;
            $_map['NOTIFY_ID'] = array('in',$nid);
            if ($adModel->save($_map) === false) {
                $this->ajaxReturn($adModel->getError());
            }else{
                $this->ajaxReturn("TRUE");
            }
        }
    }

    public function top(){//置顶 取消置顶
        $adModel = M('notify');
        $nid = I('get.nid');
        $top = I('get.top');
        $_map['TOP'] = $top;
        $_map['NOTIFY_ID'] = array('in',$nid);
        if ($adModel->save($_map) === false) {
            $this->ajaxReturn($adModel->getError());
        }else{
            $this->ajaxReturn("TRUE");
        }
    }

    public function up_show(){
        $nid = I('get.nid');
        $notify = M('notify');
        if($nid){
            $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_NAME,a.CONTENT')->join('a join boss_user b on a.FROM_ID=b.id')->where("NOTIFY_ID=".$nid)->find();
            $notifyData['attachment_name'] = basename($notifyData['attachment_name']);
            $this->ajaxReturn($notifyData);
        }
    }

    public function up_title(){
        $nid = I('get.nid');
        $notify = M('notify');
        if($nid){

            $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_NAME,a.SEND_TIME,a.CONTENT')->join('a join boss_user b on a.FROM_ID=b.id')->where("NOTIFY_ID=".$nid)->find();

            $this->ajaxReturn($notifyData);
        }
    }

    public function edit(){
        $id = I('get.id');
        if ($id > 0) {
            $notify = M('notify');
            $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_NAME,a.SEND_TIME,a.CONTENT,a.TOP')->join('a join boss_user b on a.FROM_ID=b.id')->where("NOTIFY_ID=".$id)->find();
            $this->assign('upInfo', $notifyData);
        }

        $notify_type = C('OPTION.notify_type');
        $this->assign('notify_type',$notify_type);
        $this->display();
    }
}