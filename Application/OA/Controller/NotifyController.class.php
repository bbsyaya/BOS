<?php
/**
 * Created by PhpStorm.
 * User: owq
 * Date: 2017/4/24
 * Time: 11:27
 */
namespace OA\Controller;
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
        if(UID ==1 or UID ==435 or UID == 596 or UID == 797 ) {//如果不是超级管理员,则显示对应的数据
            $where[] ="a.state=1";
        }else{
            $where[] = "a.state=1 and (a.END_DATE=0 or UNIX_TIMESTAMP(a.END_DATE)> " . time() . ")";//and (a.END_DATE=0 or UNIX_TIMESTAMP(a.END_DATE)> ".time().")
        }
        $name = I('get.name');
        if($name){
            $where[] = "a.SUBJECT like '%".$name."%'";
        }
        $notify_type = I('get.notify_type');
        if($notify_type){
            $where['TYPE_ID'] =$notify_type;
        }
        $status_type = I('get.status_type');
        if($status_type){
            if($status_type == 1){
                $where[] = 'a.END_DATE <=0';
            }elseif($status_type == 2){
                $where[] = 'a.END_DATE >0';
            }
        }

        $start_date = I('get.start_date');
        if($start_date){
            $where[] = "DATE_FORMAT(a.SEND_TIME,'%Y-%m-%d') ='".$start_date."' ";
        }

        $begin_date = I('get.begin_date');
        if($begin_date){
            $where['BEGIN_DATE'] =$begin_date;;
        }

        if(UID !=1 or UID !=435){//如果不是超级管理员,则显示对应的数据
            $dept_id = M('user')->field('dept_id,username')->where('id='.UID)->find();
            $where[] = "find_in_set(".$dept_id['dept_id'].",a.TO_ID) or find_in_set('".$dept_id['username']."',a.USER_ID)";
        }
        $notify_type = C('OPTION.notify_type');
        $status_type = C('OPTION.status_type');
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->assign('notify_type',$notify_type);
        $this->assign('status_type',$status_type);
        $this->assign('uid',UID);
        $this->display();
    }

    public function getList($where) {
        $order = I('get.order');
        $arr=explode('_',$order);
        if($arr[0]) {
            if ($arr[0] == 'beginDate') {
                $orders = ' BEGIN_DATE desc';
            } elseif ($arr[0] == 'startDate') {
                $orders = " DATE_FORMAT(a.SEND_TIME,'%Y-%m-%d') desc";
            }
        }else{
            $orders = 'a.NOTIFY_ID desc';
        }
        $notify_type = C('OPTION.notify_type');
        $notify = M('notify');
        $notifyData = $notify->field("a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,DATE_FORMAT(a.SEND_TIME,'%Y-%m-%d') as SEND_TIME,a.state")->join('a left join boss_user b on a.FROM_ID=b.id')->where($where)->order($orders)->page($_GET['p'],10)->select();
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
            if(strtotime($val['end_date'])>0 && strtotime($val['end_date'])<strtotime(date('Y-m-d'))){//结束日期小于等于0表示未终止
                $notifyData[$key]['status'] = '终止';
            }elseif($val['state'] == 1 && strtotime(date('Y-m-d'))<strtotime($val['begin_date']) ){
                $notifyData[$key]['status'] = '未生效';
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
        $Row = $notify->join('a left join boss_user b on a.FROM_ID=b.id')->where($where)->count();
        $this->totalPage =$Row;

        return $notifyData;
    }

    //新增或修改
    public function update(){

        $advModel = M('notify');
        $nid = $_POST['NOTIFY_ID'];
        $status = $_POST['status'];
        $advModel->create();

        if($nid && $status==1){//保留日志后再修改
            $notifyData = $advModel->where("NOTIFY_ID=".$nid)->find();
            $logModel = M('notify_log');
            $data = array();
            $data['NOTIFY_ID'] = $notifyData['notify_id'];
            $data['FROM_DEPT'] = $notifyData['from_dept'];
            $data['FROM_ID'] = $notifyData['from_id'];
            $data['TO_ID'] = $notifyData['to_id'];
            $data['TO_DEPART'] = $notifyData['to_depart'];
            $data['SUBJECT'] = $notifyData['subject'];
            $data['CONTENT'] = $notifyData['content'];
            $data['SEND_TIME'] = $notifyData['send_time'];
            $data['BEGIN_DATE'] = $notifyData['begin_date'];
            $data['END_DATE'] = $notifyData['end_date'];
            $data['ATTACHMENT_ID'] = $notifyData['attachment_id'];
            $data['ATTACHMENT_NAME'] = $notifyData['attachment_name'];
            $data['READERS'] = $notifyData['readers'];
            $data['PRINT'] = $notifyData['print'];
            $data['PRIV_ID'] = $notifyData['priv_id'];
            $data['USER_ID'] = $notifyData['user_id'];
            $data['TYPE_ID'] = $notifyData['type_id'];
            $data['TOP'] = $notifyData['top'];
            $data['TOP_DAYS'] = $notifyData['top_days'];
            $data['FORMAT'] = $notifyData['format'];
            $data['PUBLISH'] = $notifyData['publish'];
            $data['AUDITER'] = $notifyData['auditer'];
            $data['REASON'] = $notifyData['reason'];
            $data['AUDIT_DATE'] = $notifyData['audit_date'];
            $data['DOWNLOAD'] = $notifyData['download'];
            $data['LAST_EDITOR'] = $notifyData['last_editor'];
            $data['LAST_EDIT_TIME'] = $notifyData['last_edit_time'];
            $data['SUBJECT_COLOR'] = $notifyData['subject_color'];
            $data['SUMMARY'] = $notifyData['summary'];
            $data['KEYWORD'] = $notifyData['keyword'];
            $data['IS_FW'] = $notifyData['is_fw'];
            $data['GW_TYPE'] = $notifyData['gw_type'];
            $data['GW_WRITING'] = $notifyData['gw_writing'];
            $data['GW_USERNAME'] = $notifyData['gw_username'];
            $data['state'] = $notifyData['state'];
            $data['NAME'] = $notifyData['name'];
            $logModel->add($data);
        }
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
            $map['NAME'] = $_FILES[notifyFile]['tmp_name'];
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

    public function stop_s(){//终止
        $nid = I('get.nid');
        $adModel = M('notify');
        if($nid){
            $_map['END_DATE'] = date("Y-m-d",strtotime("-1 day"));
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
            $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_NAME,a.CONTENT')->join('a left join boss_user b on a.FROM_ID=b.id')->where("NOTIFY_ID=".$nid)->find();
            $notifyData['attachment_name'] = basename($notifyData['attachment_name']);
            $this->ajaxReturn($notifyData);
        }
    }

    public function up_title(){
        $nid = I('get.nid');
        $notify = M('notify');
        if($nid){

            $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_ID,a.ATTACHMENT_NAME,a.SEND_TIME,a.CONTENT,a.NAME')->join('a left join boss_user b on a.FROM_ID=b.id')
                ->where("NOTIFY_ID=".$nid)->find();
            $notifyData['content'] = html_entity_decode($notifyData['content']);
            if($nid<=638){
                $attachment_id = explode('@',$notifyData['attachment_id']);
                $id_string=explode('_',$attachment_id[1]);
                $a = $id_string[0];
                $b = rtrim($id_string[1], ",");
                $hz = substr($notifyData['name'], strrpos($notifyData['name'], '.')+1);
                $notifyData['attachment_name'] = "./upload/notify/".$a."/".$b.".".$hz;
            }
            $this->ajaxReturn($notifyData);
        }
    }

    public function edit(){
        $id = I('get.id');
        $status = I('get.status');
        if ($id > 0) {
            $notify = M('notify');
            $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_ID,a.ATTACHMENT_NAME,a.SEND_TIME,a.CONTENT,a.TOP,a.NAME,a.TO_DEPART')->join('a left join boss_user b on a.FROM_ID=b.id')->where("NOTIFY_ID=".$id)->find();
            $this->assign('upInfo', $notifyData);
            $this->assign('status', $status);
        }

        $notify_type = C('OPTION.notify_type');
        $this->assign('notify_type',$notify_type);
        $this->display();
    }

    public function detail(){
        $id = I('get.id');
        if ($id > 0) {
            $notify = M('notify');
            $notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_ID,a.ATTACHMENT_NAME,a.SEND_TIME,a.CONTENT,a.TOP,a.NAME,a.TO_DEPART')->join('a left join boss_user b on a.FROM_ID=b.id')->where("NOTIFY_ID=".$id)->find();
            $this->assign('upInfo', $notifyData);

            $notify_log = M('notify_log');
            $logData = $notify_log->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_ID,a.ATTACHMENT_NAME,a.SEND_TIME,a.CONTENT,a.TOP,a.NAME,a.TO_DEPART')->join('a left join boss_user b on a.FROM_ID=b.id')->where("NOTIFY_ID=".$id)->select();
            $this->assign('logData', $logData);
        }

        $notify_type = C('OPTION.notify_type');
        $this->assign('notify_type',$notify_type);
        $this->display();
    }
}