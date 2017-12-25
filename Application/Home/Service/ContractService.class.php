<?php
 /**
 * 合同service 
 */
namespace Home\Service;
use Think\Model;
use Common\Service;
class ContractService extends CommonService
{
	/**
	 * 合同即将到期
	 * @return [type] [description]
	 */
	function synContractExpireSer(){
		$sql = "SELECT 
			  run_id,
			  data_7,
			  data_111,data_6,remark,data_107,data_1
			FROM
			  `boss_flow_data_434` 
			WHERE (
			    (data_6 <> '' 
			      OR data_7 <> '') 
			    AND (
			      data_139 = '销售合同' 
			      OR data_139 = '推广合同'
			    )
			  ) 
			  AND data_111 <= CURDATE() + INTERVAL 1 MONTH 
			  AND data_111 >= NOW() AND (remark=2 OR remark=0 OR remark IS NULL)";
	  	$model = new \Think\Model();
	  	$list = $model->query($sql);
	  	unset($sql);
	  	if(!$list) echo "no datas";
	  	//添加系统通知
	  	//合同审核专员接收
	  	$user_sql = "SELECT user_id,user_name FROM `boss_oa_hr_manage` WHERE duty=47  and status!=1";
	  	$user = $model->query($user_sql);
	  	$user = $user[0];
	  	foreach ($list as $k => $v) {
	  		if($user["user_id"]){
	  			$save              = array();
				$save["date_time"] = date("Y-m-d");

				$hetong_user = $v["data_107"];
				if(is_numeric($v['data_107']) or $v['data_107'] == 0){
					$hetong_user = $v['data_1'];//合同申请人
				}

				//获取伙同责任人id
				$user_ = M("user")->field("id")->where(array("real_name"=>$hetong_user))->find();
				unset($hetong_user);
				$user_ids = "";
				if($user_["id"]){
					$user_ids .= $user_["id"];
				}
				$save["send_user"] = $user["user_id"].",784,".$user_ids;//送达合同审核员--加王荣婷
				unset($user_ids);
				unset($user_);
				$msg               = "您OA号".$v["run_id"]."的合同（".$v["data_6"]."&&".$v["data_7"]."）将于".date("Y年m月d日",strtotime($v["data_111"]))."到期";
				$save["content"]    = $msg;
				$save["status"]    = 0;
				$save["a_link"]    = "/Home/Contract/index.html?run_id=".$v["run_id"];
				$save["oa_number"] = $v["run_id"];
				$save["exp_time"] = $v["data_111"];//过期时间
				$row = M("prompt_information")->add($save);
				unset($save);	
	  		}
			
	  	}
	  	unset($user_sql);
	  	unset($user);
	  	unset($list);
	  	print_r("over");
	}

	/**
	 * 已到期合同通知
	 * @return [type] [description]
	 */
	function synExpiredContractSer(){
		$sql = "SELECT 
				  run_id,
				  data_7,
				  data_111,data_6,remark,data_107,data_1
				FROM
				  `boss_flow_data_434` 
				WHERE (
				    (data_6 <> '' 
				      OR data_7 <> '') 
				    AND (
				      data_139 = '销售合同' 
				      OR data_139 = '推广合同'
				    )
				  ) 
				  AND data_111 <= NOW() 
				  AND remark = 2 AND (remark=2 OR remark=0 OR remark IS NULL)";
	    $model = new \Think\Model();
	  	$list = $model->query($sql);
	  	unset($sql);
	  	if(!$list) echo "no datas";
	  	//添加系统通知
	  	//在职合同审核专员接收
	  	$user_sql = "SELECT user_id,user_name FROM `boss_oa_hr_manage` WHERE duty=47 and status!=1";
	  	$user = $model->query($user_sql);
	  	$user = $user[0];
	  	foreach ($list as $k => $v) {
	  		if($user["user_id"]){
  				$save              = array();
				$save["date_time"] = date("Y-m-d");

				//获取伙同责任人id
				$hetong_user = $v["data_107"];
				if(is_numeric($v['data_107']) or $v['data_107'] == 0){
					$hetong_user = $v['data_1'];//合同申请人
				}

				$user_ = M("user")->field("id")->where(array("real_name"=>$hetong_user))->find();
				unset($hetong_user);
				$user_ids = "";
				if($user_["id"]){
					$user_ids .= $user_["id"];
				}
				$save["send_user"] = $user["user_id"].",784,".$user_ids;//送达合同审核员--加王荣婷
				unset($user_ids);
				unset($user_);
				$msg               = "您OA号".$v["run_id"]."的合同（".$v["data_6"]."&&".$v["data_7"]."）已于".date("Y年m月d日",strtotime($v["data_111"]))."到期";
				$save["content"]    = $msg;
				$save["status"]    = 0;
				$save["a_link"]    = "/Home/Contract/index.html?run_id=".$v["run_id"];
				$save["oa_number"] = $v["run_id"];
				$save["exp_time"] = $v["data_111"];//过期时间
				$row = M("prompt_information")->add($save);
				unset($save);
	  		}
			
	  	}
	  	unset($user_sql);
	  	unset($user);
	  	unset($list);
	  	print_r("over");
	}
}
?>