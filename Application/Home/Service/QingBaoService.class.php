<?php 

/**
* 情报业务逻辑处理
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class QingBaoService
{
	/**
     * 读取我负责的，参与的
     * @return [type] [description]
     */
	function ajaxQinBaoListSer($where_){
		//先读取主情报
		$where     = " where 1=1 ";
		$left_join = "";
		//不是超管权限
		if($_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"]!=200){
			$where .= " and  (r.pri_uid = ".$where_["uid"]." 
				  or r.part_uids LIKE '%".$where_["uid"].",%')";
		}

		if($where_["qb_name"]){
			$where .= " and r.title like '%".$where_["qb_name"]."%'";
		}

		//超管权限
		if($_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"]==200){
			if($where_["qb_bmname"]){
				$where .= " and d.id=".$where_["qb_bmname"];
			}
			$left_join = " LEFT JOIN `boss_user` AS us ON us.id=r.pri_uid 
							LEFT JOIN `boss_user_department` AS d ON d.id = us.`dept_id` ";
		}else{
			//部门负责人id
			if($where_["qb_fzrname"]){
				$pri_uid_ = empty($where_["qb_fzrname"])?"0":$where_["qb_fzrname"];
				$where .= " and r.pri_uid in (".$pri_uid_.")";
			}
		}
		//情报状态
		if($where_["qb_xmstatus"]){
			$where .= " and r.status=".$where_["qb_xmstatus"];
		}
		$sql  = "SELECT 
				  r.id,
				  r.title,r.pri_uid,r.status,r.is_dec
				FROM
				  `boss_main_report` as r {$left_join} {$where}
				 order by r.ctime desc limit ".$where_["limit"];
				 // print_r($sql);exit;
	    $model = new \Think\Model();
	    $list = $model->query($sql);

	    if(!$list){ return false;}

	    //读取用户id集合，部门id集合
	    $user_list_ = array();
	    if($_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"]==200){
	    	$user_list_ = $this->getUserNameAndDepartsTree($list);
	    }else{
	    	$user_list_ = $this->getUserNameTree($list);
	    }

	    //查找主情报是否有子任务
	    $QinBaoStatus_list = C("OPTION.QinBaoStatus");
	    foreach ($list as $k => $v) {
			$list[$k]["username"] = $user_list_[$v["pri_uid"]]["real_name"];
			$list[$k]["status_"] = $QinBaoStatus_list[$v["status"]];
			//判断当前任务的负责人是否为当前用户
			$list[$k]["is_my_pri"] = $where_["uid"] == $v["pri_uid"]?200:500; 
			$list[$k]["is_dec"] = intval($v["is_dec"])>0?$v["is_dec"]:0;
	    }

	    return $list;

	}

	


	/**
	 * getUserNameTree
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function getUserNameAndDepartsTree($list){
		$uid_ids = "";
		foreach ($list as $k => $v) {
			$uid_ids .= $v["pri_uid"].",";
		}
		if($uid_ids){
			$uid_ids = substr($uid_ids,0,strlen($uid_ids)-1);
		}

		$uid_ids = empty($uid_ids)?"0":$uid_ids;

		$model = new \Think\Model();
		$sql = "SELECT 
				  u.id,
				  u.`real_name`,
				  d.`name` 
				FROM
				  `boss_user` AS u 
				  LEFT JOIN `boss_user_department` AS d 
				    ON d.id = u.`dept_id` where u.id in ($uid_ids)";

		$list = $model->query($sql);
		if(!$list){
			return false;
		}
		$userlist_ = array();
		
		foreach ($list as $k => $v) {
			$userlist_[$v["id"]]["real_name"] = $v["name"]."/".$v["real_name"];

		}
		unset($list);
		unset($uid_ids);
		unset($sql);
		return $userlist_;
	}

	/**
	 * getUserNameTree
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function getUserNameTree($list){
		$uid_ids = "";
		foreach ($list as $k => $v) {
			$uid_ids .= $v["pri_uid"].",";
		}
		if($uid_ids){
			$uid_ids = substr($uid_ids,0,strlen($uid_ids)-1);
		}

		$uid_ids = empty($uid_ids)?"0":$uid_ids;

		$userlist = M("user")->field("id,real_name")->where("id in ($uid_ids)")->select();

		if(!$userlist){
			return false;
		}
		$userlist_ = array();
		foreach ($userlist as $k => $v) {
			$userlist_[$v["id"]]["real_name"] = $v["real_name"];
		}

		unset($userlist);
		unset($list);
		unset($uid_ids);
		return $userlist_;
	}

	/**
	 * 获取条数
	 * @return [type] [description]
	 */
	function ajaxQinBaoListCountSer($where_){
		$where     = " 1=1 ";
		$left_join = "";
		if($_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"]!=200){
			$where .= "and (r.pri_uid=".$where_["uid"]."  or r.part_uids LIKE '%".$where_["uid"].",%')";
		}
		
		if($where_["qb_name"]){
			$where .= " and r.title like '%".$where_["qb_name"]."%'";
		}

		//超管权限
		if($_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"]==200){
			if($where_["qb_bmname"]){
				$where .= " and d.id=".$where_["qb_bmname"];
			}
			//关联主情报负责人的部门id
			$left_join = " LEFT JOIN `boss_user` AS us ON us.id=r.pri_uid 
							LEFT JOIN `boss_user_department` AS d ON d.id = us.`dept_id` ";
		}else{
			//部门负责人id
			if($where_["qb_fzrname"]){
				$pri_uid_ = empty($where_["qb_fzrname"])?"0":$where_["qb_fzrname"];
				$where .= " and r.pri_uid in (".$pri_uid_.")";
			}
		}
		//情报状态
		if($where_["qb_xmstatus"]){
			$where .= " and r.status=".($where_["qb_xmstatus"]-1);
		}

		$model = new \Think\Model();
		$sql = "SELECT 
				  count(1) as no 
				FROM
				  `boss_main_report` as r
				  {$left_join}
				WHERE {$where} ";
	    $list = $model->query($sql);
	    if(!$list){ return 0;}

	    $count = $list[0]["no"];
	   	return $count;

	}

	/**
	 * 获取情报任务为处理中的任务列表
	 * @return [type] [description]
	 */
	function getZqbChildTaskListSer_status1($where_,$fields_,$order_="",$limit_="",$data_=array()){
		//判断当前用户是否和主情报负责人一致
		$zqb_fzr_id = 0;
		if($data_["zqb_id"]){
			$zqb_fzr_id = M("main_report")->field("pri_uid")->where(array("id"=>$data_["zqb_id"]))->find();
			$zqb_fzr_id = $zqb_fzr_id["pri_uid"];
		}
		$sql = 'SELECT '.$fields_.'
					FROM
					  `boss_main_task` AS t 
					  LEFT JOIN `boss_user` AS u 
					    ON t.`pri_uid` = u.`id` 
					'.$where_.$order_.$limit_;

		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){ return false;}
		$newslit = $this->getTaskListDeRequrieFieldsList($list);
		foreach ($list as $k => $v) {
			$list[$k]["fields_name"]  = $newslit[$v["id"]]["fields_name"];
			$list[$k]["ctime"]        = date("Y-m-d",strtotime($v["ctime"]));
			if($v["exp_end_time"]){
				$list[$k]["exp_end_time"] = date("Y-m-d",strtotime($v["exp_end_time"]));
			}
			if($v["fact_end_time"]){
				$list[$k]["fact_end_time"] = date("Y-m-d",strtotime($v["fact_end_time"]));
			}
			
			//判断当前子任务和当前登录用户是否一致
			$list[$k]["is_same_pri"] = 1;
			if($v["pri_uid"]==UID){
				$list[$k]["is_same_pri"] = 2;
			}


			//判断当前用户是否和主情报负责人一致
			if($zqb_fzr_id==UID){
				$list[$k]["is_same_pri"] = 2;
			}
		}
		unset($newslit);
		unset($sql);
		return $list;
	}

	/**
	 * 获取子任务的需求字段字符串
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function getTaskListDeRequrieFieldsList($list){
		$task_ids = "";
		foreach ($list as $k => $v) {
			$task_ids .= $v["id"].",";
		}
		if($task_ids){
			$task_ids = substr($task_ids,0,strlen($task_ids)-1);
		}

		$task_ids = empty($task_ids)?"0":$task_ids;


		//获取任务去修字段名称
		$sql = "SELECT 
				  t.`id`,
				  GROUP_CONCAT(f.`name`) as fields_name
				FROM
				  boss_main_task AS t 
				  LEFT JOIN boss_task_require_statis AS r 
				    ON t.id = r.`task_id` 
				  LEFT JOIN boss_task_require_field AS f 
				    ON r.`field_id` = f.`id` 
				     where t.id in (".$task_ids.")
				GROUP BY t.`id` ";
	    $model = new \Think\Model();
	    $list = $model->query($sql);
	    if(!$list){ return false;}
	    $new_list  = array();
	    foreach ($list as $k => $v) {
	    	$new_list[$v["id"]]["fields_name"] = $v["fields_name"];
	    }
	    unset($list);
	    unset($task_ids);
	    unset($sql);
	    return $new_list;
	}

	/**
	 * [saveZdDoSer description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function saveZdDoSer($data){
		$row  = M("task_require_field")->add($data);
		return $row;
	}

	/**
	 * [继续保存任务信息 description]
	 * @return [type] [description]
	 */
	function saveTaskGoOnDoSer($data){
		$data_["head_title"]   = $data["head_title"];
		$data_["pri_uid"]      = $data["pri_uid"];
		$data_["exp_end_time"] = $data["exp_end_time"];
		$data_["qb_ms"]        = $data["qb_ms"];
		$task_obj              = M("main_task");
		$row  = $task_obj->where(array("id"=>$data["id"]))->save($data_);
		unset($data_);

		$res         = array("code"=>500,"msg"=>"保存失败");
		$save_zd_row = 0;


		//先删除之前的字段数据
		$drow = M("task_require_statis")->where(array("task_id"=>$data["id"]))->delete();

		//保存字段数据
		$zd_list = $data["zd_data"];
		foreach ($zd_list as $k => $v) {
			$vList                = explode("__", $v);
			$f_id                 = $vList[0];
			$f_value              = $vList[1];
			
			$data_s["task_id"]    = $data["id"];
			$data_s["ctime"]      = date("Y-m-d H:i:s",time());
			$data_s["field_id"]   = $f_id;
			$data_s["uid"]        = $data["uid"];
			$data_s["demand_val"] = $f_value;
			unset($vList);

			$row = M("task_require_statis")->add($data_s);
			if($row){
				$save_zd_row++;
			}
		}

		//保存情报日志
		$log_               = array();
		$log_["uid"]        = $data["uid"];
		$log_["ctime"]      = date("Y-m-d H:i:s",time());
		$log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."修改任务信息";
		$log_["custome_id"] = $data["id"];
        $row = M("intel_log")->add($log_);
        unset($log_);


		$res = array("code"=>200,"msg"=>"保存成功");
		
		unset($data);
		return $res;
	}

	/**
	 * 记载日志
	 * @return [type] [description]
	 */
	function loadTakLogSer($where_="",$fiels="",$order="",$limit=""){
		$sql = "SELECT 
				  l.content,
				  l.ctime,
				  u.real_name,l.attach_url,l.attach_name
				FROM
				  `boss_intel_log` AS l 
				  LEFT JOIN `boss_user` AS u 
				    ON l.uid = u.id {$where_} {$order} {$limit}";
	    $model = new \Think\Model();
	    $list = $model->query($sql);
		return $list;	
	}

	/**
	 * [ajaxQinBaoListCountSer description]
	 * @param  string $where_ [description]
	 * @return [type]         [description]
	 */
	function ajaxQinBaoListCountSer_riz($where_=""){
		$sql = "SELECT 
				  count(1) as no 
				FROM
				  `boss_intel_log` AS l 
				  LEFT JOIN `boss_user` AS u 
				    ON l.uid = u.id {$where_}";
	    $model = new \Think\Model();
	    $list = $model->query($sql);
		return $list[0]["no"];
	}

	/**
	 * 创建子任务
	 * @return [type] [description]
	 */
	function createZrwSer($param){
		$res                  = array("meg"=>"fail","code"=>500);
		
		//创建一条子任务
		$main_task            = M("main_task");
		$main_task->startTrans();
		$data["head_title"]   = $param["zrw_title"];
		$data["pri_uid"]      = $param["qb_fzr"];
		$data["ctime"]        = date("Y-m-d H:i:s",time());
		$data["uid"]          =  $param["uid"];
		$data["exp_end_time"] = $param["qb_jhwcsj"];
		$data["status"]       = 1;
		$data["pid"]          = $param["parent_task_id"];
		
		//主情报id
		$zqb_id               = $main_task->field("mr_id")->where(array("id"=>$data["pid"]))->find();

		$data["mr_id"]        = $zqb_id["mr_id"];
		$data["last_charge"]  = $data["pri_uid"];
		
		$row                  = $main_task->add($data);
		$save_zd_row          = 0;
		if($row){


			//将当前子任务负责人添加到主情报的参与人中
			$mrInfo               = M("main_report")->field("part_uids")->where(array("id"=>$zqb_id["mr_id"]))->find();
			$data_mr["part_uids"] = $mrInfo["part_uids"].",".$data["pri_uid"];
			M("main_report")->where(array("id"=>$zqb_id["mr_id"]))->save($data_mr);
			unset($data_mr);
			unset($mrInfo);

			//添加字段数据
			$zd_list = $param["zd_data"];
			foreach ($zd_list as $k => $v) {
				$vList                = explode("__", $v);
				$f_id                 = $vList[0];
				$f_value              = $vList[1];
				
				$data_s["task_id"]    = $row;
				$data_s["ctime"]      = date("Y-m-d H:i:s",time());
				$data_s["field_id"]   = $f_id;
				$data_s["uid"]        = $param["uid"];
				$data_s["demand_val"] = $f_value;
				unset($vList);

				$row1 = M("task_require_statis")->add($data_s);
				unset($data_s);
				if($row1){
					$save_zd_row++;
				}
			}

			//添加系统通知
			$add              = array();
			$add['date_time'] = date('Y-m-d H:i:s',time());
			$add['send_user'] = $data["pri_uid"];//子任务负责人id
			$add['content']   = "亲，您有一个待处理的情报任务需要处理！";
			$add['a_link']    = '/Home/IntelligenceSystem/index.html?zqb_id='.$data["mr_id"];
			$add['oa_number'] = $data["mr_id"];
			M('prompt_information')->add($add);
			unset($add);


			//保存情报日志
			$log_               = array();
			$log_["uid"]        = $param["uid"];
			$log_["ctime"]      = date("Y-m-d H:i:s",time());
			$fzr_name = "";
			if($data["pri_uid"]){
				$one      = M("user")->field("real_name")->where(array("id"=>$data["pri_uid"]))->find();
	            $fzr_name = ",并分给了".$one["real_name"];
	            unset($one);
			}
			$log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."创建了子任务".$fzr_name;

			//将当前子任务的父级id主任务Id赋值给日志中
			$log_["custome_id"] = $param["parent_task_id"];
	        $row = M("intel_log")->add($log_);
	        unset($log_);


			$main_task->commit();
			$res = array("meg"=>"success","code"=>200);
		}
		unset($data);
		unset($param);

		return $res;
	}

	/**
	 * 编辑子任务
	 * @return [type] [description]
	 */
	function editZrwSer($data){
		$data_["pri_uid"]      = $data["qb_fzr"];
		$data_["exp_end_time"] = $data["qb_jhwcsj"];
		$data_["head_title"]   = $data["zrw_title"];
		
		$task_obj              = M("main_task");
		$old_one               = $task_obj->field("head_title,pri_uid,last_charge,mr_id")->where(array("id"=>$data["zrw_id"]))->find();
		//判断当前任务的主负责人和上一次的负责人是否相同
        if($old_one['last_charge']!=$data["pri_uid"]){
            //将任务负责人追加主情报参与中,去掉之前主负责人
            $old_zqb = M("main_report")->field("part_uids")->where(array("id"=>$old_one["mr_id"]))->find();
            $cyr_ids = str_replace($old_one['last_charge'].",", $data["pri_uid"].",", $old_zqb["part_uids"]);

            $re_data["part_uids"] = $cyr_ids;
            M("main_report")->where(array("id"=>$old_one["mr_id"]))->save($re_data);
            unset($re_data);
            unset($old_zqb);
            unset($cyr_ids);
        }


		$row  = $task_obj->where(array("id"=>$data["zrw_id"]))->save($data_);
		unset($data_);

		$res         = array("code"=>500,"msg"=>"保存失败");
		$save_zd_row = 0;


		//先删除之前的字段数据
		$drow = M("task_require_statis")->where(array("task_id"=>$data["zrw_id"]))->delete();

		//保存字段数据
		$zd_list = $data["zd_data"];
		foreach ($zd_list as $k => $v) {
			$vList                = explode("__", $v);
			$f_id                 = $vList[0];
			$f_value              = $vList[1];
			
			$data_s["task_id"]    = $data["zrw_id"];
			$data_s["ctime"]      = date("Y-m-d H:i:s",time());
			$data_s["field_id"]   = $f_id;
			$data_s["uid"]        = $data["uid"];
			$data_s["demand_val"] = $f_value;
			unset($vList);

			$row = M("task_require_statis")->add($data_s);
			if($row){
				$save_zd_row++;
			}

		}

		//保存情报日志
		$log_               = array();
		$log_["uid"]        = $data["uid"];
		$log_["ctime"]      = date("Y-m-d H:i:s",time());
		$fzr_name = "";
		if($data["pri_uid"]){
			$one      = M("user")->field("real_name")->where(array("id"=>$data["pri_uid"]))->find();
            $fzr_name = ",并分给了".$one["real_name"];
            unset($one);
		}
		$log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."修改了子任务".$fzr_name;

		//将当前子任务的父级id主任务Id赋值给日志中
		$log_["custome_id"] = $data["parent_task_id"];
        $row = M("intel_log")->add($log_);
        unset($log_);


		$res = array("code"=>200,"msg"=>"保存成功");
		
		unset($data);
		return $res;
	}

	/**
	 * [getZhuQinBaoCountByWhere description]
	 * @param  [type] $where_ [description]
	 * @return [type]         [description]
	 */
	function getZhuQinBaoCountByWhere($where_){
		$sql = "SELECT 
				  COUNT(1) AS no 
				FROM
				  `boss_main_report` AS r 
				  LEFT JOIN `boss_user` AS u 
				    ON r.`pri_uid` = u.`id` {$where_}";
	    $model = new \Think\Model();
	    $list = $model->query($sql);
	    return $list[0]["no"];
	}

	/**
	 * [getZhuQinBaoListByWhere description]
	 * @param  [type] $where_ [description]
	 * @param  string $fields [description]
	 * @param  string $order  [description]
	 * @param  [type] $limit  [description]
	 * @return [type]         [description]
	 */
	function getZhuQinBaoListByWhere($where_,$fields="",$order="",$limit=""){
		$sql = "SELECT 
				  {$fields}
				FROM
				  `boss_main_report` AS r 
				  LEFT JOIN `boss_user` AS u 
				    ON r.`pri_uid` = u.`id` {$where_} order by {$order} limit {$limit} ";
	    $model = new \Think\Model();
	    $list = $model->query($sql);
	    if(!$list){ return false;}
	    $qb_status = C("OPTION.QinBaoStatus");
	    $zhuqinBaoList = $this->getZhuQinBaoTaskCount($list);
	    foreach ($list as $k => $v) {
			$list[$k]["mr_no"]         = $zhuqinBaoList[$v["id"]]["no"];

			if($v["exp_end_time"]){
				$list[$k]["exp_end_time"] = date("Y-m-d",strtotime($v["exp_end_time"]));
			}
			if($v["fact_end_time"]){
				$list[$k]["fact_end_time"] = date("Y-m-d",strtotime($v["fact_end_time"]));
			}
			$list[$k]["sum_status"] = "--";
			if($v["sum_status"]>0){
				$list[$k]["sum_status"] = $v["sum_status"]==1?"成功":"失败";
			}
			
			$list[$k]["ctime"] = date("Y-m-d",strtotime($v["ctime"]));
			$list[$k]["status_str"]    = $qb_status[$v['status']];
	    }
	    return $list;
	}

	/**
	 * [getZhuQinBaoTaskCount description]
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function getZhuQinBaoTaskCount($list){
		$mr_ids = "";
		foreach ($list as $k => $v) {
			$mr_ids .= $v["id"].",";
		}
		if($mr_ids){
			$mr_ids = substr($mr_ids,0,strlen($mr_ids)-1);
		}

		$mr_ids = empty($mr_ids)?"0":$mr_ids;

		$sql = "SELECT 
				  COUNT(1) AS NO,
				  mr_id 
				FROM
				  `boss_main_task`  where mr_id in ({$mr_ids})
				GROUP BY mr_id";
		$model = new \Think\Model();
	    $list = $model->query($sql);
	    $newslit = array();
    	foreach ($list as $k => $v) {
    		$newslit[$v["mr_id"]]["no"] = $v["no"];
    	}
    	unset($list);
    	return $newslit;
	}
	

	/**
	 * [getTaskListSer description]
	 * @return [type] [description]
	 */
	function getTaskListSer($zqb_id){
		$all_task = array();
		$sql = "SELECT 
				  t.id,
				  t.head_title,
				  u.`real_name`,
				  t.mr_id,t.pid
				FROM
				  `boss_main_task` AS t 
				  LEFT JOIN `boss_user` AS u 
				    ON t.pri_uid = u.`id` where t.mr_id={$zqb_id}";
		$model       = new \Think\Model();
		$all_task    = $model->query($sql);
		
		//获取所有的任务的字段
		$all_zd_list = $this->getTaskFieldList($all_task);

		//获取主情报下的所有任务,第一级
		$list = $this->getChildListByPid(0,$all_task);
	    if(!$list){ return false; }

	    foreach ($list as $k => $v) {
			$list[$k]["zd_list"]  = $all_zd_list[$v["id"]]["zd_list"];
			
			//子任务
			$zrw_list             = $this->getChildListByPid($v["id"],$all_task);
			$list[$k]["zrw_list"] = $zrw_list;
    		foreach ($list[$k]["zrw_list"] as $ka => $va) {
    			$list[$k]["zrw_list"][$ka]["zd_list"] = $all_zd_list[$va["id"]]["zd_list"];
    		}
	    }
	    unset($all_zd_list);
	    unset($all_task);
	    unset($sql);
	    return $list;
	}




	/**
	 * [getTaskFieldList description]
	 * @return [type] [description]
	 */
	function getTaskFieldList($list){
		$mr_ids = "";
		foreach ($list as $k => $v) {
			$mr_ids .= $v["id"].",";
		}
		if($mr_ids){
			$mr_ids = substr($mr_ids,0,strlen($mr_ids)-1);
		}

		$mr_ids = empty($mr_ids)?"0":$mr_ids;

		$sql = "SELECT 
				  tr.name,
				  r.demand_val,
				  r.task_id 
				FROM
				  `boss_task_require_statis` AS r 
				  LEFT JOIN `boss_task_require_field` AS tr 
				    ON tr.id = r.field_id where r.task_id in ({$mr_ids})";
		$model = new \Think\Model();
	    $list = $model->query($sql);
	    $newslit = array();
    	foreach ($list as $k => $v) {
    		$newslit[$v["task_id"]]["zd_list"][] = $v;
    	}
    	unset($list);
    	return $newslit;
	}

	/**
	 * [getChildListByPid description]
	 * @param  [type] $pid [description]
	 * @return [type]      [description]
	 */
	function getChildListByPid($pid,$all_task){
		$list = array();
		foreach ($all_task as $k => $v) {
			if($v["pid"]==$pid){
				$list[] = $v;
			}
		}
	    return $list;
	}

	/**
	 * [getGengJinSer description]
	 * @param  [type] $zqb_id [description]
	 * @return [type]         [description]
	 */
	function getGengJinSer($zqb_id){
		//获取主情报下所有的任务id列表
		$rw_list = M("main_task")->field("id")->where(array("mr_id"=>$zqb_id))->select();
		$rw_ids = "";
		foreach ($rw_list as $k => $v) {
			$rw_ids .= $v["id"].",";
		}
		if($rw_ids){
			$rw_ids = substr($rw_ids,0,strlen($rw_ids)-1);
		}
		$rw_ids = empty($rw_ids)?"0":$rw_ids;
		$sql = "SELECT 
				  e.expand_id,
				  e.visit_time,
				  e.visit_way,
				  e.remark,
				  e.result,
				  e.`type_id`,
				  u.`real_name` 
				FROM
				  `boss_expand_follow` AS e 
				  LEFT JOIN `boss_user` AS u 
				    ON e.`follow_uid` = u.`id` where e.expand_id in ($rw_ids) and e.type_id=100";
	    $model = new \Think\Model();
	    $list = $model->query($sql);
	    if(!$list){ return false; }
	    foreach ($list as $k => $v) {
	    	$list[$k]["visit_time"] = date("Y-m-d",strtotime($v["visit_time"]));
	    }
	    return $list;
	}

	/**
	 * 获取情报统计数据
	 * @param  [type] $type [description]
	 * @return [type]       [description]
	 */
	function getQinBaoDataSer($data){
		$res = array("data"=>array());
		switch ($data["type"]) {
			case 1:
				//获取待处理的情报任务
				$res["data"] = $this->getAllQinBaoByStatus($data);
			break;
			
			case 2:
				//获取采集中的情报任务
				$res["data"] = $this->getAllQinBaoByStatus_collecting($data);
			break; 

			case 3:
				//获取所有任务的比例
				$res["data"] = $this->getAllQinBaoTaskPercent($data);
			break; 
			case 4:
				//获取入库的情报任务
				$res["data"] = $this->getAllQinBaoHadRuKu($data);
			break; 
			
		}
		return $res;
	}

	/**
	 * [获取入库的情报任务 description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function getAllQinBaoHadRuKu($data){
		$timeSer   = new Service\DateService();
		$time_list = $timeSer->getMonthWeeks($data["query_month"]);
		$res       = array("zqb_list"=>array(),"zqb_item_list"=>array(),"zqb_average"=>array(),"week_list"=>array(),"code"=>500);


		//不是超管权限
		$where = "";
		if($_SESSION["sec_/Home/IntelligenceSystem/auth_isdepart"]!=200){
			$where .= " and  (pri_uid = ".$data["uid"]." or part_uids LIKE '%".$data["uid"].",%')";
		}



		//获取所有的主情报、所有的主情报的情报项目个数
		$sql = "SELECT 
				  ctime,
				  fact_end_time,id
				FROM
				  `boss_main_report` 
				WHERE STATUS = 3 {$where}";
		// print_r($sql);exit;		
		$model             = new \Think\Model();
		$all_zqb_list      = $model->query($sql);
		if(!$all_zqb_list){ return $res; }
		$res["code"] = 200;
		$all_zqb_item_list = $this->getTaskItemCountByZqbList($all_zqb_list);
		foreach ($time_list as $k => $v) {
			$res["week_list"][] = "第".$k."周";
			$zqb_list_count = 0;//主情报数
			$zqb_item_count = 0;//主情报项目数
			$zqb_diff_days  = 0;
			foreach ($all_zqb_list as $ka => $va) {
				if($va["fact_end_time"]>=$v["starTime"] && $va["fact_end_time"]<=$v["endTime"]){
					//获取满足时间周期的主情报项目数
					$zqb_list_count++;
					$zqb_item_no = $all_zqb_item_list[$va["id"]]["no"];
					if($zqb_item_no>0){
						$zqb_item_count = $zqb_item_count+$zqb_item_no;
					}

					//主情报经历了多少天数
					$diff_days = $timeSer->diffBetweenTwoDays($va["fact_end_time"],$va["ctime"]);
					if($diff_days>0){
						$zqb_diff_days = $zqb_diff_days+$diff_days;
					}
				}
			}
			//当前周期的主情报数
			$res["zqb_list"][]      = $zqb_list_count;
			$res["zqb_item_list"][] = $zqb_item_count;
			$avg_days               = $zqb_diff_days/$zqb_list_count;
			$avg_days = floatval($avg_days)>0?parseFloat2($avg_days):0;
			$res["zqb_average"][]   = $avg_days;
			unset($avg_days);
			unset($zqb_list_count);
			unset($zqb_item_count);
			unset($zqb_diff_days);
		}

		unset($all_zqb_list);
		unset($all_zqb_item_list);
		unset($time_list);
		unset($timeSer);
		unset($model);
		return $res;
	} 

	/**
	 * [getTaskItemCountByZqbList description]
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	private function getTaskItemCountByZqbList($list){
		$mr_ids = "";
		foreach ($list as $k => $v) {
			$mr_ids .= $v["id"].",";
		}
		if($mr_ids){
			$mr_ids = substr($mr_ids, 0,strlen($mr_ids)-1);
		}
		$mr_ids = empty($mr_ids)?"0":$mr_ids;
		$model  = new \Think\Model();
		$sql    = "SELECT 
				COUNT(1) AS no,
				mr_id 
				FROM
				`boss_main_task` 
				WHERE mr_id IN ({$mr_ids}) 
				GROUP BY mr_id ";
		$list   = $model->query($sql);
		
		if(!$list){ return false; }
		$newList = array();
		foreach ($list as $k => $v) {
			$newList[$v["mr_id"]]["no"] = $v["no"];
		}
		unset($list);
		unset($sql);
		return $newList;
	}


	/**
	 * [获取所有任务的比例 description]
	 * @return [type] [description]
	 */
	private function getAllQinBaoTaskPercent($data){
		$where = "";
		if($data["is_super"]==500){
			//读取当前用户负责的，参与的，创建的，
			$where = " where (pri_uid=".$data["uid"]."  or uid=".$data["uid"].")";
		}
		$sql = "SELECT 
				  COUNT(1) AS value,
				  CASE
				    STATUS 
				    WHEN 0 
				    THEN \"待分解\" 
				    WHEN 1 
				    THEN \"跟进中\" 
				    WHEN 2 
				    THEN \"已结束\" 
				    END AS name
				FROM
				  boss_main_task {$where}
				GROUP BY STATUS";
		$model = new \Think\Model();
		$list  = $model->query($sql);
		return $list;

	}


	function getAllQinBaoByStatus_collecting($data){
		$where = "";
		if($data["is_super"]==500){
			//读取当前用户负责的，参与的，创建的，
			$where = " and (t.pri_uid=".$data["uid"]." or r.part_uids like '%".$data["uid"].",%' or r.ct_user=".$data["uid"].")";
		}

		$sql = "SELECT 
				  DATE_FORMAT(t.ctime, '%Y-%c-%d') AS t_time,
				  DATE_FORMAT(r.ctime, '%Y-%c-%d') AS r_ntime,
				  t.id
				FROM
				  `boss_main_report` AS r 
				  LEFT JOIN `boss_main_task` AS t 
				    ON r.id = t.mr_id 
				    WHERE (t.status=1 || t.status IS NULL) AND r.ctime >= DATE_ADD(NOW(),INTERVAL -107 DAY) {$where}";

		$model = new \Think\Model();
		$list  = $model->query($sql);

		if(!$list){ return false;}

		//获取任务列表的字段值
		$nList = $this->getAllTaskFieldValCount($list);
		$res       = array();
		$time_list = array();//时间整理数组
		foreach ($list as $k => $v) {
			if($nList[$v["id"]]["val"]){
				$time               = empty($v["t_time"])?$v["r_ntime"]:$v["t_time"];
				$time_list[$time][] = 1; 
			}
		}

		//第二次筛选数据
		foreach ($time_list as $k => $v) {
			$res["ctime"][] = $k;
			$res["no"][]    = count($v);
		}
		unset($list);
		return $res;
	}

	/**
	 * [获取任务列表的字段值 description]
	 * @return [type] [description]
	 */
	private function getAllTaskFieldValCount($list){
		$task_id = "";
		foreach ($list as $k => $v) {
			$task_id .= $v["id"].",0";
		}
		// if($task_id){
		// 	$task_id = substr($task_id,0,strlen($task_id)-1);
		// }
		$task_id = empty($task_id)?"0":$task_id;
		$sql = "SELECT 
				  GROUP_CONCAT(ts.demand_val) AS rs_val,
				  t.id 
				FROM
				  `boss_main_task` AS t 
				  LEFT JOIN `boss_task_require_statis` AS ts 
				    ON ts.task_id = t.`id` 
				WHERE t.id IN ({$task_id}) 
				GROUP BY t.`ctime` ";

		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){ return false;}
		$nList = array();
		foreach ($list as $k => $v) {
			$nList[$v["id"]]["val"] = $v["rs_val"];
		}
		unset($sql);
		unset($model);
		unset($task_id);
		return $nList;
	}
	
	/**
	 * 获取最近7天的
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function getAllQinBaoByStatus($data){
		$where = "";
		if($data["is_super"]==500){
			//读取当前用户负责的，参与的，创建的，
			$where = " and (t.pri_uid=".$data["uid"]." or part_uids like '%".$data["uid"].",%' or r.ct_user=".$data["uid"].")";
		}

		$sql = "SELECT 
				  COUNT(1) AS NO,
				  DATE_FORMAT(t.ctime, '%Y-%c-%d') AS ntime,
				  DATE_FORMAT(r.ctime, '%Y-%c-%d') AS r_ntime,
				  GROUP_CONCAT(rs.demand_val) AS rs_val 
				FROM
				  `boss_main_report` AS r 
				  LEFT JOIN `boss_main_task` AS t 
				    ON r.id = t.mr_id 
				  LEFT JOIN `boss_task_require_statis` AS rs 
				    ON rs.task_id = t.id 
				WHERE (t.status = 1 || t.status IS NULL) 
				  AND r.ctime >= DATE_ADD(NOW(), INTERVAL - 7 DAY) 
				GROUP BY ntime 
				HAVING rs_val <> '' ";

				
		$sql = "SELECT 
				  COUNT(1) AS no,
				  DATE_FORMAT(t.ctime, '%Y-%c-%d') AS t_time,
				  DATE_FORMAT(r.ctime, '%Y-%c-%d') AS r_ntime 
				FROM
				  `boss_main_report` AS r 
				  LEFT JOIN `boss_main_task` AS t 
				    ON r.id = t.mr_id 
				    WHERE (t.status=".$data["status"]." || t.status IS NULL) AND r.ctime >= DATE_ADD(NOW(),INTERVAL -107 DAY) {$where}
				GROUP BY t_time ";


		$model = new \Think\Model();
		$list  = $model->query($sql);
		if(!$list){ return false;}
		$res = array();
		foreach ($list as $k => $v) {
			$time = empty($v["t_time"])?$v["r_ntime"]:$v["t_time"];
			$res["ctime"][] = date("Y.m.d",strtotime($time));
			$res["no"][] = $v["no"];
		}
		unset($list);
		return $res;
	}

	/**
	 * [GetZRWGenJindata description]
	 */
	function GetZRWGenJindataSer($data){
		$extendSer = new Service\ExtendAdvService();
		$where     = " where e.expand_id=".$data["extid"]." and e.type_id=".$data["type_id"];
		$count     = $extendSer->getFollowListCountByWhere($where,true);
		$listRows  = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page      = new \Think\AjaxPage($count, $listRows,"qb.initGetZRWGenJindata");
		$fields_   = "e.*, us.`real_name`";
		$list      = $extendSer->getFollowListByWhere($where,$fields_," order by e.id desc",$page->firstRow,$page->listRows,true);
		$extendOptions = C('OPTION.extend_status');
		foreach ($list as $k => $v) {
			$list[$k]["visit_time"] = date("Y-m-d",strtotime($v["visit_time"]));
			$list[$k]["status_str"] = $extendOptions[$v["status"]];
		}
		$res       = array("code"=>200,"data"=>$list,"page"=>$page->show());
		unset($extendOptions);
		unset($list);
		unset($fields_);
		return $res;
	}

	/**
	 * [ajaxSureZuQingBaoSer description]
	 * @return [type] [description]
	 */
	function ajaxSureZuQingBaoSer($data){
		$res = array("code"=>"500");
		$mainReport = M("main_report");
		$mainReport->startTrans();
		$row = $mainReport->where(array("id"=>$data["zqb_id"]))->save(array("is_dec"=>$data['issure']));
		$log_                 = array();
		$success_count = 0;//其他成功的条数
		//不分解，就将主情报的信息复制成一个默认任务
		if($data['issure']==2){
			$zqb_info             = $mainReport->field("title,pri_uid")->where(array("id"=>$data["zqb_id"]))->find();
			
			$data_["head_title"]  = $zqb_info["title"];
			$data_["pri_uid"]     = $zqb_info["pri_uid"];
			$data_["mr_id"]       = $data["zqb_id"];
			
			$data_["ctime"]       = date("Y-m-d H:i:s",time());
			$data_["uid"]         = $data['uid'];
			$data_["status"]      = 1;
			$data_["last_charge"] = $zqb_info["pri_uid"];
			$rw_id                = M("main_task")->add($data_);
			if($rw_id){ $success_count++; }
			
			//记录日志
			$log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."确认主情报,不分解主情报";
			$log_["custome_id"] = $rw_id;
	        

            //修改主情报为处理中
            $re_data["status"]    = 2;
           $row_1 =  $mainReport->where(array("id"=>$data_["mr_id"]))->save($re_data);
           if($row_1){ $success_count++; }

            unset($data_);
	        unset($zqb_info);

		}else{
			$log_["content"]    = $_SESSION["userinfo"]["realname"]."在".$log_["ctime"]."确认主情报,并分解主情报";
			$log_["custome_id"] = $rw_id;
		}


		//记录日志
		$log_["uid"]        = $data['uid'];
		$log_["ctime"]      = date("Y-m-d H:i:s",time());
		$row1               = M("intel_log")->add($log_);
		if($row1){ $success_count++; }
        unset($log_);

		if($row && $success_count>0){
			$mainReport->commit();
			$res = array("code"=>"200");
		}else{
			$mainReport->rollback();
		}
		return $res;
	}

	/**
	 * [updateSystemInfoByNum description]
	 * @param  [type] $num [description]
	 * @return [type]      [description]
	 */
	function updateSystemInfoByNum($num){
		$row = M("prompt_information")->where(array("a_link"=>array("like","%/Home/IntelligenceSystem/index%"),"oa_number"=>$num))->save(array("status"=>1));
		return $row;
	}
	
	 
}
?>