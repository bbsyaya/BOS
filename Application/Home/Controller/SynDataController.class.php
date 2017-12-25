<?php
namespace Home\Controller;
use Think\Controller;
use Common\Service;

/**
* 同步数据专用
*/
class SynDataController extends Controller
{
	private $rec_url = "http://dist.youxiaoad.com/api.php/Alimamastop/alimamaRecovery";
	/**
	 * 同步停止的计费标识到分发
	 * @return [type] [description]
	 */
	function synJFBSToFenFa(){
		$token = trim(I("token"));
		if($token){
			$page      = empty(trim(I("page"))) ? 1 :trim(I("page"));
			$row       = empty(trim(I("row"))) ? 10 : trim(I("row"));
			$start_row = ($page-1)*$row;
			$sql       = "  SELECT id,prot_id FROM boss_charging_logo ORDER BY id DESC LIMIT {$start_row},{$row}";
			$model     = new \Think\Model();
			$list      = $model->query($sql);
			$detModel  = M('charging_logo_assign');
			$clModel   = M('charging_logo');
			$nlist = array();
			if(!$list){ echo "no list data!";exit;}
			foreach ($list as $k => $v) {
				$cur = $detModel->where(array("cl_id"=>$v["id"]))->order('id DESC')->limit(1)->find();
				if($cur["status"]==3){
					$json_data['promotion_id'] = $v["id"];
					$json_data['sup_id']       = $cur['sup_id'];
					$json_data['pr_id']        = $v["prot_id"];
					$json_data['bos_jfid']     = $cur['id'];
					$json_data['start_date']   = $cur['promotion_stime'];
					$json_data['end_date']    = date('Y-m-d');
					$nlist[]                   = $json_data;
				}else{
					continue;
				}
			}
			if($nlist){
				$appsecret    = "b#asb%svp&^";
				$data['ts']   = time();
				$data['sign'] = md5($appsecret.$data['ts']);
				$data['data'] = json_encode($nlist);
				$responsData  = bossPostData($this->rec_url, $data);//同步至分发

				//记录日志
				$data_l["postUrl"]  = $this->rec_url;
				$data_l["postData"] = json_encode($data,JSON_UNESCAPED_UNICODE);
				$data_l["data"]     = $responsData;
				$data_l["type"]     = 101;
				$finLogSer          = new Service\FinanceLogService();
				$finLogSer->writeLog($data_l);
				print_r(json_decode($data_l["data"],true));
				echo count($nlist)." tiao zhi xing over<br/>";
			}else{
				echo "nlist is null<br/>";
			}
		}

		echo "over";
		
	}

	/**
	 * 同步oa到boss_user
	 * 表服务器：localhost:3336 
	 *  username:oa_read   pass: oa_read  数据库：TD_OA
	 * @return [type] [description]
	 */
	function synUserInfo(){
		$token = trim(I("token"));
		if($token=="token023"){
			$sql = "SELECT 
					  u.id,
					  u.`username`,
					  u.`real_name`,
					u.uid,u.password,u.oa_passpwd,
					  o.`UID`  as oa_uid,
					  o.`USER_NAME`,
					  o.`PASSWORD`  as oa_pwd
					FROM
					  `boss_user` AS u 
					  LEFT JOIN `boss_oa_user` AS o 
					    ON  u.`real_name` = o.`user_name`
					WHERE u.`dept_id` > 0 AND u.`status`=1 and (u.oa_passpwd is null or u.oa_passpwd='')
					ORDER BY id DESC";
			$model = new \Think\Model();
			$list  = $model->query($sql);
			if(!$list){
				echo "no -data!";exit;
			}
			foreach ($list as $k => $v) {
				//更新数据
				$data               = array();
				$data["oa_passpwd"] = $v["oa_pwd"];
				$data["uid"]        = $v["oa_uid"];
				$row = M("user")->where(array("id"=>$v["id"]))->save($data);
				if($row){
					echo $v["id"]."--更新成功<br>";
				}else{
					echo $v["id"]."--更新失败<br>";
				}
			}
		}
		echo "over";
	}


	/**
	 * 同步用户一级部门
	 * @return [type] [description]
	 */
	function synUserLevel(){
		$token = I("token");
		if($token==="token023"){
			$list = M("oa_hr_manage")->field("depart_id,user_id,leve_depart_id,id,depart_name")->select();
			foreach ($list as $k => $v) {

				//排除董事会.总裁办,财务部,风控部,人力行政部,品牌公关部
				if($v["depart_name"]=="总裁办" || $v["depart_name"]=="财务部" || $v["depart_name"]=="风控部" || $v["depart_name"]=="人力行政部" || $v["depart_name"]=="品牌公关部"){
					$data = array();
					$data["leve_depart_id"] = $v["depart_id"];
					$row = M("oa_hr_manage")->where(array("id"=>$v["id"]))->save($data);
					if($row){
						echo $v["id"]."--hr update success;";
					}else{
						echo $v["id"]."--hr update fail;";
					}
					$data = array();
					//同步user
					$data["leve_depart_id"] = $v["depart_id"];
					$row = M("user")->where(array("id"=>$v["user_id"]))->save($data);
					if($row){
						echo $v["user_id"]."--user update success;<br>";
					}else{
						echo $v["user_id"]."--user update fail;<br>";
					}
				}else{
					//获取pid的id
					$deprt = M("user_department")->field("id,pid")->where(array("id"=>$v["depart_id"]))->find();
					if($deprt){
						$data = array();
						$data["leve_depart_id"] = $deprt["pid"];
						$row = M("oa_hr_manage")->where(array("id"=>$v["id"]))->save($data);
						if($row){
							echo $v["id"]."--hr update success;";
						}else{
							echo $v["id"]."--hr update fail;";
						}
						$data = array();
						//同步user
						$data["leve_depart_id"] = $deprt["pid"];
						$row = M("user")->where(array("id"=>$v["user_id"]))->save($data);
						if($row){
							echo $v["user_id"]."--user update success;<br>";
						}else{
							echo $v["user_id"]."--user update fail;<br>";
						}
					}
				}
			}
		}
		echo "over";
		
	}

	/**
	 * 同步工号--将user表中的uid同步到hr_manager表中job_no(工号)
	 * @return [type] [description]
	 */
	function synHrJobNo(){
		$token = trim(I("token"));
		if($token=="token023"){
			$list = M("oa_hr_manage")->field("user_id,job_no,id")->select();
			if(!$list){ echo "no data!";exit;}
			foreach ($list as $k => $v) {
				$user_ = M("user")->field("id,uid")->where(array("id"=>$v["user_id"]))->find();
				if($user_){
					$hr_data["job_no"] = $user_["uid"];
					$row = M("oa_hr_manage")->where(array("id"=>$v["id"]))->save($hr_data);
					unset($hr_data);
					if($row){
						echo $v["id"]."--success<br/>";
					}else{
						echo $v["id"]."--fail<br/>";
					}
				}else{
					echo $v["id"]."--no user<br/>";
				}
			}
			unset($list);
		}
		echo "over";
	}

	/**
	 * 修复数据
	 * @return [type] [description]
	 */
	function synSupply(){
		$token = trim(I("token"));
		if($token=="token023"){
			$list = M("supplier_2017072017h")->field("tag,id")->select();
			if(!$list){ echo "no data!";exit;}
			foreach ($list as $k => $v) {
				$data["tag"] = $v["tag"];
				$row = M("supplier")->where(array("id"=>$v["id"]))->save($data);
				if($row){
					echo $v["id"].'--'.$v["tag"].'--'."--success<br/>";
				}else{
					echo $v["id"].'--'.$v["tag"].'--'."--fail<br/>";
				}
			}
			unset($list);
		}
		echo "over";
	}

	/**
	 * 修复邮箱
	 * @return [type] [description]
	 */
	function synEmail(){
		$token = trim(I("token"));
		if($token=="token023"){
			$list = M("oa_hr_manage")->field("id,username_pinyin")->select();
			foreach ($list as $k => $v) {
				$data["post_email"] = $v["username_pinyin"]."@yandui.com";
				$row = M("oa_hr_manage")->where(array("id"=>$v["id"]))->save($data);
				if($row){
					echo $v["id"].'--'.$data["post_email"].'--'."--success<br/>";
				}else{
					echo $v["id"].'--'.$data["post_email"].'--'."--fail<br/>";
				}
				unset($data);
			}
		}
		echo "over";
	}


	/**
	 * 修复司龄
	 * @return [type] [description]
	 */
	function synCompanyAge(){
		$token = trim(I("token"));
		if($token=="token023"){
			$list = M("oa_hr_manage")->field("id,entry_time")->select();
			foreach ($list as $k => $v) {
				$diff_days  = getDatesDiff($v["entry_time"],date("Y-m-d",time()));
				$diff_year  = 0;
				$diff_month = 0;
				if(!empty($diff_days["y"]) && $diff_days["y"]!="00"){
					$diff_year = preg_replace('/^0*/', '', $diff_days["y"]);
				}
				if(!empty($diff_days["m"]) && $diff_days["m"]!="0"){
					$diff_month = $diff_days["m"];
				}
				$data["company_age"] = $diff_year.".".$diff_month;

				$row = M("oa_hr_manage")->where(array("id"=>$v["id"]))->save($data);
				if($row){
					echo $v["id"].'--'.$data["company_age"].'--'."--success<br/>";
				}else{
					echo $v["id"].'--'.$data["company_age"].'--'."--fail<br/>";
				}
				unset($data);
			}
		}
		echo "over";
	}

	/**
	 * 导出公司在职人员的权限
	 * @return [type] [description]
	 */
	/*
	function explortUserMenus(){
		$token = trim(I("token"));
		if($token=="token023"){
			$sql = "SELECT 
				  ap.id AS p_id,
				  ap.`title`,
				  a.`title` AS child_name,
				  a.id AS c_id 
				FROM
				  `boss_auth_rule` AS a 
				  LEFT JOIN `boss_auth_rule` AS ap 
				    ON a.pid = ap.id 
				WHERE a.pid > 0 ";
			$model = new \Think\Model();
			$list = $model->query($sql);

			foreach ($list as $k => $v) {
				//获取某一个子菜单的有哪些人在使用
				//角色id
				$v["c_id"] = empty($v["c_id"])?0:$v["c_id"];
				$sql = " SELECT 
					        id as role_id
					      FROM
					        `boss_auth_group` 
					      WHERE FIND_IN_SET('".$v["c_id"]."', rules)";
		      	$role_id_list = $model->query($sql);
		      	$role_id = "";
		      	foreach ($role_id_list as $kr => $vr) {
		      		$role_id .= $vr["role_id"].",";
		      	}
		      	if($role_id){
		      		$role_id = substr($role_id, 0,strlen($role_id)-1);
		      	}

		      	$role_id = empty($role_id)?0:$role_id;
		      	//哪些人用了这些角色
		      	$sql = "SELECT uid FROM
					      `boss_auth_group_access` 
					    WHERE group_id in ({$role_id})";
			    $user_id_list = $model->query($sql);
			    $uids = "";
			    foreach ($user_id_list as $ku => $vu) {
		    		$uids .= $vu["uid"].",";
			    }
			    if($uids){
		      		$uids = substr($uids, 0,strlen($uids)-1);
		      	}
		      	$uids = empty($uids)?0:$uids;


		      	$sql = "SELECT 
							  user_name 
							FROM
							  `boss_oa_hr_manage`
							where user_id in ({$uids}) and status not in (1,4)";
				$user_id_list = $model->query($sql);
				$uids = "";
			    foreach ($user_id_list as $ku => $vu) {
		    		$uids .= $vu["user_name"].",";
			    }

			    $list[$k]["user_name"] = $uids;

			}


			//导出excel
			$se = new \Home\Service\ExcelLogicService();
			$se->explortUserMenusToExcel($list);
		}
	}*/

	/**
	 * 更新用户的oa的oa_link字段
	 * @return [type] [description]
	 */
	function synUserTableOALink(){
		$token = trim(I("token"));
		if($token=="token023"){
			$list = M("user")->field("oa_link,id")->select();
			foreach ($list as $k => $v) {
				if($v["oa_link"]){
					$oa_link = $v["oa_link"];
					$liuchengList = M("oa_liuchen_m")->field("id")->where(array("id"=>array("in",$v["oa_link"])))->select();

					$where_ = "";
					foreach ($liuchengList as $kb => $vb) {
						if($vb["id"]){
							if($where_){
								$where_ .= " or name like '%/OA/Index/begin?id=".$vb["id"]."%' ";
							}else{
								$where_ .= "( name like '%/OA/Index/begin?id=".$vb["id"]."%' ";
							}
							
						}	
					}
					$where_ .= ") and pid>0";
					//查询权限菜单的id集合
					$caidanList = M("auth_rule")->field("id")->where($where_)->select();
					$caidanList_ids = "";
					foreach ($caidanList as $ka => $va) {
						$caidanList_ids .= $va["id"].",";
					}

					if($caidanList_ids){
						$caidanList_ids = substr($caidanList_ids, 0,strlen($caidanList_ids)-1);
					}

					//保存到user表的oa_link字段
					$data = array();
					$data["oa_link"] = $caidanList_ids;
					$row = M("user")->where(array("id"=>$v["id"]))->save($data);

					if($row){
						print_r("success");
						print_r("<br/>");
					}else{
						print_r("fail");
						print_r("<br/>");
					}
				}else{
					continue;
				}
				
			}
		}
		echo "over";
		
	}

	/**
	 * 更新权限菜单的img字段
	 * @return [type] [description]
	 */
	function synauthImg(){
		$token = trim(I("token"));
		if($token=="token023"){
			$list = M("auth_rule")->field("id,name")->where("name like '%/OA/Index/begin?id=%'")->select();
			foreach ($list as $k => $v) {
				if($v["name"]){
					// print_r($v["name"]);
					$pattern='/=(\d*)/';
					preg_match_all($pattern,$v["name"],$regs);
					$mid = $regs[1][0];
					if($mid){
						$row = M("auth_rule")->where(array("id"=>$v["id"]))->save(array("img"=>$mid));
						if($row){
							print_r("success");
							print_r("<br/>");
						}else{
							print_r("fail");
							print_r("<br/>");
						}
					}else{
						print_r($v["name"]."---正则获取失败");
						print_r("<br/>");
					}
				}
			}
		}
		echo "over";
	}


	function synUserAuthGroup(){
		$token = trim(I("token"));
		if($token=="token023"){
			$list = M("auth_group_access")->field("uid,group_id")->select();
			foreach ($list as $k => $v) {
				$data = array();
				$data["group_id"] = $v["group_id"];
				$row = M("user")->where(array("id"=>$v["uid"]))->save($data);
			}
		}
		echo "over";
	}



}
?>