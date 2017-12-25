<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/13
 * Time: 15:12
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
class ContractController extends BaseController {

    //合同列表
    public function index(){

        /*$con_data = M('prompt_information')->field('*')->where("a_link='' ")->order("id desc")->select();
        foreach($con_data as $val){
            preg_match_all('/<a href="([^"]*)">/',$val['content'],$res_file);
            $a_link = $res_file[1][0];
            $cm = M('prompt_information');
            $map = array();
            $map['id']= $val['id'];
            $map['a_link'] = $a_link;
            if ($cm->save($map) === false) {
                //$this->ajaxReturn($cm->getError());
            }else{
                //$this->ajaxReturn("TRUE");
            }
        }*/

        //列表数据
        $map["run_id"]            = $run_id            = I('get.run_id',0,'intval');
        $map["contract_category"] = $contract_category = I('get.contract_category','');
        $map["contract_type"]     = $contract_type     = I('get.contract_type','');
        $map["contract_nature"]   = $contract_nature   = I('get.contract_nature','');
        $map["aciName"]           = $aciName           = I('get.aciName','');
        $map["deptName"]          = $deptName          = I('get.deptName','');
        $map["status"]            = $status            = I('get.status','');//执行状态
        $map["validity_period"]   = $validity_period   = I('get.validity_period','');//合同有效期
        $map["user_name"]         = $user_name         = I('get.user_name','');//经办人
        $map["approval_status"]   = $approval_status   = I('get.approval_status','');//审批状态
        $map["sb_name"]           = $sb_name           = I('get.sb_name','');//签订主体
        $map["remark"]            = $remark            = I('get.remark',0,'intval');//备注
        $this->assign("map",$map);

        $where = " (data_6 <> '' or data_7 <> '') and (data_139='销售合同' or data_139='推广合同') ";
        //改成字符串，原生sql
        if($_GET['id']){
            $where .= " and id=".$_GET['id']." ";
        }
        if ($run_id>0) {
            $where .= " and run_id=$run_id ";
        }
        if ($contract_category) {
            $where .= " and data_8='".$contract_category."' ";
        }
        if ($contract_type) {
            $where .= " and data_112='".$contract_type."' ";
        }
        if ($contract_nature) {
            $where .= " and data_139='".$contract_nature."' ";
        }
        if ($aciName) {
            $where .= " and (data_6 like '%$aciName%' or data_7 like '%$aciName%') ";
        }
        if ($deptName) {
            $where .= " and data_2 like '%$deptName%' ";
        }
        if ($status == '未存档') {
            $where .= " and data_50 = '' ";
        }elseif($status == '流程正常完结'){
            $where .= " and data_50 <> '' ";
        }elseif($status == '即将到期'){
            $where .= " and data_111 > '".date('Y-m-d')."' and data_111<='".date("Y-m-d",strtotime("+1months"))."' ";
        }elseif($status == '已到期'){
            $where .= " and data_111< '".date('Y-m-d')."' ";
        }elseif($status == 0){
            //$where .= " and status=0 ";
        }
        if ($validity_period) {
            $where .= " and data_110 <='".$validity_period."' ";
        }
        if ($user_name) {
            $where .= " and data_107 like '%$user_name%' ";
        }

        $isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr = $_SESSION["userinfo"]["realname"];
            $where .= " and data_107='%{$spidStr}%' ";
        }

        if ($approval_status) {
            if($approval_status == 1){
                $where .= " and data_50 <> '' and data_50 !='流程作废' ";
            }elseif($approval_status == 2){
                $where .= " and data_50 = '' ";
            }elseif($approval_status == 3){
                $where .= " and data_50='流程作废' ";
            }

        }
        if ($sb_name) {
            $where .= " and data_7='".$sb_name."' ";
        }
        if($remark){
            if($remark==4){
                $where .= " and (remark=2 || remark=0) ";
            }
            else $where .= " and remark=".$remark." ";
        }

        $uid = UID;
        $plModel = M('user');
        $userData = $plModel->field("real_name")->where("id=$uid")->find();
        $all_data = D('user')->getDataRange();
        // if($all_data == 1){
        //     //$where .= " and data_107 like '%".$userData['real_name']."%' ";
        //     $arr_name=array();
        //     $arr_name['line']=array('a.data_612');
        //     $arr_name['user']=array('a.data_107');
        //     $ruleser=new Service\RuleService();
        //     $myrule_data=$ruleser->getmyrule_data($arr_name);
        //     $where .= "and $myrule_data";
        // }

        $arr_name         =array();
        $arr_name['line'] =array('data_612');
        $arr_name['user'] =array('data_107');
        $ruleser          =new Service\RuleService();
        $myrule_data      =$ruleser->getmyrule_data($arr_name,0);
        $where            .= "and $myrule_data";

        //业务线
        $plModel = M('business_line');
        $pl_name = $plModel->field("name")->where("status=1")->select();

        //P($where,true);
        // print_r($where);exit;
        $list = $this->lists('Contract', $where);
        $this->assign('list', $list);
        $this->assign('pl_name', $pl_name);
        $this->assign('op_status', C('OPTION.function_status'));
        $this->display();

    }

    public  function uploadImg(){

        if(!empty($_FILES[contractImg]['tmp_name'])){ //是否上传操作
            $cid = $_POST['cid'];
            $qualiInfo = $this->uplaodfile('contractImg', UPLOAD_CONTRACT);
            if (!is_array($qualiInfo)) {
                $this->ajaxReturn($qualiInfo);
            }

            $filepath = UPLOAD_CONTRACT .$qualiInfo['contractImg']['savepath'].$qualiInfo['contractImg']['savename'];
            //$this->ajaxReturn($filepath);

            //保存图片路径
            $contModel = M('contract_file');//m(表)   D('model')
            $map = array();
            $map['cid']= $cid;
            $map['file_cont'] = $filepath;
            if ($contModel->add($map) === false) {
                $this->ajaxReturn($contModel->getError());
            }else{
                $this->ajaxReturn("上传成功");
            }

        }
    }

    function  change_state(){
        $id = $_POST['ID'];
        $STATUS = $_POST['STATUS'];
        if(!empty($id)){

            //修改状态
            $cm = D('Contract');
            $map = array();
            $map['id']= $id;
            $map['status'] = $STATUS;
            if ($cm->save($map) === false) {
                $this->ajaxReturn($cm->getError());
            }else{
                $this->ajaxReturn("TRUE");
            }

        }
    }

    function change_remark(){//修改备注 2017.01.09
        $id = $_POST['ID'];
        $REMARK = $_POST['REMARK'];
        if(!empty($id)){
            //修改状态
            $cm = D('Contract');
            $map = array();
            $map['id']= $id;
            $map['remark'] = $REMARK;
            if ($cm->save($map) === false) {
                $this->ajaxReturn($cm->getError());
            }else{
                $this->ajaxReturn("TRUE");
            }
        }
    }

    //消息提示
    function to_information(){

        $contModel = M('prompt_information');
        $uid = UID;
        $data = $contModel->where("FIND_IN_SET('".$uid."',send_user) AND `status`=0")->select();
        if(!empty($data)){
            $this->ajaxReturn($data);
            //echo "true";exit;
        }else{
            //$this->ajaxReturn($contModel->getError());
            echo "false";exit;
        }

    }

    //修改通知信息状态
    function change_status(){

        $fid = $_POST['FID'];
        $fid = rtrim($fid, ",");

        $map = array();
        $map['id']= array('in',$fid);
        $map['status'] = 1;

        $contModel = M('prompt_information');
        if( $contModel->save($map) === false){
            $this->ajaxReturn($contModel->getError());
        }else{
            $this->ajaxReturn("TRUE");
        }
    }

    function status_up(){
        $status = $_POST['status'];
        $uid = UID;
        $contModel = M('prompt_information');
        $data = $contModel->where(" FIND_IN_SET('".$uid."',send_user) AND `status`=".$status." ")->select();
        //$contModel->getLastSql();exit;
        //var_dump($data);exit;
        if(!empty($data)){
            $this->ajaxReturn($data);
        }else{
            $this->ajaxReturn($contModel->getError());
        }
    }

    //查询统计图数据
    function s_data(){

        $pl_name = $_POST['plname'];
        if($pl_name){
            $where = " and data_612='".$pl_name."'";
        }else{
            $where = " and 1=1";
        }
        $jsonData = array();

        //销售合同状态 start
        //总数
        $contModel = M('flow_data_434');
        $count_all = $contModel->field("COUNT(*) AS count_all")->where("data_139='销售合同'".$where." ")->select();
        $sum_all = "";
        foreach($count_all as $val){
            $sum_all = $val['count_all'];
        }
        //已到期
        $count_dq = $contModel->field("COUNT(*) AS count_dq")->where("data_139='销售合同' AND data_111<'".date('Y-m-d')."'".$where." ")->select();
        $sum_dq = "";
        foreach($count_dq as $val){
            $sum_dq = $val['count_dq'];
        }
        //即将到期
        $count_jj = $contModel->field("COUNT(*) AS count_jj")->where("data_139='销售合同' AND DATE_SUB(data_111,INTERVAL 1 MONTH) < '".date('Y-m-d')."'".$where."")->select();
        $sum_jj = "";
        foreach($count_jj as $val){
            $sum_jj = $val['count_jj'];
        }
        //echo $contModel->getLastSql();exit;

        //执行中
        $count_zxz = $contModel->field("COUNT(*) AS count_zxz")->where("data_139='销售合同' AND DATE_SUB(data_111,INTERVAL 1 MONTH) > '".date('Y-m-d')."'".$where."")->select();
        $sum_zxz = "";
        foreach($count_zxz as $val){
            $sum_zxz = $val['count_zxz'];
        }

        $sale_status = array(
            array(
                'name'=>'执行中',
                'value'=>$sum_zxz
                ),
            array(
                'name'=>'即将到期',
                'value'=>$sum_jj
            ),
            array(
                'name'=>'已到期',
                'value'=>$sum_dq
            )
        );
        //end

        //销售合同类型 start
        //常规合同
        $count_cg = $contModel->field("COUNT(*) AS count_cg")->where("data_139='销售合同' AND data_112='常规合同'".$where."")->select();
        $sum_cg = "";
        foreach($count_cg as $val){
            $sum_cg = $val['count_cg'];
        }
        //框架协议
        $count_kj = $contModel->field("COUNT(*) AS count_kj")->where("data_139='销售合同' AND data_112='框架协议'".$where."")->select();
        $sum_kj = "";
        foreach($count_kj as $val){
            $sum_kj = $val['count_kj'];
        }
        //echo $contModel->getLastSql();exit;

        //补充协议
        $count_bc = $contModel->field("COUNT(*) AS count_bc")->where("data_139='销售合同' AND data_112='补充协议'".$where."")->select();
        $sum_bc = "";
        foreach($count_bc as $val){
            $sum_bc = $val['count_bc'];
        }
        $sale_type = array(
            array(
                'name'=>'框架合同',
                'value'=>$sum_kj
            ),
            array(
                'name'=>'常规合同',
                'value'=>$sum_cg
            ),
            array(
                'name'=>'补充协议',
                'value'=>$sum_bc
            )
        );
        //end

        //销售合同每月新增情况
        $count_mon = $contModel->field("COUNT(*) AS count_mon,MONTH(begin_time) as mon")->where("data_139='销售合同'".$where."")->group("MONTH(begin_time)")->select();
        $sale_mon = array();
        foreach($count_mon as $val){
            $sale_mon[$val['mon']] = $val['count_mon'];
        }
        //print_r($sum_mon);
        /*******************************************************************/

        //推广合同状态 start
        //总数
        $contModel = M('flow_data_434');
        $count_all = $contModel->field("COUNT(*) AS count_all")->where("data_139='推广合同'".$where."")->select();
        $sum_all = "";
        foreach($count_all as $val){
            $sum_all = $val['count_all'];
        }
        //已到期
        $count_dq = $contModel->field("COUNT(*) AS count_dq")->where("data_139='推广合同' AND data_111<'".date('Y-m-d')."'".$where."")->select();
        $sum_dq = "";
        foreach($count_dq as $val){
            $sum_dq = $val['count_dq'];
        }
        //即将到期
        $count_jj = $contModel->field("COUNT(*) AS count_jj")->where("data_139='推广合同' AND DATE_SUB(data_111,INTERVAL 1 MONTH) < '".date('Y-m-d')."'".$where."")->select();
        $sum_jj = "";
        foreach($count_jj as $val){
            $sum_jj = $val['count_jj'];
        }
        //echo $contModel->getLastSql();exit;

        //执行中
        $count_zxz = $contModel->field("COUNT(*) AS count_zxz")->where("data_139='推广合同' AND DATE_SUB(data_111,INTERVAL 1 MONTH) > '".date('Y-m-d')."'".$where."")->select();
        $sum_zxz = "";
        foreach($count_zxz as $val){
            $sum_zxz = $val['count_zxz'];
        }

        $promotion_status = array(
            array(
                'name'=>'执行中',
                'value'=>$sum_zxz
            ),
            array(
                'name'=>'即将到期',
                'value'=>$sum_jj
            ),
            array(
                'name'=>'已到期',
                'value'=>$sum_dq
            )
        );
        //end

        //销售合同类型 start
        //常规合同
        $count_cg = $contModel->field("COUNT(*) AS count_cg")->where("data_139='推广合同' AND data_112='常规合同'".$where."")->select();
        $sum_cg = "";
        foreach($count_cg as $val){
            $sum_cg = $val['count_cg'];
        }
        //框架协议
        $count_kj = $contModel->field("COUNT(*) AS count_kj")->where("data_139='推广合同' AND data_112='框架协议'".$where."")->select();
        $sum_kj = "";
        foreach($count_kj as $val){
            $sum_kj = $val['count_kj'];
        }
        //echo $contModel->getLastSql();exit;

        //补充协议
        $count_bc = $contModel->field("COUNT(*) AS count_bc")->where("data_139='推广合同' AND data_112='补充协议'".$where."")->select();
        $sum_bc = "";
        foreach($count_bc as $val){
            $sum_bc = $val['count_bc'];
        }
        $promotion_type = array(
            array(
                'name'=>'框架合同',
                'value'=>$sum_kj
            ),
            array(
                'name'=>'常规合同',
                'value'=>$sum_cg
            ),
            array(
                'name'=>'补充协议',
                'value'=>$sum_bc
            )
        );
        //end

        //推广合同每月新增情况
        $count_mon = $contModel->field("COUNT(*) AS count_mon,MONTH(begin_time) as mon")->where("data_139='推广合同'".$where."")->group("MONTH(begin_time)")->select();
        $promotion_mon = array();
        foreach($count_mon as $val){
            $promotion_mon[$val['mon']] = $val['count_mon'];
        }
        //end

        $jsonData = array(
            'sale_status'=>$sale_status,
            'sale_type'=>$sale_type,
            'sale_mon'=>$sale_mon,
            'promotion_status'=>$promotion_status,
            'promotion_type'=>$promotion_type,
            'promotion_mon'=>$promotion_mon,
            'msg'=>'success'
        );
        if($jsonData){
            $this->ajaxReturn($jsonData);
        }else{
            $jsonData = array(
                'senddata' => array(),
                'msg' => '没有数据了'
            );
        }
    }

    public function ContractExport(){

        $data = D('Contract')->getContract();

        foreach ($data as $key => $value) {
            $data[$key]['start_end']=$value['data_110'].'-'.$value['data_111'];
            if($value['status'] == 1){
                $data[$key]['ht_status']='执行中';
            }else{
                $data[$key]['ht_status']='已结束';
            }
            if(!empty($value['data_50']) && $value['data_50'] !='流程作废') {
                $data[$key]['status'] = '已审核';
            }elseif($value['data_50'] =='流程作废'){
                $data[$key]['status']='已作废';
            }else{
                $data[$key]['status']='待审核';
            }
            if($value['data_7'] == '上饶市网聚天下科技有限公司' or $value['data_7'] == '重庆趣玩科技有限公司' or $value['data_7'] == '上海趣比科技有限公司' or $value['data_7'] == '上海趣比科技有限公司重庆分公司' or $value['data_7'] == '重庆趣玩科技有限公司北京分公司' or $value['data_7']== '上饶网聚天下科技有限公司重庆分公司'){
                $data[$key]['data_7'] = $value['data_7'];
                $data[$key]['data_6'] = $value['data_6'];
            }else{
                $data[$key]['data_7'] = $value['data_6'];
                $data[$key]['data_6'] = $value['data_7'];
            }

        }
        $list=array(array('id','序号'),array('run_id','合同审批编号'),array('run_name','合同名称'),array('data_7','签订主体'),array('data_2','归属部门'),array('data_8','合同类别'),array('data_112','合同类型'),array('data_139','合同性质'),array('data_612','业务线'),array('data_106','申请日期'),array('start_end','合同有效期'),array('data_6','客户名称'),array('data_107','经办人'),array('ht_status','合同状态'),array('status','审批状态'));
        $this->downloadlist($data,$list,'合同列表'.date('Y-m-d'));
    }

    //是否检查状态改变
    public function is_check(){
        $rid = $_POST['rid'];
        $is_check = $_POST['is_check'];
        //echo $rid.'-'.$is_check;
        if(!empty($rid)){

            //修改状态
            $cm = M('flow_data_434');;
            $map = array();
            $map['id']= $rid;
            $map['is_check'] = $is_check;
            if ($cm->save($map) === false) {
                $this->ajaxReturn($cm->getError());
            }else{
                $this->ajaxReturn("TRUE");
            }

        }
    }

    //消息提示单个修改状态 2016.12.20
    function change_one(){

        $fid = $_POST['FID'];//id
        $a_link = $_POST['A_LINK'];//路径

        $map = array();
        $map['id']= $fid;
        $map['status'] = 1;

        $contModel = M('prompt_information');
        if( $contModel->save($map) === false){
            $this->ajaxReturn($contModel->getError());
        }else{
            $this->ajaxReturn("TRUE");
        }
    }
}

