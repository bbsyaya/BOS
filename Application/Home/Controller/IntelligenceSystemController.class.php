<?php
/**
 * 情报系统首页
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-10-25
 * Time: 10:51
 */
namespace Home\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 情报系统首页
 */
class IntelligenceSystemController extends BaseController
{   


    /**
     * 首页
     * @return [type] [description]
     */
    public function index(){
        //检查当前用户是否有查看部门权限
        $isHas_check = $_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"];
        if(!$isHas_check){
            $isHas_check = isHasAuthToQuery("/Home/IntelligenceSystem/auth_isdepart",UID);
            $_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"]  = $isHas_check;
        }
        $this->assign('isHas_check',$isHas_check);

        //情报状态
        $this->assign("QinBaoStatus_list",C("OPTION.QinBaoStatus"));

        //默认显示本月
        $map["strtime_"] = date("Y-m",time());
        $this->assign("map",$map);

        //读取消息
        $zqb_id = trim(I("zqb_id"));
        if($zqb_id){
            //判断主情报是否需要被确认
            $this->assign("zqb_id",$zqb_id);

            //是否需要被确认
            $isNeedSure = 500;
            $row = M("main_report")->field("id,pri_uid,is_dec")->where(array("id"=>$zqb_id))->find();
            if($row["pri_uid"]===UID && $row["is_dec"]==0){
                $isNeedSure = 200;
            }else{
                //直接跳转到主流程页面
                echo "<script>window.location.href='/Home/IntelligenceSystem/gatherworkflow.html?qbid=".$zqb_id."';</script>";
                exit;
            }   
            $this->assign("isNeedSure",$isNeedSure);

        }
        


        $this->display();
    }

    /*工作流修改页面*/
    public function update(){

    }

    /**
     *我的项目
     * @return [type] [description]
     */
    function myItem(){
        //获取我负责的，创建的
        $uid    = UID;
        $where_ = " where 1=1 ";
        $where_ .= " and (r.pri_uid={$uid} or r.ct_user={$uid}) ";
        $map['xm_name'] = trim(I("xm_name"));

        if($map['xm_name']){
            $where_ .= " and r.title like '%".$map['xm_name']."%' ";
        }
        $this->assign("map",$map);

        $ser    = new \Home\Service\QingBaoService();
        $count  = $ser->getZhuQinBaoCountByWhere($where_);
        $row    = 10;
        $page   = new \Think\Page($count, $row);
        $fields = "r.id,r.title,r.ctime,r.exp_end_time,r.pri_uid,u.real_name,r.status,r.fact_end_time";
        $limit  = $page->firstRow.",".$page->listRows;
        $list   = $ser->getZhuQinBaoListByWhere($where_,$fields,"fact_end_time desc",$limit);
        unset($where_);
        $this->assign("page",$page->show());
        $this->assign("list",$list);
    	$this->display();
    }

    /**
     * 情报采集工作流
     * @return [type] [description]
     */
    function gatherworkflow(){
        $zqb_id = trim(I("qbid"));
        $issure = trim(I("issure"));
        $ser    = new Service\MainReportService();
        $fields = "id,title,ct_user,pri_uid,status,part_uids,is_dec";
        $zqbOne = $ser->getOneByWhere(array("id"=>$zqb_id),$fields);
        $this->assign("zqbOne",$zqbOne);

        //判断当前是否为主情报的负责人和创建人
        $isNow = false;
        $uid = UID;
        if($zqbOne["ct_user"]==$uid || $zqbOne["pri_uid"]==$uid){
            $isNow = true;
        }

        //允许哪些人可以创建任务（当前主情报的负责人，创建人，参与人）
        $is_part = false;
        $part_uids_ids = explode(",", $zqbOne["part_uids"]);
        if(in_array($uid, $part_uids_ids)){
            $is_part = true;
        }
        $can_create_task = false;
        if($isNow || $is_part){
           $can_create_task = true;
        }

        //
        $this->assign("issure",$issure);
        $this->assign("isNow",$isNow);
        $this->assign("can_create_task",$can_create_task);

        //修改主情报的系统消息为已读
        $ser    = new \Home\Service\QingBaoService();
        $ser->updateSystemInfoByNum($zqb_id);
    	$this->display();
    }

    /**
     * 分配任务
     * @return [type] [description]
     */
    function allotTask(){
        $task_id   = trim(I("task_id"));
        $rw_status = trim(I("rw_status"));
        $one       = M("main_task")->field("id,head_title,pri_uid,ctime,exp_end_time,status,mr_id,qb_ms")->where(array("id"=>$task_id))->find();
        $this->assign("one",$one);

        //读取当前任务是否任务需求字段
        $data_fields = M("task_require_statis")->field("count(1) as no")->where(array("task_id"=>$task_id))->select();
        $this->assign("fdata",$data_fields[0]["no"]);

        //判断当前任务的负责人是否为当前用户
        $is_my_task = 500;
        if($one["pri_uid"] == UID){
            $is_my_task = 200;
        }
        //已完成的任务只能查看 $rw_status==3表示已完成
        $is_query = 500;
        if($rw_status==3){
            $is_my_task = 500;
            $is_query   = 200;
        }
        $this->assign("is_my_task",$is_my_task);
        $this->assign("rw_status",$rw_status);
        $this->assign("is_query",$is_query);
    	$this->display();
    }

    /**
     * 情报库
     * @return [type] [description]
     */
    function intelLibrary(){
        //不是超级管理员无法看到所有的入库信息，只能看到自己参与的和负责的主情报
        $is_super = $_SESSION["sec_/Home/IntelligenceSystem/auth_qingBaoRuKu"];
        if(!$is_super){
            $is_super = isHasAuthToQuery("/Home/IntelligenceSystem/auth_qingBaoRuKu",UID);
            $_SESSION["sec_/Home/IntelligenceSystem/auth_qingBaoRuKu"]  = $is_super;
        }
        
        $where_ = " where 1=1 and r.status=3";
        if($is_super!=200){
            //不是超级管理员只能看自己负责人或者参与的
            $uid = UID;
            $where_ .= " and (r.pri_uid={$uid} or r.ct_user={$uid} or r.part_uids like '%{$uid},%') ";
        }

        $map['xm_name'] = trim(I("xm_name"));
        $map["xm_zt"]   = trim(I("xm_zt"));
        $map["fzr"]     = trim(I("fzr"));

        if($map['xm_name']){
            $where_ .= " and r.title like '%".$map['xm_name']."%' ";
        }
        // if($map['xm_zt']){
        //     $where_ .= " and r.status=".$map['xm_zt'];
        // }
        if($map['fzr']){
            $where_ .= " and r.pri_uid=".$map['fzr'];
        }
        $this->assign("map",$map);

        $ser    = new \Home\Service\QingBaoService();
        $count  = $ser->getZhuQinBaoCountByWhere($where_);
        $row    = 10;
        $page   = new \Think\Page($count, $row);
        $fields = "r.id,r.title,r.ctime,r.exp_end_time,r.pri_uid,u.real_name,r.status,r.fact_end_time,r.sum_status";
        $limit  = $page->firstRow.",".$page->listRows;
        $list   = $ser->getZhuQinBaoListByWhere($where_,$fields,"fact_end_time desc",$limit);
        unset($where_);
        $this->assign("page",$page->show());
        $this->assign("list",$list);
        $this->assign("is_super",$is_super);

    	$this->display();
    }

    /**
     * 情报详情
     * @return [type] [description]
     */
    function taskdetail(){
        $zqb_id = trim(I("zqb_id"));
        $print = trim(I("print"));
        $sql = "SELECT 
                  r.`ctime`,
                  r.id,
                  r.`ct_user`,
                  r.`exp_end_time`,
                  r.`fact_end_time`,
                  r.`part_uids`,
                  r.`pri_uid`,
                  r.`remark`,
                  r.`title`,r.sum_status,
                  u.`real_name` as ct_user,
                  u1.real_name as pri_user,
                  r.int_sum
                FROM
                  `boss_main_report` AS r 
                  LEFT JOIN `boss_user` AS u 
                    ON r.`ct_user` = u.`id` 
                  LEFT JOIN `boss_user` AS u1 
                    ON r.`pri_uid` = u1.`id` where r.id={$zqb_id}";
        $model = new \Think\Model();
        $zqbOne = $model->query($sql);
        $zqbOne = $zqbOne[0];


        $zqbOne["qb_no"] = "QB".str_pad($zqbOne["id"],6,"0",STR_PAD_LEFT);
        $this->assign("zqbOne",$zqbOne);


        //判断当前用户是否为项目总负责人
        $canSum = false;
        if(UID==$zqbOne["pri_uid"]){
            $canSum = true; 
        }
        $this->assign("canSum",$canSum);

        //获取任务信息
        $ser    = new \Home\Service\QingBaoService();
        $rwList   = $ser->getTaskListSer($zqb_id);
        $this->assign("rwList",$rwList);

        $genjinList   = $ser->getGengJinSer($zqb_id);
        $this->assign("genjinList",$genjinList);
        if($print==="yes"){
            $this->assign("webTitle",$zqbOne["title"]);
        }
        $this->assign("print",$print);
    	$this->display();
    }

    /**
     * 创建主情报
     * @return [type] [description]
     */
    function createZqb(){
        $zqb_id = trim(I("zqb_id"));
        $issure = trim(I("issure"));//确认情报
        if($zqb_id>0){
            //编辑
            $fields = "id,ct_user,pri_uid,exp_end_time,remark,part_uids,pri_mode,depart_id,title,sum_status";
            $zqbOne = M("main_report")->field($fields)->where(array("id"=>$zqb_id))->find();
            $this->assign("one",$zqbOne);
        }
        //只能读取
        $issure_ = "";
        if($issure==200){
            $issure_ = "disabled='true'";
        }
        $this->assign("issure_",$issure_);
        $this->assign("issure",$issure);
        $this->display();
    }

    
    /**
     * 主情报参与人
     * @return [type] [description]
     */
    function ajaxZqbCanYuRen(){
        $ser    = new Service\UserService();
        $fields = "id,real_name";
        $list   = $ser->getUserListByWhere(array("status"=>1),$fields);
        $this->ajaxReturn($list);
    }

    /**
     * 创建主情报
     * @return [type] [description]
     */
    function ajaxcreateQb(){
        $zqb_id               = I("zqb_id");
        $data["title"]        = trim(I("zqb_name"));
        $data["pri_uid"]      = trim(I("zqb_fzr"));
        $data["exp_end_time"] = trim(I("zqb_jhwcsj"));
        $data["remark"]       = trim(I("zqb_ms"));
        $zqb_cyr_ids          = $_REQUEST["zqb_cyr_ids"];
        $data["part_uids"]    = implode(",",$zqb_cyr_ids).",";
        
        $ser                  = new Service\MainReportService();
        $caz_str              = "创建";
        if($zqb_id>0){
             $row     = $ser->savemain_reportData(array("id"=>$zqb_id),$data);
             $caz_str = "修改";
        }else{
            $data["ctime"]     = date("Y-m-d H:i:s",time());
            $data["ct_user"]   = UID;
            $data["depart_id"] = $_SESSION["userinfo"]["depart_id"];
            $data["status"]    = 1;
            $zqb_id            = $ser->addData($data);


            //添加通知信息到boss首页，通知参与人和负责人
            // $add              = array();
            // $add['date_time'] = date('Y-m-d H:i:s',time());
            // $add['send_user'] = $data["pri_uid"];//子任务负责人id
            // $add['content']   = "亲，".$_SESSION["userinfo"]["realname"]."给您指派了一个待处理的情报任务您是负责人,需要您处理！";
            // $add['a_link']    = '/Home/IntelligenceSystem/index';
            // M('prompt_information')->add($add);


            $add              = array();
            $add['date_time'] = date('Y-m-d H:i:s',time());
            $add['send_user'] = $data["pri_uid"].",".$data["part_uids"];//子任务负责人id
            $add['content']   = "亲，您有一个情报任务需要处理！";
            $add['a_link']    = '/Home/IntelligenceSystem/index.html?zqb_id='.$zqb_id;
            $add['oa_number'] = $zqb_id;
            M('prompt_information')->add($add);

        }


        //日志记录
        $log_          = array();
        $log_["uid"]   = UID;
        $log_["ctime"] = date("Y-m-d H:i:s",time());
        $fze_name      = "";
        if($data["pri_uid"]){
            $one      = M("user")->field("real_name")->where(array("id"=>$data["pri_uid"]))->find();
            $fze_name = ",负责人是".$one["real_name"];
            unset($one);
        }
        $log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"].$caz_str."主情报".$data["title"].$fze_name;
        $log_["custome_id"] = $zqb_id;
        $this->writeQinBaoLog($log_);
        unset($log_);

        $res                  = array("code"=>200,"id"=>$zqb_id);
        $this->ajaxReturn($res);
    }   

    /**
     * 获取主情报待处理任务列表
     * @return [type] [description]
     */
    function getZqbChildTaskList(){
        $zqb_id = trim(I("zqb_id"));
        $status = trim(I("status"));
        $data["zqb_id"] = $zqb_id;

        $res    = array("code"=>500,"data"=>array());
        $ser    = new \Home\Service\QingBaoService();
        $where  = " where t.mr_id={$zqb_id} and t.status={$status} and t.pid=0";
        $fields = "t.id,t.head_title,t.pri_uid,t.ctime,t.uid,t.exp_end_time,u.real_name,t.fact_end_time";
        $list   = $ser->getZqbChildTaskListSer_status1($where,$fields," order by t.ctime desc","",$data);
        if($list){
                $res = array("code"=>200,"data"=>$list);
        }
        $this->ajaxReturn($res);

    }

    /**
     * 读取我负责的，参与的
     * @return [type] [description]
     */
    function ajaxQinBaoList(){
        ignore_user_abort();//脱离客户端
        set_time_limit(0);//不限时间执行
        session_write_close();//session解锁
        $ser                  = new \Home\Service\QingBaoService();
        $res                  = array("code"=>500,"data"=>array());
        $where["qb_name"]     = trim(I("qb_name"));
        $where["qb_bmname"]   = trim(I("qb_bmname"));
        $where["qb_fzrname"]  = trim(I("qb_fzrname"));
        $where["qb_xmstatus"] = trim(I("qb_xmstatus"));
        $where["uid"]         = UID;

        $count          = $ser->ajaxQinBaoListCountSer($where);
        $row            = 5;
        $page           = new \Think\AjaxPage($count, $row, "In.getQbList");
        $show           = $page->show();
        $where["limit"] = $page->firstRow . ',' . $page->listRows;
        $list           = $ser->ajaxQinBaoListSer($where);
        if($list){
            $res = array("code"=>200,"data"=>$list,"page"=>$show);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 读取部门或者负责人
     * @return [type] [description]
     */
    function getBumenOrFuzeren(){
        ignore_user_abort();//脱离客户端
        set_time_limit(0);//不限时间执行
        session_write_close();//session解锁
        $is_super  = trim(I("is_super"));
        $res = array("code"=>500,"data"=>array());
        if($is_super==200){
            //读取部门
            $departSer = new Service\DepartSettingService();
            //指派部门：是业务中心的，如事业发展部、营销部、采购部,只需要一级部门 168,171,175,189,191,204,176,202,172,173,174,192,193
            //没有研发和后勤 、运营，技术部
            // $where_    = "1=1 and type=0 and id in (168,171,172,173,174,175)";
            $where_ = "1=1 and type=0 ";
            $list   = $departSer->getListByWhere($where_,"id,name","sort desc");
            $res    = array("code"=>200,"data"=>$list);
        }else{
            $ser    = new Service\UserService();
            $fields = "id,real_name as name";
            $list   = $ser->getUserListByWhere(array("status"=>1),$fields);
            $res    = array("code"=>200,"data"=>$list);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 显示待处理任务
     */
    function ShowDclTask(){
        $id = trim(I("id"));
        $zqb_id = trim(I("zqb_id"));
        if($id>0){
            //编辑
            $one = M("main_task")->field("id,head_title,pri_uid,mr_id")->where(array("id"=>$id))->find();
            $this->assign("one",$one);
        }
        $this->assign("zqb_id",$zqb_id);
        $this->display();
    }

    /**
     * 保存、编辑任务
     * @return [type] [description]
     */
    function saveRwDo(){
        $rw_id              = trim(I("id"));
        $data["head_title"] = trim(I("rw_yj_title"));
        $data["pri_uid"]    = trim(I("rw_fze"));
        $data["mr_id"]      = trim(I("mr_id"));

        $operation_str = "创建";
        if($rw_id>0){
            $old_one       = M("main_task")->field("head_title,pri_uid,last_charge")->where(array("id"=>$rw_id))->find();

            //判断当前任务的主负责人和上一次的负责人是否相同
            if($old_one['last_charge']!=$data["pri_uid"]){
                //将任务负责人追加主情报参与中,去掉之前主负责人
                $old_zqb = M("main_report")->field("part_uids")->where(array("id"=>$data["mr_id"]))->find();
                $cyr_ids = str_replace($old_one['last_charge'].",", $data["pri_uid"].",", $old_zqb["part_uids"]);

                $re_data["part_uids"] = $cyr_ids;
                M("main_report")->where(array("id"=>$data["mr_id"]))->save($re_data);
                unset($re_data);
                unset($old_zqb);
                unset($cyr_ids);
            }

            //编辑--保存数据
            $data["last_charge"] = $data["pri_uid"];
            $row                 = M("main_task")->where(array("id"=>$rw_id))->save($data);
            $operation_str       = "编辑";


        }else{

            //添加
            $data["ctime"]        = date("Y-m-d H:i:s",time());
            $data["uid"]          = UID;
            $data["status"]       = trim(I("isnext"));
            $data["last_charge"]  = $data["pri_uid"];
            $rw_id                = M("main_task")->add($data);

            //将任务负责人追加主情报参与中
            $old_zqb              = M("main_report")->field("part_uids")->where(array("id"=>$data["mr_id"]))->find();
            $re_data["part_uids"] = $old_zqb["part_uids"].",".$data["pri_uid"];
            //修改主情报为处理中
            $re_data["status"]    = 2;
            M("main_report")->where(array("id"=>$data["mr_id"]))->save($re_data);

        }

        //记录日志
        $log_          = array();
        $log_["uid"]   = UID;
        $log_["ctime"] = date("Y-m-d H:i:s",time());
        $fze_name      = "";
        if($data["pri_uid"]){
            $one      = M("user")->field("real_name")->where(array("id"=>$data["pri_uid"]))->find();
            $fze_name = ",指派给了".$one["real_name"];
            unset($one);
        }
        $log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"].$operation_str."任务".$data["head_title"].$fze_name;
        $log_["custome_id"] = $rw_id;

        $this->writeQinBaoLog($log_);
        unset($log_);


        //添加一条前台通知信息
        $add              = array();
        $add['date_time'] = date('Y-m-d H:i:s',time());
        $add['send_user'] = $data["pri_uid"];//子任务负责人id
        $add['content']   = "亲，".$_SESSION["userinfo"]["realname"]."给您指派了一个待处理的情报任务需要您处理！";
        $add['a_link']    = '/Home/IntelligenceSystem/index.html?zqb_id='.$data["mr_id"];
        $add['oa_number'] = $data["mr_id"];
        M('prompt_information')->add($add);
        unset($add);


        unset($data);
        $res = array("code"=>500,"data"=>array());
        $this->ajaxReturn($res);
    }

    /**
     * [updateTaskStatus description]
     * @param  [type] $task_id [description]
     * @param  [type] $status  [description]
     * @return [type]          [description]
     */
    function updateTaskStatusDo(){
        $id            = trim(I("id"));
        $status        = trim(I("status"));
        $row           = M("main_task")->where(array("id"=>$id))->save(array("status"=>$status));
        
        $log_          = array();
        $log_["uid"]   = UID;
        $log_["ctime"] = date("Y-m-d H:i:s",time());
        $content       = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."将任务转交为处理中";
        if($status==2){
            //记录完成时间
            M("main_task")->where(array("id"=>$id))->save(array("fact_end_time"=>date("Y-m-d H:i:s",time())));
            $content = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."将任务转交为已完成";
        }

        //记录日志
        $log_["content"]    = $content;
        $log_["custome_id"] = $id;
        $this->writeQinBaoLog($log_);
        unset($log_);

        $res    = array("code"=>200,"data"=>array());
        $this->ajaxReturn($res);
    }

   /**
     * 记录情报日志
     * @return [type] [description]
     */
    function writeQinBaoLog($data){
        $row = M("intel_log")->add($data);
        unset($data);
    }

    /**
     * 加载需求字段
     * @return [type] [description]
     */
    function getTaskQuQiuZiDuan(){
        $fields_data_count = I("fields_data_count");
        $task_id           = trim(I("task_id"));
        $res               = array("code"=>200,"data"=>array(),"myfieldsdata"=>array());
        if($fields_data_count>0){
            //读取当前任务的
            $where = " where tr.task_id=".$task_id;
            $sql  = "SELECT 
                      tr.`demand_val`,
                      tr.`field_id`,
                      f.`name` 
                    FROM
                      `boss_task_require_statis` AS tr 
                      LEFT JOIN `boss_task_require_field` AS f 
                        ON tr.`field_id` = f.`id` {$where}";
            $model = new \Think\Model();
            $mydata = $model->query($sql);
            unset($where);
            $res["myfieldsdata"] = $mydata;
            unset($myData);
        }
        $list        = M("task_require_field")->field("id,name")->order("id desc")->select();
        $res["data"] = $list;
        $this->ajaxReturn($res);
    }

    /**
     * 继续保存任务
     * @return [type] [description]
     */
    function saveTaskGoOnDo(){
        $data["id"]           = trim(I("id"));
        $data["head_title"]   = trim(I("qb_title"));
        $data["pri_uid"]      = trim(I("qb_fzr"));
        $data["exp_end_time"] = trim(I("qb_jhwcsj"));
        $data["qb_ms"]        = trim(I("qb_ms"));
        $data["uid"]          = UID;
        $data["zd_data"]      = $_REQUEST["zd_data"];
        //保存字段数据
        $ser = new \Home\Service\QingBaoService();
        $res = $ser->saveTaskGoOnDoSer($data);
        $this->ajaxReturn($res);
    }

    /**
     * 保存字段
     * @return [type] [description]
     */
    function saveZdDo(){
        $data["name"] = trim(I("xz_zd_name"));
        $res          = array("code"=>500,"id"=>array(),"msg"=>"");
        //判断是否重复
        $count        = M("task_require_field")->where(array("name"=>$data["name"]))->count();
        if($count>0){
            $res  = array("code"=>600,"id"=>array(),"msg"=>"情报字段已存在,您搜一搜任务需求");
        }else{
            $data["ctime"] = date("Y-m-d H:i:s",time());
            $data["uid"]   = UID;
           
            $ser           = new \Home\Service\QingBaoService();
            $row           = $ser->saveZdDoSer($data);
            $res["id"]     = $row;
           

            //保存情报日志
            $log_               = array();
            $log_["uid"]        = $data["uid"];
            $log_["ctime"]      = date("Y-m-d H:i:s",time());
            $log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."添加新字段".$data["name"];
            $log_["custome_id"] = trim(I("hd_task_id"));
            $this->writeQinBaoLog($log_);
            unset($log_);
        }
         unset($data);
        
        $this->ajaxReturn($res);
    }

    /**
     * 记载日志
     * @return [type] [description]
     */
    function loadTakLog(){
        $task_id = trim(I("task_id"));
        $where   = " where custome_id={$task_id}";
        $ser     = new \Home\Service\QingBaoService();
        $count   = $ser->ajaxQinBaoListCountSer_riz($where);
        $row     = 5;
        $page    = new \Think\AjaxPage($count, $row, "qb.loadLog");
        $limit   = " limit ".$page->firstRow . ',' . $page->listRows;
        $show    = $page->show();
        $list    =  $ser->loadTakLogSer($where,"","order by ctime desc",$limit);
        $res     = array("code"=>200,"data"=>$list,"page"=>$show);
        $this->ajaxReturn($res);
    }

    /**
     * 保存日志留言
     */
    function addLogMesSer(){
        $hasimg       = trim(I("hasimg"));
        $hd_rw_status = trim(I("hd_rw_status"));
        $hd_task_id   = trim(I("hd_task_id"));
        $res          = array("code"=>500,"id"=>array(),"msg"=>"");
        $data         = array();
        $url_         = "/Home/IntelligenceSystem/allotTask.html?task_id={$hd_task_id}&rw_status={$hd_rw_status}";
        if($hasimg==200){
            $file_url = $this->upAjaxAttach();
            if($file_url["status"]!=1){
                $res["msg"] = $file_url["msg"];
                $this->redirect("Public/layerAlert",array("title"=>$res["msg"],"url"=>$url_));
                exit;
            }
            $data["attach_url"] = $file_url["file_path"];
            $ysfilename         = trim(I("ysfilename"));
            $index_             = strrpos($ysfilename,"\\");
            if($index_){
                $ysfilename = substr($ysfilename, $index_+1);
            }
            $data["attach_name"]      = $ysfilename;

        }
        
        $data["ctime"]      = date("Y-m-d H:i:s",time());
        $data["uid"]        = UID;
        $data["content"]    = trim(I("msgxx"));
        $data["custome_id"] = $hd_task_id;
        $row = M("intel_log")->add($data);
        unset($data);
        echo "<script>window.location.href='{$url_}';</script>";
        exit;
    }

    function addZrwPage(){
        $zrw_id   = I("zrw_id");
        $is_query = trim(I("is_query"));
        if($zrw_id>0){
            //编辑子任务
            $fields                  = "id,pri_uid,ctime,uid,exp_end_time,status,mr_id,head_title";
            $zrw_one                 = M("main_task")->field($fields)->where(array("id"=>$zrw_id))->find();
            $zrw_one["exp_end_time"] = date("Y-m-d",strtotime($zrw_one["exp_end_time"]));
            $this->assign("zrw_one",$zrw_one);

            //读取当前任务是否任务需求字段
            $data_fields = M("task_require_statis")->field("count(1) as no")->where(array("task_id"=>$zrw_id))->select();
            $this->assign("fdata",$data_fields[0]["no"]);
        }
        $parent_task_id = trim(I("parent_task_id"));
        $this->assign("parent_task_id",$parent_task_id);
        $this->assign("is_query",$is_query);
        $this->assign("zrw_id",$zrw_id);
        $is_readonly="";
        if($is_query==1){
            $is_readonly="disabled='true'";
        }
        $this->assign("is_readonly",$is_readonly);
        $this->display();
    }

    /**
     * 保存子任务数据
     * @return [type] [description]
     */
    function saveZrwData(){
        $data["zrw_title"]      = trim(I("zrw_title"));
        $data["zrw_id"]         = trim(I("zrw_id"));
        $data["qb_fzr"]         = trim(I("qb_fzr"));
        $data["qb_jhwcsj"]      = trim(I("qb_jhwcsj"));
        $data["parent_task_id"] = trim(I("parent_task_id"));
        $data["uid"]            = UID;
        $data["zd_data"]        = $_REQUEST["zd_data"];

        $res = array();
        $ser = new \Home\Service\QingBaoService();
        if($data["zrw_id"]>0){
            //编辑子任务
            $res = $ser->editZrwSer($data);
        }else{
            //创建子任务
            $res = $ser->createZrwSer($data);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 获取子任务list
     */
    function GetZRWDdata(){
        $data["parent_task_id"] = trim(I("parent_task_id"));
        $ser                    = new \Home\Service\QingBaoService();
        $where                  = " where t.pid=".$data["parent_task_id"];
        $fields                 = "t.id,t.head_title,t.pri_uid,t.ctime,t.uid,t.exp_end_time,u.real_name";
        $list                   = $ser->getZqbChildTaskListSer_status1($where,$fields," order by t.ctime desc");
        $res                    = array("code"=>200,"data"=>$list);
        $this->ajaxReturn($res);
    }

    /**
     * 是否可以入库
     * @return [type] [description]
     */
    function canRuKu(){
        $zqb_id = I("zqb_id");
        $row    = M("main_task")->field("count(1) as no")->where(array("mr_id"=>$zqb_id,"status"=>array("in","0,1")))->select();
        $row    = $row[0]["no"];
        $res    = array("code"=>200,"no"=>$row);
        $this->ajaxReturn($res);
    }

    /**
     * [goRuKu description]
     * @return [type] [description]
     */
    function goRuKu(){
        $zqb_id   = I("zqb_id");
        $type     = I("type");
        $end_time = date("Y-m-d H:i:s",time());
        if($type=="500"){
            //修改主情报的所有的状态
            $row = M("main_task")->where(array("mr_id"=>$zqb_id))->save(array("status"=>2,"fact_end_time"=>$end_time));
        }
        $row = M("main_report")->where(array("id"=>$zqb_id))->save(array("status"=>3,"fact_end_time"=>$end_time));
        $res    = array("code"=>200,"no"=>$row);
        $this->ajaxReturn($res);
    }

    /**
     * [getTaskList description]
     * @return [type] [description]
     */
    function getTaskList(){
        $zqb_id = trim(I("zqb_id"));
        $ser    = new \Home\Service\QingBaoService();
        $list   = $ser->getTaskListSer($zqb_id);
        $res    = array("code"=>200,"list"=>$list);
        $this->ajaxReturn($res);
    }

    /**
     * 读取跟进信息
     * @return [type] [description]
     */
    function getGengJin(){
        $zqb_id = trim(I("zqb_id"));
        $ser    = new \Home\Service\QingBaoService();
        $list   = $ser->getGengJinSer($zqb_id);
        $res    = array("code"=>200,"list"=>$list);
        $this->ajaxReturn($res);
    }

    /**
     * [saveSumDo description]
     * @return [type] [description]
     */
    function saveSumDo(){
        $zqb_id  = trim(I("zqb_id"));
        $int_sum = trim(I("int_sum"));
        $row     = M("main_report")->where(array("id"=>$zqb_id))->save(array("int_sum"=>$int_sum));
        $res     = array("code"=>200,"msg"=>"保存成功");
        $this->ajaxReturn($res);
    }

    /**
     * 获取情报统计数据
     * @return [type] [description]
     */
    function getQinBaoData(){
        ignore_user_abort();//脱离客户端
        set_time_limit(0);//不限时间执行
        session_write_close();//session解锁
        $data["type"]     = trim(I("type"));
        $data["is_super"] = trim(I("is_super"));
        $data["uid"]      = UID;
        $data["status"]   = trim(I("status"));
        if(I("query_month")){
            $data["query_month"] = trim(I("query_month"));
        }
        $data["status"]   = trim(I("status"));
        $ser              = new \Home\Service\QingBaoService();
        $res              = $ser->getQinBaoDataSer($data);
        unset($data);
        $this->ajaxReturn($res);
    }

    /**
     * 上传附件
     * @return [type] [description]
     */
    function upAjaxAttach(){
        $dir       = "./upload/charlog/";
        if(!(is_dir($dir) && is_writable($dir))){
            mkdir('./upload/charlog/',0777,true);
        }
        $info      = $this->uplaodfile_public_("files",$dir);
        // print_r($info);exit;
        $file_path = $dir.$info["files"]["savepath"].$info["files"]["savename"];
        $file_path = ltrim($file_path,".");
        $list      = array("msg"=>"上传失败","data"=>"","status"=>0);
        if($info){
            $list["msg"]    = "上传成功";
            $list["status"] = 1;
            $list["file_path"] = $file_path;
        }
        // print_r($list);exit;
        return $list;
        // $this->ajaxReturn($list);
    }

    function uplaodfile_public_($name,$dir){
        $upload           = new \Think\Upload();// 实例化上传类
        $upload->maxSize  =     99999 ;// 设置附件上传大小
        $upload->rootPath =     $dir; // 设置附件上传根目录
        $upload->savePath =     ''; // 设置附件上传（子）目录

        // 上传文件
        $upload->__set('saveName',time().rand(100,999));
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            return $upload->getError();
        }else{// 上传成功
            return $info;
        }
    }

    /**
     * 评定结果
     * @return [type] [description]
     */
    function saveSumStatusDo(){
        $data["sum_status"] = trim(I("sum_status"));
        $res                = array("msg"=>"评判失败","status"=>500);
        if($data["sum_status"]<=0){
            $this->ajaxReturn($res);
            exit;
        }

        $zqb_id = trim(I("zqb_id"));
        $row    = M("main_report")->where(array("id"=>$zqb_id))->save($data);
        $res    = array("msg"=>"评判成功","status"=>200);
        unset($data);
        $this->ajaxReturn($res);
    }
    /**
     * 打印
     * @return [type] [description]
     */
    function wprint(){
        $this->display();
    }

    /**
     * [获取跟进信息 description]
     */
    function GetZRWGenJindata(){
        $ser             = new \Home\Service\QingBaoService();
        $data["extid"]   = trim(I("parent_task_id"));
        $data["type_id"] = trim(I("type_id"));
        $list            = $ser->GetZRWGenJindataSer($data);
        unset($data);
        $this->ajaxReturn($list);
    }

    /**
     * 确认主情报
     * @return [type] [description]
     */
    function ajaxSureZuQingBao(){
        $ser            = new \Home\Service\QingBaoService();
        $data["issure"] = trim(I("issure"));
        $data["zqb_id"] = trim(I("zqb_id"));
        $data["uid"]    = UID;
        $list           = $ser->ajaxSureZuQingBaoSer($data);
        unset($data);
        $this->ajaxReturn($list);
    }


}