<?php
/**
* InteSystemService service
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class InteSystemService extends CommonService
{	

	/**
	 * 已合作广告主列表
	 * @return [type] [description]
	 */
	function getAllCooperAdver($where="",$orderby="ORDER BY ad.add_time DESC",$where_count=""){
		$result = array("list"=>array(),"page"=>"");
		$sql = "SELECT 
				  ad.id AS ad_id,
				  ad.name AS ad_name,
				  re.name AS region_name,
				  ad.tag,
				  ad.add_time,
				  ef.result AS f_result,
				  GROUP_CONCAT(p.cooperate_state) as hz_status_str,
				  GROUP_CONCAT(p.saler_id) AS user_ids
				FROM
				  `boss_advertiser` AS ad 
				  LEFT JOIN boss_region AS re ON re.id = ad.province_id
				  LEFT JOIN `boss_product` AS p ON ad.id=p.`ad_id`
				  LEFT JOIN 
				    (SELECT 
				      * 
				    FROM
				      boss_expand_follow a 
				    WHERE a.id IN 
				      (SELECT 
				        MAX(id) AS id 
				      FROM
				        boss_expand_follow 
				      WHERE type_id = 2 
				      GROUP BY expand_id) 
				      AND type_id = 2) AS ef 
				    ON ef.expand_id = ad.id 
				    {$where} GROUP BY ad.id {$orderby} ";
		$sql_count = "SELECT 
					  COUNT(1) AS NO
					FROM
					  `boss_advertiser` AS ad 
					  LEFT JOIN boss_region AS re ON re.id = ad.province_id 
				    {$where_count}";


		$model    = new \Think\Model();
		$count    = $model->query($sql_count);
		$count    = $count[0]["no"];
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);

		$sql     .= " limit ".$page->firstRow.",".$page->listRows."";
		// print_r($sql);exit;
		$list     = $model->query($sql);
		unset($sql);unset($sql_count);
		
		//计算广告主当年的总收入和成本
		$ad_ids    = $this->getAdverId($list);
		// print_r($ad_ids);exit;
		$ad_ids    = empty($ad_ids)?"0":$ad_ids;

		$prev_year = date("Y",time());
		$star_time = $prev_year."-01-01";
		$end_time  = $prev_year."-12-31";
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  SUM(out_newmoney) as total_out,
				  SUM(in_newmoney)-SUM(out_newmoney) AS total_liushui,
				  in_adverid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_adverid in ({$ad_ids}) 
				GROUP BY in_adverid  ";
				// print_r($sql);exit;
		$ad_total_list = $model->query($sql);
		$ad_liushui = array();
		foreach ($ad_total_list as $k => $v) {
			$one["total_in"] = $v["total_in"];
			$one["total_liushui"] = $v["total_liushui"];
			$ad_liushui[$v["in_adverid"]] = $one;
			unset($one);
		}

		//计算广告主当月的总收入流水
		$year_month = date("Y-m",time());
		$fact_days = $this->getMonthDays($year_month);
		$star_time = $year_month."-01";
		$end_time  = $year_month."-".$fact_days;
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  in_adverid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_adverid in ({$ad_ids}) 
				GROUP BY in_adverid  ";
		$ad_month_list = $model->query($sql);
		$ad_month_liushui = array();
		foreach ($ad_month_list as $k => $v) {
			$ad_month_liushui[$v["in_adverid"]]["total_in"] = $v["total_in"];
		}

		//第二次筛选
		$user_list = $this->getUserTrees($list,$model);
		// print_r($user_list);
		foreach ($list as $k => $v) {
			//总利润
			$total_liushui             = $ad_liushui[$v["ad_id"]]["total_liushui"];
			$total_liushui             = empty($total_liushui)?0:$total_liushui;
			$list[$k]["total_liushui"] = number_format($total_liushui, 2, '.', ',');
			unset($total_liushui);
			
			//总收入
			$total_in                  = $ad_liushui[$v["ad_id"]]["total_in"];
			$total_in                  = empty($total_in)?0:$total_in;
			$list[$k]["total_in"]      = number_format($total_in, 2, '.', ',');

			//月总流水
			$total_in                  = $ad_month_liushui[$v["ad_id"]]["total_in"];
			$total_in                  = empty($total_in)?0:$total_in;
			//根据月总流水评级
			$list[$k]["ad_grade"] = $this->getGradesByLiushui($total_in);

			//判断合作状态
			$list[$k]["hz_status_str"] = $this->getHZStatus($v["hz_status_str"]);
			//销售人员
			$ids = explode(",",$v["user_ids"]);
			// print_r($ids);print_r("<br>");
			$user_names = "";
			foreach ($ids as $k1 => $v1) {
				$user_names .= $user_list[$v1]["real_name"].",";
			}
			$list[$k]["xs_user_names"] = substr($user_names,0,strlen($user_names)-1);
			unset($user_names);
		}
		// exit;
		$result["list"] = $list;
		$result["page"] = $page->show();
		unset($$list);
		unset($page);
		return $result;
	}

	/**
	 * 获取用户tree
	 * @return [type] [description]
	 */
	private function getUserTrees($list,$model){
		$ids = "";
		if(!$list) return false;
		foreach ($list as $k => $v) {
			if($v["user_ids"]){
				$ids .= $v["user_ids"].",";
			}
		}
		if($ids){
			$ids = substr($ids, 0,strlen($ids)-1);
		}
		$model    = empty($model) ? new \Think\Model() : $model;
		$ids      = empty($ids)?0:$ids;
		$sql      = "SELECT id,real_name FROM `boss_user` WHERE id IN ({$ids})";
		// print_r($sql);exit;
		$list     = $model->query($sql);
		$userTree = array();
		foreach ($list as $k => $v) {
			$userTree[$v["id"]] = $v;
		}
		unset($list);
		return $userTree;
	}

	/**
	 * 判断合作状态
	 * @return [type] [description]
	 */
	private function getHZStatus($hz_status_str){
		$status_str = "";
		$list = explode(",", $hz_status_str);
		if(in_array(3, $list)){
			$status_str = "暂停中";
		}
		if(in_array(1, $list) || in_array(2, $list)){
			$status_str = "合作中";
		}
		unset($list);unset($hz_status_str);
		return $status_str;
	}



	/**
	 * 评级流水
	 * @param  [type] $liushui [description]
	 * @return [type]          [description]
	 */
	private function getGradesByLiushui($liushui){
		$grade = "--";
		if($liushui<50000){
			$grade = "C";//专员
		}
		if($liushui>=50000 && $liushui<100000){
			$grade = "B";//主管，经理
		}
		if($liushui>=100000 && $liushui<500000){
			$grade = "A";//高级经理
		}
		if($liushui>=500000){
			$grade = "S";//总监，boss
		}
		return $grade;
	}

	/**
	 * [已合作广告主列表--详细 description]
	 * @param  string $where [description]
	 * @return [type]        [description]
	 */
	function cooperAdverDetailSer($where="",$orderby=""){
		$result = array("list"=>array(),"page"=>"");
		$sql = "SELECT 
				  p.ad_id,
				  ad.name as ad_name,
				  p.name AS pro_name,
				  p.charging_mode,
				  ad.tag,
				  b.name AS line_name,
				  ad.ad_grade,
				  u.real_name,
				  ad_c.name AS adc_name,
				  ad_c.mobile AS adc_mobile,p.id,p.cooperate_state,re.name as region_name,ef.visit_time,ef.result as f_result,
				  CASE 
				  WHEN p.cooperate_state=1 OR p.cooperate_state=2 THEN '合作中'
				  WHEN p.cooperate_state=3 THEN '已暂停'
				  END
				  AS cooperate_state_str,
				  ad.add_time
				FROM
				  `boss_product` AS p 
				  LEFT JOIN `boss_advertiser` AS ad 
				    ON p.ad_id = ad.id 
				  LEFT JOIN `boss_business_line` AS b 
				    ON b.id = p.bl_id 
				  LEFT JOIN `boss_user` AS u 
				    ON u.id = p.saler_id 
				  LEFT JOIN boss_advertiser_contacts AS ad_c 
				    ON ad_c.ad_id = ad.id 
				   left join boss_region as re on re.id=ad.province_id
				   LEFT JOIN 
					    (SELECT 
					      * 
					    FROM
					      boss_expand_follow a 
					    WHERE a.id IN 
					    	(SELECT 
						        MAX(id) AS id 
						      FROM
						        boss_expand_follow where type_id=5
						      GROUP BY expand_id
					        )
							and type_id=5
						) AS ef 
					    ON ef.expand_id = p.id 
				    {$where}  {$orderby}";
		$sql_count = "SELECT 
				  count(1) as no
				FROM
				  `boss_product` AS p 
				  LEFT JOIN `boss_advertiser` AS ad 
				    ON p.ad_id = ad.id 
				  LEFT JOIN `boss_business_line` AS b 
				    ON b.id = p.bl_id 
				  LEFT JOIN `boss_user` AS u 
				    ON u.id = p.saler_id 
				  LEFT JOIN boss_advertiser_contacts AS ad_c ON ad_c.ad_id = ad.id 
				  left join boss_region as re on re.id=ad.province_id
				    {$where}  {$orderby}";


		$model    = new \Think\Model();
		$count    = $model->query($sql_count);
		$count    = $count[0]["no"];
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);

		$sql     .= " limit ".$page->firstRow.",".$page->listRows."";
		// print_r($sql);exit;
		$list     = $model->query($sql);
		unset($sql);unset($sql_count);

		//计算广告主的上一年的总收入和成本
		$ad_ids    = $this->getAdverId($list);
		$ad_ids    = empty($ad_ids)?"0":$ad_ids;
		$prev_year = date("Y",time());
		$star_time = $prev_year."-01-01";
		$end_time  = $prev_year."-12-31";
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  SUM(out_newmoney) as total_out,
				  SUM(in_newmoney)-SUM(out_newmoney) AS total_liushui,
				  in_adverid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_adverid in ({$ad_ids}) 
				GROUP BY in_adverid  ";
				// print_r($sql);exit;
		$ad_total_list = $model->query($sql);
		$ad_liushui = array();
		foreach ($ad_total_list as $k => $v) {
			$one["total_in"] = $v["total_in"];
			$one["total_liushui"] = $v["total_liushui"];
			$ad_liushui[$v["in_adverid"]] = $one;
			unset($one);
		}

		//计算产品前日收入流水
		$product_ids = $this->getProductIds($list);
		$product_ids    = empty($product_ids)?"0":$product_ids;
		$prev_year = date("Y-m-d",strtotime("-2 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(in_newmoney) as yestoday_total_in,
				  in_comid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_comid in ({$product_ids}) 
				GROUP BY in_comid  ";

		$ad_yestoday_total_list = $model->query($sql);
		$product_yestoday1_liushui = array();
		foreach ($ad_yestoday_total_list as $k => $v) {
			$one["yestoday_total_in"] = $v["yestoday_total_in"];
			$product_yestoday1_liushui[$v["in_comid"]] = $one;
			unset($one);
		}

		//计算广告主当月的总收入流水
		$year_month = date("Y-m",time());
		$fact_days = $this->getMonthDays($year_month);
		$star_time = $year_month."-01";
		$end_time  = $year_month."-".$fact_days;
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  in_adverid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_adverid in ({$ad_ids}) 
				GROUP BY in_adverid  ";
		$ad_month_list = $model->query($sql);
		$ad_month_liushui = array();
		foreach ($ad_month_list as $k => $v) {
			$ad_month_liushui[$v["in_adverid"]]["total_in"] = $v["total_in"];
		}
		unset($ad_month_list);

		//第三次筛选
		foreach ($list as $k => $vv) {
			//总收入
			$total_in                       = $ad_liushui[$vv["ad_id"]]["total_in"];
			$total_in                       = empty($total_in)?0:$total_in;
			$list[$k]["total_in"]           = number_format($total_in, 2, '.', ',');
			unset($total_in);
			
			//总收入
			$total_liushui                  = $ad_liushui[$vv["ad_id"]]["total_liushui"];
			$total_liushui                  = empty($total_liushui)?0:$total_liushui;
			$list[$k]["total_liushui"]      = number_format($total_liushui, 2, '.', ',');
			unset($total_liushui);
			
			//前日流水
			$yestoday_total_in              = $product_yestoday1_liushui[$vv["id"]]["yestoday_total_in"];
			$yestoday_total_in              = empty($yestoday_total_in)?0:$yestoday_total_in;
			$list[$k]["yestoday_total_in1"] = number_format($yestoday_total_in, 2, '.', ',');
			unset($yestoday_total_in);


			//月总流水
			$total_in                  = $ad_month_liushui[$vv["ad_id"]]["total_in"];
			$total_in                  = empty($total_in)?0:$total_in;
			//根据月总流水评级
			$list[$k]["ad_grade"] = $this->getGradesByLiushui($total_in);
			unset($total_in);
		}
		unset($ad_liushui);
		unset($ad_month_liushui);
		
		$result["list"] = $list;
		$result["page"] = $page->show();
		unset($$list);
		unset($page);
		return $result;
	}

	/**
	 * [[已合作供应商列表--详细 description]
	 * @param  string $where   [description]
	 * @param  string $orderby [description]
	 * @return [type]          [description]
	 */
	function cooperSupplyDetailSer($where="",$orderby="ORDER BY s.add_time DESC"){
		$result = array("list"=>array(),"page"=>"");
		$sql = "SELECT 
				  s.name AS gys_name,
				  p.name AS pro_name,
				  p.charging_mode,
				  p.cooperate_state,
				  s.tag,
				  b.name AS line_name,
				  re.`name` AS diqu_name,
				  s.grade,
				  sc.`mobile`,
				  sc.`name` AS lxr_name,
				  p.id AS pro_id,
				  s.id AS gys_id,ef.visit_time,ef.result as f_result,
				   CASE 
				  WHEN p.cooperate_state=1 OR p.cooperate_state=2 THEN '合作中'
				  WHEN p.cooperate_state=3 THEN '已暂停'
				  END
				  AS cooperate_state_str,s.add_time
				FROM
				  `boss_supplier` AS s 
				  LEFT JOIN boss_charging_logo_assign AS cla 
				    ON cla.`sup_id` = s.`id` 
				  LEFT JOIN `boss_charging_logo` AS c 
				    ON c.id = cla.`cl_id` 
				  LEFT JOIN `boss_product` AS p 
				    ON p.id = c.`prot_id` 
				  LEFT JOIN `boss_business_line` AS b 
				    ON b.id = p.bl_id 
				  LEFT JOIN boss_region AS re 
				    ON re.id = s.region 
				  LEFT JOIN `boss_supplier_contacts` AS sc 
				    ON sc.`sp_id` = s.`id`  
				  LEFT JOIN 
					    (SELECT 
					      * 
					    FROM
					      boss_expand_follow a 
					    WHERE a.id IN 
					    	(SELECT 
						        MAX(id) AS id 
						      FROM
						        boss_expand_follow where type_id=6
						      GROUP BY expand_id
					        )
							and type_id=6
						) AS ef 
				    ON ef.expand_id = p.id 
			    {$where} GROUP BY p.id {$orderby}";
		$sql_count = "SELECT 
					  COUNT(1) AS NO 
					FROM
					  `boss_supplier` AS s 
					  LEFT JOIN boss_charging_logo_assign AS cla 
					    ON cla.`sup_id` = s.`id` 
					  LEFT JOIN `boss_charging_logo` AS c 
					    ON c.id = cla.`cl_id` 
					  LEFT JOIN `boss_product` AS p 
					    ON p.id = c.`prot_id` 
					  LEFT JOIN `boss_business_line` AS b 
					    ON b.id = p.bl_id   
				    {$where} GROUP BY p.id";
		$model    = new \Think\Model();
		$count    = $model->query($sql_count);
		$count    = $count[0]["no"];
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);

		$sql     .= " limit ".$page->firstRow.",".$page->listRows."";
		// print_r($sql);exit;
		$list     = $model->query($sql);
		unset($sql);unset($sql_count);

		//计算供应商的上一年的总收入和成本
		$supply_ids    = $this->getSupplyId($list);
		$supply_ids    = empty($supply_ids)?"0":$supply_ids;
		$prev_year = date("Y",time());
		$star_time = $prev_year."-01-01";
		$end_time  = $prev_year."-12-31";
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  SUM(out_newmoney) as total_out,
				  SUM(in_newmoney)-SUM(out_newmoney) AS total_liushui,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and out_superid in ({$supply_ids}) 
				GROUP BY out_superid  ";
		
		// print_r($sql);exit;
		$supply_total_list = $model->query($sql);
		$supply_liushui = array();
		foreach ($supply_total_list as $k => $v) {
			$one["total_in"]                   = $v["total_in"];
			$one["total_out"]                  = $v["total_out"];
			$one["total_liushui"]              = $v["total_liushui"];
			$supply_liushui[$v["out_superid"]] = $one;
			unset($one);
		}


		//计算供应商前日成本流水
		$prev_year = date("Y-m-d",strtotime("-2 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(out_newmoney) as yestoday_total_in,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}'
				GROUP BY out_superid  ";
		// print_r($sql);exit;
		$ad_yestoday_total_list = $model->query($sql);
		$product_yestoday_liushui1 = array();
		foreach ($ad_yestoday_total_list as $k => $v) {
			$one["yestoday_total_in"] = $v["yestoday_total_in"];
			$product_yestoday_liushui1[$v["out_superid"]] = $one;
			unset($one);
		}


		//计算供应商当月的总成本流水
		$year_month = date("Y-m",time());
		$fact_days = $this->getMonthDays($year_month);
		$star_time = $year_month."-01";
		$end_time  = $year_month."-".$fact_days;
		$sql = "SELECT 
				  SUM(out_newmoney) as total_out,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and out_superid in ({$supply_ids}) 
				GROUP BY out_superid  ";
		$gys_month_list = $model->query($sql);
		$gys_month_liushui = array();
		foreach ($gys_month_list as $k => $v) {
			$gys_month_liushui[$v["out_superid"]]["total_out"] = $v["total_out"];
		}

		//获取计费标识下的商务，商务和供应商联系，供应商的流水都是算到这个商务头上的
		$all_business_list = $this->getbusinessList($supply_ids,$model);
		//第二次筛选
		$arr = $this->getTags();
		foreach ($list as $k => $vv) {
			$tag       =json_decode(htmlspecialchars_decode($vv['tag']),true);
			if($tag[0]['media_type'] && $tag[0]['resource_scale']){
				$str             =$tag[0]['media_type'].'('.$arr[$tag[0]['media_type']].'):'.$tag[0]['resource_scale'];
				$list[$k]['tag'] = $str;
				if(count($tag)>1)$str .=" ...";
			}
			//商务
			$sw_name             = $all_business_list[$vv["gys_id"]]["sw_name"];
			$list[$k]["sw_name"] = empty($sw_name)?"--":$sw_name;
			unset($sw_name);

			//前日成本流水
			$yestoday_total_in              = $product_yestoday_liushui1[$vv["gys_id"]]["yestoday_total_in"];
			$yestoday_total_in              = empty($yestoday_total_in)?0:$yestoday_total_in;
			$list[$k]["yestoday_total_in1"] = number_format($yestoday_total_in, 2, '.', ',');
			
			//上一年成本
			$total_out                      = $supply_liushui[$vv["gys_id"]]["total_out"];
			$total_out                      = empty($total_out)?0:$total_out;
			$list[$k]["total_out"]          = number_format($total_out, 2, '.', ',');
			
			//上一年利润
			$total_liushui                  = $supply_liushui[$vv["gys_id"]]["total_liushui"];
			$total_liushui                  = empty($total_liushui)?0:$total_liushui;
			$list[$k]["total_liushui"]      = number_format($total_liushui, 2, '.', ',');
			unset($total_liushui);
			unset($total_out);
			unset($yestoday_total_in);

			//月总流水
			$total_out                  = $gys_month_liushui[$v["gys_id"]]["total_out"];
			$total_out                  = empty($total_out)?0:$total_out;
			//根据月总流水评级
			$list[$k]["grade"] = $this->getGradesByLiushui($total_out);
			unset($total_out);

		}
		unset($arr);
		unset($all_business_list);

		$result["list"] = $list;
		$result["page"] = $page->show();
		unset($list);
		unset($page);
		return $result;
	}

	/**
	 * 获取广告主id
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function getAdverId($list){
		$ids = "";
		if(!$list) return false;
		foreach ($list as $k => $v) {
			if($v["ad_id"]){
				$ids .= $v["ad_id"].",";
			}
		}
		if($ids){
			$ids = substr($ids, 0,strlen($ids)-1);
		}
		return $ids;
	}

	/**
	 * [getProductIds description]
	 * @return [type] [description]
	 */
	private function getProductIds($list){
		$ids = "";
		if(!$list) return false;
		foreach ($list as $k => $v) {
			$ids .= $v["id"].",";
		}
		if($ids){
			$ids = substr($ids, 0,strlen($ids)-1);
		}
		return $ids;
	}

	/**
	 * [getProductIds description]
	 * @return [type] [description]
	 */
	private function getProductIds_($list){
		$ids = "";
		if(!$list) return false;
		foreach ($list as $k => $v) {
			$ids .= $v["pro_id"].",";
		}
		if($ids){
			$ids = substr($ids, 0,strlen($ids)-1);
		}
		return $ids;
	}
	/**
	 * 供应商
	 * @return [type] [description]
	 */
	private function getSupplyId($list){
		$ids = "";
		if(!$list) return false;
		foreach ($list as $k => $v) {
			if($v["gys_id"]){
				$ids .= $v["gys_id"].",";
			}
		}
		if($ids){
			$ids = substr($ids, 0,strlen($ids)-1);
		}
		return $ids;
	}

	/**
	 * 若流水比前一天数据存在正负30%的波动则需要系统发起提醒功能--广告主
	 * @return [type] [description]
	 */
	function checkYestodayCompareTodaySer(){
		$model         = new \Think\Model();
		//计算广告主昨日收入流水
		$prev_year = date("Y-m-d",strtotime("-1 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(in_newmoney) as yestoday_total_in,
				  in_adverid
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' 
				GROUP BY in_adverid  ";
				// print_r($sql);exit;
		$ad_yestoday_total_list = $model->query($sql);
		// $ad_yestoday_total_list = array(0=>array("yestoday_total_in"=>101,"in_adverid"=>10));//模拟数据
		$ad_yestoday_liushui = array();
		foreach ($ad_yestoday_total_list as $k => $v) {
			$one["yestoday_total_in"]            = $v["yestoday_total_in"];
			$one["in_adverid"]                   = $v["in_adverid"];
			$ad_yestoday_liushui[$v["in_adverid"]] = $one;
			unset($one);
		}
		unset($ad_yestoday_total_list);

		//计算广告主前日收入流水
		$prev_year = date("Y-m-d",strtotime("-2 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(in_newmoney) as yestoday_total_in,
				  in_adverid
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' 
				GROUP BY in_adverid ";
				// print_r($sql);exit;
		$ad_yestoday1_total_list = $model->query($sql);
		$ad_yestoday1_liushui = array();//前天的
		// $ad_yestoday1_total_list = array(0=>array("yestoday_total_in"=>68,"in_adverid"=>10));
		foreach ($ad_yestoday1_total_list as $k => $v) {
			$one["yestoday_total_in"]             = $v["yestoday_total_in"];
			$one["in_adverid"]                    = $v["in_adverid"];
			$ad_yestoday1_liushui[$v["in_adverid"]] = $one;
			unset($one);
		}
		// print_r($ad_yestoday1_liushui);exit;

		unset($ad_yestoday1_total_list);
		unset($all_product_ids);

		//昨日和前日比较，昨日流水超出前日正负30%,设置提醒
		$need_follow_adverids = "";
		foreach ($ad_yestoday1_liushui as $k => $v) {
			$qiantian_val = $v["yestoday_total_in"];
			$yestoday_val = $ad_yestoday_liushui[$v['in_comid']]["yestoday_total_in"];
			$qiantian_min = $qiantian_val*(1-0.3);
			$qiantian_max = $qiantian_val*(1+0.3);
			if($yestoday_val>$qiantian_max || $yestoday_val<$qiantian_min){
				//满足条件
				$need_follow_adverids .= $k.",";
			}
		}

		// print_r($need_follow_adverids);exit;
		//有满足的广告主
		$result = array("code"=>500,"adver_ids"=>"");
		if($need_follow_adverids){
			$need_follow_adverids = substr($need_follow_adverids,0,strlen($need_follow_adverids)-1);
			$need_follow_adverids = empty($need_follow_adverids)?0:$need_follow_adverids;
			//查询出满足条件的,并且没有跟进的广告主的广告主id
			$sql = "SELECT 
					  ad.`id` as adid,
					  ex.`expand_id` 
					FROM
					  `boss_advertiser` AS ad 
					  LEFT JOIN `boss_expand_follow` AS ex 
					    ON ex.`expand_id` = ad.id 
				    where ad.`id` IN ({$need_follow_adverids}) and ex.type_id=2";
			$need_adverids = "";
			$list = $model->query($sql);
			foreach ($list as $k => $v) {
				if(empty($v["expand_id"])){
					$need_adverids .= $v["adid"].",";
				}
			}
			if($need_adverids){
				$result["code"] = 200;
				$result["adver_ids"] = substr($need_adverids, 0,strlen($need_adverids)-1);
			}
			unset($need_adverids);
		}
		return $result;
	}

	/**
	 * [若流水比前一天数据存在正负30%的波动则需要系统发起提醒功能--供应商 description]
	 * @return [type] [description]
	 */
	function checkYestodayCompareTodaySupplySer(){
		//计算供应商昨日成本流水
		$prev_year = date("Y-m-d",strtotime("-1 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(out_newmoney) as yestoday_total_out,
				  out_superid
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}'
				  GROUP BY out_superid ";
				// print_r($sql);exit;
	    $model = new \Think\Model();
		$ad_yestoday_total_list = $model->query($sql);
		// $ad_yestoday_total_list = array(0=>array("yestoday_total_in"=>101,"in_adverid"=>10));
		$ad_yestoday_liushui = array();
		foreach ($ad_yestoday_total_list as $k => $v) {
			$one["yestoday_total_out"]            = $v["yestoday_total_out"];
			$one["out_superid"]                  = $v["out_superid"];
			$ad_yestoday_liushui[$v["out_superid"]] = $one;
			unset($one);
		}

		//计算供应商前日成本流水
		$prev_year = date("Y-m-d",strtotime("-2 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(out_newmoney) as yestoday_total_out,out_superid
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' 
				GROUP BY out_superid ";
				// print_r($sql);exit;
		$ad_yestoday1_total_list = $model->query($sql);
		$ad_yestoday1_liushui = array();
		// $ad_yestoday1_total_list = array(0=>array("yestoday_total_in"=>68,"in_adverid"=>10));
		foreach ($ad_yestoday1_total_list as $k => $v) {
			$one["yestoday_total_out"] = $v["yestoday_total_out"];
			$one["out_superid"]       = $v["out_superid"];
			$ad_yestoday1_liushui[$v["out_superid"]] = $one;
			unset($one);
		}
		unset($all_product_ids);

		//昨日和前日比较，昨日流水超出前日正负30%,设置提醒--成本流水
		$need_follow_gys_ids = "";//供应商id 
		foreach ($ad_yestoday1_liushui as $k => $v) {
			$qiantian_val = $v["yestoday_total_out"];
			$yestoday_val = $ad_yestoday_liushui[$v['out_superid']]["yestoday_total_out"];
			$qiantian_min = $qiantian_val*(1-0.3);
			$qiantian_max = $qiantian_val*(1+0.3);
			if($yestoday_val>$qiantian_max || $yestoday_val<$qiantian_min){
				//满足条件
				$need_follow_gys_ids .= $k.",";
			}
		}
		unset($ad_yestoday1_liushui);
		//有满足的广告主
		$result = array("code"=>500,"gys_ids"=>"");
		if($need_follow_gys_ids){
			$need_follow_gys_ids = substr($need_follow_gys_ids,0,strlen($need_follow_gys_ids)-1);
			$need_follow_gys_ids = empty($need_follow_gys_ids)?0:$need_follow_gys_ids;

			//查询出满足条件的,并且没有跟进的广告主的供应商id
			$sql = "SELECT 
					  p.id,
					  ef.`expand_id` 
					FROM
					  `boss_product` AS p 
					  LEFT JOIN `boss_expand_follow` AS ef 
					    ON p.id = ef.`expand_id` 
					WHERE p.id in ({$need_follow_gys_ids}) and ef.type_id=3";
			unset($need_follow_gys_ids);
			$need_gys_ids = "";
			$list = $model->query($sql);
			foreach ($list as $k => $v) {
				if(empty($v["expand_id"])){
					$need_gys_ids .= $v["id"].",";
				}
			}
			if($need_gys_ids){
				$result["code"] = 200;
				$result["gys_ids"] = substr($need_gys_ids, 0,strlen($need_gys_ids)-1);
			}
			unset($need_gys_ids);
		}
		return $result;
	}

	/**
	 * 已合作供应商
	 * @return [type] [description]
	 */
	function getAllCooperSupply($where="",$orderby="ORDER BY s.add_time DESC"){
		$result = array("list"=>array(),"page"=>"");
		$sql = "SELECT 
				  s.name AS gys_name,
				  s.tag,
				  re.`name` AS diqu_name,
				  s.id AS gys_id,
				  ef.visit_time,
				  ef.result AS f_result,s.add_time,sc.`business_uid` as user_ids
				FROM
				  `boss_supplier` AS s 
				  LEFT JOIN boss_region AS re ON re.id = s.region 
				  LEFT JOIN `boss_supplier_contacts` AS sc ON sc.`sp_id`=s.`id`
				  LEFT JOIN 
				    (SELECT 
				      * 
				    FROM
				      boss_expand_follow a 
				    WHERE a.id IN 
				      (SELECT 
				        MAX(id) AS id 
				      FROM
				        boss_expand_follow 
				      WHERE type_id = 3 
				      GROUP BY expand_id) 
				      AND type_id = 3) AS ef 
				    ON ef.expand_id = s.id  
			    {$where} {$orderby}";
		$sql_count = "SELECT 
					  COUNT(1) AS NO
					FROM
					  `boss_supplier` AS s 
					  LEFT JOIN boss_region AS re 
					    ON re.id = s.region 
					  LEFT JOIN 
					    (SELECT 
					      * 
					    FROM
					      boss_expand_follow a 
					    WHERE a.id IN 
					      (SELECT 
					        MAX(id) AS id 
					      FROM
					        boss_expand_follow 
					      WHERE type_id = 3 
					      GROUP BY expand_id) 
					      AND type_id = 3) AS ef 
					    ON ef.expand_id = s.id  
						{$where}";

				    // print_r($sql_count);
				    // print_r($sql);
				    // exit;
		$model    = new \Think\Model();
		$count    = $model->query($sql_count);
		$count    = $count[0]["no"];
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$sql     .= " limit ".$page->firstRow.",".$page->listRows."";
		$list     = $model->query($sql);
		unset($sql);unset($sql_count);

		//第二次筛选
		$all_gys_id = $this->getSupplyId($list);
		$all_gys_id = empty($all_gys_id)?"0":$all_gys_id;

		//计算供应商的当年的总收入和成本
		$supply_ids = $this->getSupplyId($list);
		$supply_ids = empty($supply_ids)?"0":$supply_ids;
		$prev_year  = date("Y",time());
		$star_time  = $prev_year."-01-01";
		$end_time   = $prev_year."-12-31";
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  SUM(out_newmoney) as total_out,
				  SUM(in_newmoney)-SUM(out_newmoney) AS total_liushui,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and out_superid in ({$supply_ids}) 
				GROUP BY out_superid  ";
				// print_r($sql);exit;
		$supply_total_list = $model->query($sql);
		$supply_liushui = array();
		foreach ($supply_total_list as $k => $v) {
			$one["total_in"]                   = $v["total_in"];
			$one["total_out"]                  = $v["total_out"];
			$one["total_liushui"]              = $v["total_liushui"];
			$supply_liushui[$v["out_superid"]] = $one;
			unset($one);
		}
		unset($all_gys_id);unset($sql);
		unset($supply_total_list);
		

		//计算供应商当月的总成本流水
		$year_month = date("Y-m",time());
		$fact_days = $this->getMonthDays($year_month);
		$star_time = $year_month."-01";
		$end_time  = $year_month."-".$fact_days;
		$sql = "SELECT 
				  SUM(out_newmoney) as total_out,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and out_superid in ({$supply_ids}) 
				GROUP BY out_superid  ";
		$gys_month_list = $model->query($sql);
		// print_r($sql);exit;
		$gys_month_liushui = array();
		foreach ($gys_month_list as $k => $v) {
			$gys_month_liushui[$v["out_superid"]]["total_out"] = $v["total_out"];
		}

		// print_r($gys_month_liushui);exit;

		//第二次筛选
		$arr = $this->getTags();
		$user_list = $this->getUserTrees($list,$model);
		foreach ($list as $k => $vv) {
			//标签
			$tag       =json_decode(htmlspecialchars_decode($vv['tag']),true);
			if($tag[0]['media_type'] && $tag[0]['resource_scale']){
				$str             =$tag[0]['media_type'].'('.$arr[$tag[0]['media_type']].'):'.$tag[0]['resource_scale'];
				$list[$k]['tag'] = $str;
				if(count($tag)>1) $str .=" ...";
			}
			//上一年成本
			$total_out                 = $supply_liushui[$vv["gys_id"]]["total_out"];
			$total_out                 = empty($total_out)?0:$total_out;
			$list[$k]["total_out"]     = number_format($total_out, 2, '.', ',');
			
			//上一年利润
			$total_liushui             = $supply_liushui[$vv["gys_id"]]["total_liushui"];
			$total_liushui             = empty($total_liushui)?0:$total_liushui;
			$list[$k]["total_liushui"] = number_format($total_liushui, 2, '.', ',');
			unset($total_out);
			unset($total_liushui);

			//月总流水
			$total_out                     = $gys_month_liushui[$vv["gys_id"]]["total_out"];
			
			// print_r($vv["gys_id"]);
			$total_out                     = empty($total_out)?0:$total_out;
			$list[$k]["gys_month_liushui"] =$total_out;
			//根据月总流水评级
			$list[$k]["grade"]             = $this->getGradesByLiushui($total_out);
			//合作状态
			$list[$k]["hz_status_str"]     = $total_out>0?"合作中":"暂停中";
			unset($total_out);
			$list[$k]["sw_name"]     = $user_list[$vv["user_ids"]]["real_name"];

		}

		$result["list"] = $list;
		$result["page"] = $page->show();
		unset($list);
		unset($page);
		unset($gys_month_liushui);
		unset($gys_month_list);
		unset($sql);unset($supply_ids);
		return $result;
	}



	/**
	 * 获取供应商标签
	 * @return [type] [description]
	 */
	function getTags(){
		$arr = array(
			'电脑客户端'   =>'安装量',
			'网站'      =>'日独立用户数',
			'加粉设备'    =>'用户覆盖量',
			'电脑预装'    =>'安装量',
			'移动预装'    =>'安装量',
			'网红推广'    =>'粉丝量',
			'电脑端拓展工具' =>'日活跃用户数',
			'移动端拓展工具' =>'日活跃用户数',
			'社群推广'    =>'日活跃用户数',
			'微博/博客'   =>'用户访问量',
			'群控'      =>'微信账号量',
			'ASO刷榜'   =>'设备量',
			'下载站'     =>'日活跃用户数',
			'竞价排名'    =>'指标为空',
			'移动应用'    =>'日活跃用户数',
			'商业WiFi'  =>'用户覆盖量',
			'公众号'     =>'粉丝量',
			'平台联盟'    =>'广告展现量',
			'应用商店'    =>'日活跃用户数'
			);
		return $arr;
	}


	/**
	 * 获取商务人员集合
	 * @return [type] [description]
	 */
	function getbusinessList($superid_ids,$model){
		//获取商务人员集合
		$sql = "SELECT 
				  u.real_name,
				  dut.businessid,
				  dut.superid 
				FROM
				  `boss_daydata_out` AS dut 
				  LEFT JOIN boss_user AS u 
				    ON dut.businessid = u.id 
				WHERE dut.superid IN ({$superid_ids}) 
				GROUP BY dut.superid ";
						    // exit;
				// print_r($sql);exit;
		$model    = empty($model)? new \Think\Model():$model;
		$list    = $model->query($sql);
		unset($sql);
		if(!$list){ return false; }
		$business_list = array();
		foreach ($list as $k => $v) {
			$business_list[$v["superid"]]["sw_name"]  =  $v["real_name"];
		}
		unset($list);
		return $business_list;
	}

	/**
	 * 获取广告主收益
	 * @return [type] [description]
	 */
	function getAdverIncomeSer($params){
		$data = array("code"=>500,"data"=>array("date"=>array(),"fit"=>array(),"in"=>array(),"out"=>array()));
		if(!$params["adverid"]){ return $data; }
		$adverid = $params["adverid"];
		if(intval($params["ggz_name"])>0){
			$adverid = $params["ggz_name"];
		}
		$adverid   = empty($adverid)?0:$adverid;
		$star_time = $params["strtime"];
		$end_time  = $params["endtime"];
		$sql = "SELECT 
			  SUM(in_newmoney) AS total_in,
			  SUM(out_newmoney) AS total_out,
			  adddate
			FROM
			  `boss_daydata_inandout` 
			WHERE in_adverid ={$adverid}
			  AND ADDDATE>= '{$star_time}' and adddate<='{$end_time}'
			GROUP BY ADDDATE";
		$model = new \Think\Model();
		$list = $model->query($sql);
		unset($sql);
		unset($adverid);
		unset($star_time);
		unset($end_time);
		if(!$list) return $data;
		$data["code"] = 200;
		foreach ($list as $k => $v) {
			$data["data"]["date"][] = $v["adddate"];
			$fit                    = $v["total_in"]-$v["total_out"];
			$fit                    = empty($fit)?0:$fit;
			$data["data"]["fit"][]  = $fit;
			$data["data"]["in"][]   = empty($v["total_in"])?0:$v["total_in"];
			$data["data"]["out"][]  = empty($v["total_out"])?0:$v["total_out"];
			unset($fit);
		}
		return $data;
	}

	/**
	 * 获取供应商成本
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	function getSupplyOutSer($params){
		$data = array("code"=>500,"data"=>array("date"=>array(),"fit"=>array(),"in"=>array(),"out"=>array()));
		if(!$params["gys_id"]){ return $data; }
		$gys_id = $params["gys_id"];
		if(intval($params["ggz_name"])>0){
			$gys_id = $params["ggz_name"];
		}
		$gys_id   = empty($gys_id)?0:$gys_id;
		$star_time = $params["strtime"];
		$end_time  = $params["endtime"];
		$sql = "SELECT 
			  SUM(in_newmoney) AS total_in,
			  SUM(out_newmoney) AS total_out,
			  adddate
			FROM
			  `boss_daydata_inandout` 
			WHERE out_superid ={$gys_id}
			  AND ADDDATE>= '{$star_time}' and adddate<='{$end_time}'
			GROUP BY ADDDATE";
		$model = new \Think\Model();
		$list = $model->query($sql);
		unset($sql);
		unset($adverid);
		unset($star_time);
		unset($end_time);
		if(!$list) return $data;
		$data["code"] = 200;
		foreach ($list as $k => $v) {
			$data["data"]["date"][] = $v["adddate"];
			$fit                    = $v["total_in"]-$v["total_out"];
			$fit                    = empty($fit)?0:$fit;
			$data["data"]["fit"][]  = $fit;
			$data["data"]["in"][]   = empty($v["total_in"])?0:$v["total_in"];
			$data["data"]["out"][]  = empty($v["total_out"])?0:$v["total_out"];
			unset($fit);
		}
		return $data;
	}

	/**
	 * 导出广告主信息
	 * @return [type] [description]
	 */
	function exportAdverListSer($where,$orderby=""){
		$result = array("list"=>array());
		$sql = "SELECT 
				  p.ad_id,
				  ad.name as ad_name,
				  p.name AS pro_name,
				  p.charging_mode,
				  ad.tag,
				  b.name AS line_name,
				  ad.ad_grade,
				  u.real_name,
				  ad_c.name AS adc_name,
				  ad_c.mobile AS adc_mobile,p.id,p.cooperate_state,re.name as region_name,ef.visit_time,ef.result as f_result,
				  CASE 
				  WHEN p.cooperate_state=1 OR p.cooperate_state=2 THEN '合作中'
				  WHEN p.cooperate_state=3 THEN '已暂停'
				  END
				  AS cooperate_state_str
				FROM
				  `boss_product` AS p 
				  LEFT JOIN `boss_advertiser` AS ad 
				    ON p.ad_id = ad.id 
				  LEFT JOIN `boss_business_line` AS b 
				    ON b.id = p.bl_id 
				  LEFT JOIN `boss_user` AS u 
				    ON u.id = p.saler_id 
				  LEFT JOIN boss_advertiser_contacts AS ad_c 
				    ON ad_c.ad_id = ad.id 
				   left join boss_region as re on re.id=ad.province_id
				   LEFT JOIN 
					    (SELECT 
					      * 
					    FROM
					      boss_expand_follow a 
					    WHERE a.id IN 
					    	(SELECT 
						        MAX(id) AS id 
						      FROM
						        boss_expand_follow where type_id=5
						      GROUP BY expand_id
					        )
							and type_id=5
						) AS ef 
					    ON ef.expand_id = p.id 
				    {$where}  {$orderby}";

		$model    = new \Think\Model();
		$list     = $model->query($sql);
		// print_r($sql);exit;
		unset($sql);

		//计算广告主的上一年的总收入和成本
		$ad_ids    = $this->getAdverId($list);
		$ad_ids    = empty($ad_ids)?"0":$ad_ids;
		$prev_year = date("Y",time());
		$star_time = $prev_year."-01-01";
		$end_time  = $prev_year."-12-31";
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  SUM(out_newmoney) as total_out,
				  SUM(in_newmoney)-SUM(out_newmoney) AS total_liushui,
				  in_adverid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_adverid in ({$ad_ids}) 
				GROUP BY in_adverid  ";
				// print_r($sql);exit;
		$ad_total_list = $model->query($sql);
		$ad_liushui = array();
		foreach ($ad_total_list as $k => $v) {
			$one["total_in"] = $v["total_in"];
			$one["total_liushui"] = $v["total_liushui"];
			$ad_liushui[$v["in_adverid"]] = $one;
			unset($one);
		}

		//计算产品前日收入流水
		$product_ids = $this->getProductIds($list);
		$product_ids    = empty($product_ids)?"0":$product_ids;
		$prev_year = date("Y-m-d",strtotime("-2 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(in_newmoney) as yestoday_total_in,
				  in_comid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_comid in ({$product_ids}) 
				GROUP BY in_comid  ";

		$ad_yestoday_total_list = $model->query($sql);
		$product_yestoday1_liushui = array();
		foreach ($ad_yestoday_total_list as $k => $v) {
			$one["yestoday_total_in"] = $v["yestoday_total_in"];
			$product_yestoday1_liushui[$v["in_comid"]] = $one;
			unset($one);
		}

		//计算广告主当月的总收入流水
		$year_month = date("Y-m",time());
		$fact_days = $this->getMonthDays($year_month);
		$star_time = $year_month."-01";
		$end_time  = $year_month."-".$fact_days;
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  in_adverid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and in_adverid in ({$ad_ids}) 
				GROUP BY in_adverid  ";
		$ad_month_list = $model->query($sql);
		$ad_month_liushui = array();
		foreach ($ad_month_list as $k => $v) {
			$ad_month_liushui[$v["in_adverid"]]["total_in"] = $v["total_in"];
		}
		unset($ad_month_list);

		//第三次筛选
		foreach ($list as $k => $vv) {
			//总收入
			$total_in                       = $ad_liushui[$vv["ad_id"]]["total_in"];
			$total_in                       = empty($total_in)?0:$total_in;
			$list[$k]["total_in"]           = number_format($total_in, 2, '.', ',');
			unset($total_in);
			
			//总收入
			$total_liushui                  = $ad_liushui[$vv["ad_id"]]["total_liushui"];
			$total_liushui                  = empty($total_liushui)?0:$total_liushui;
			$list[$k]["total_liushui"]      = number_format($total_liushui, 2, '.', ',');
			unset($total_liushui);
			
			//前日流水
			$yestoday_total_in              = $product_yestoday1_liushui[$vv["id"]]["yestoday_total_in"];
			$yestoday_total_in              = empty($yestoday_total_in)?0:$yestoday_total_in;
			$list[$k]["yestoday_total_in1"] = number_format($yestoday_total_in, 2, '.', ',');
			unset($yestoday_total_in);


			//月总流水
			$total_in                  = $ad_month_liushui[$vv["ad_id"]]["total_in"];
			$total_in                  = empty($total_in)?0:$total_in;
			//根据月总流水评级
			$list[$k]["ad_grade"] = $this->getGradesByLiushui($total_in);
			unset($total_in);
		}
		unset($ad_liushui);
		unset($ad_month_liushui);
		
		$result["list"] = $list;
		unset($$list);
		return $result;
	}

	/**
	 * 供应商信息-导出
	 * @param  [type] $where   [description]
	 * @param  string $orderby [description]
	 * @return [type]          [description]
	 */
	function exportSupplyListSer($where,$orderby=""){
		$result = array();
		$sql = "SELECT 
				  s.name AS gys_name,
				  p.name AS pro_name,
				  p.charging_mode,
				  p.cooperate_state,
				  s.tag,
				  b.name AS line_name,
				  re.`name` AS diqu_name,
				  s.grade,
				  sc.`mobile`,
				  sc.`name` AS lxr_name,
				  p.id AS pro_id,
				  s.id AS gys_id,ef.visit_time,ef.result as f_result,
				   CASE 
				  WHEN p.cooperate_state=1 OR p.cooperate_state=2 THEN '合作中'
				  WHEN p.cooperate_state=3 THEN '已暂停'
				  END
				  AS cooperate_state_str
				FROM
				  `boss_supplier` AS s 
				  LEFT JOIN boss_charging_logo_assign AS cla 
				    ON cla.`sup_id` = s.`id` 
				  LEFT JOIN `boss_charging_logo` AS c 
				    ON c.id = cla.`cl_id` 
				  LEFT JOIN `boss_product` AS p 
				    ON p.id = c.`prot_id` 
				  LEFT JOIN `boss_business_line` AS b 
				    ON b.id = p.bl_id 
				  LEFT JOIN boss_region AS re 
				    ON re.id = s.region 
				  LEFT JOIN `boss_supplier_contacts` AS sc 
				    ON sc.`sp_id` = s.`id`  
				  LEFT JOIN 
					    (SELECT 
					      * 
					    FROM
					      boss_expand_follow a 
					    WHERE a.id IN 
					    	(SELECT 
						        MAX(id) AS id 
						      FROM
						        boss_expand_follow where type_id=6
						      GROUP BY expand_id
					        )
							and type_id=6
						) AS ef 
				    ON ef.expand_id = p.id 
			    {$where} GROUP BY p.id {$orderby}";
		
		$model    = new \Think\Model();
		// print_r($sql);exit;
		$list     = $model->query($sql);
		unset($sql);

		//计算供应商的上一年的总收入和成本
		$supply_ids    = $this->getSupplyId($list);
		$supply_ids    = empty($supply_ids)?"0":$supply_ids;
		$prev_year = date("Y",time());
		$star_time = $prev_year."-01-01";
		$end_time  = $prev_year."-12-31";
		$sql = "SELECT 
				  SUM(in_newmoney) as total_in,
				  SUM(out_newmoney) as total_out,
				  SUM(in_newmoney)-SUM(out_newmoney) AS total_liushui,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and out_superid in ({$supply_ids}) 
				GROUP BY out_superid  ";
		
		// print_r($sql);exit;
		$supply_total_list = $model->query($sql);
		$supply_liushui = array();
		foreach ($supply_total_list as $k => $v) {
			$one["total_in"]                   = $v["total_in"];
			$one["total_out"]                  = $v["total_out"];
			$one["total_liushui"]              = $v["total_liushui"];
			$supply_liushui[$v["out_superid"]] = $one;
			unset($one);
		}


		//计算供应商前日成本流水
		$prev_year = date("Y-m-d",strtotime("-2 day"));
		$star_time = $prev_year." 00:00:00";
		$end_time  = $prev_year." 23:59:59";
		$sql = "SELECT 
				  SUM(out_newmoney) as yestoday_total_in,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}'
				GROUP BY out_superid  ";
		// print_r($sql);exit;
		$ad_yestoday_total_list = $model->query($sql);
		$product_yestoday_liushui1 = array();
		foreach ($ad_yestoday_total_list as $k => $v) {
			$one["yestoday_total_in"] = $v["yestoday_total_in"];
			$product_yestoday_liushui1[$v["out_superid"]] = $one;
			unset($one);
		}


		//计算供应商当月的总成本流水
		$year_month = date("Y-m",time());
		$fact_days = $this->getMonthDays($year_month);
		$star_time = $year_month."-01";
		$end_time  = $year_month."-".$fact_days;
		$sql = "SELECT 
				  SUM(out_newmoney) as total_out,
				  out_superid 
				FROM
				  boss_daydata_inandout 
				WHERE ADDDATE>= '{$star_time}' 
				  AND ADDDATE<= '{$end_time}' and out_superid in ({$supply_ids}) 
				GROUP BY out_superid  ";
		$gys_month_list = $model->query($sql);
		$gys_month_liushui = array();
		foreach ($gys_month_list as $k => $v) {
			$gys_month_liushui[$v["out_superid"]]["total_out"] = $v["total_out"];
		}

		//获取计费标识下的商务，商务和供应商联系，供应商的流水都是算到这个商务头上的
		$all_business_list = $this->getbusinessList($supply_ids,$model);
		//第二次筛选
		$arr = $this->getTags();
		foreach ($list as $k => $vv) {
			$tag       =json_decode(htmlspecialchars_decode($vv['tag']),true);
			if($tag[0]['media_type'] && $tag[0]['resource_scale']){
				$str             =$tag[0]['media_type'].'('.$arr[$tag[0]['media_type']].'):'.$tag[0]['resource_scale'];
				$list[$k]['tag'] = $str;
				if(count($tag)>1)$str .=" ...";
			}
			//商务
			$sw_name             = $all_business_list[$vv["gys_id"]]["sw_name"];
			$list[$k]["sw_name"] = empty($sw_name)?"--":$sw_name;
			unset($sw_name);

			//前日成本流水
			$yestoday_total_in              = $product_yestoday_liushui1[$vv["gys_id"]]["yestoday_total_in"];
			$yestoday_total_in              = empty($yestoday_total_in)?0:$yestoday_total_in;
			$list[$k]["yestoday_total_in1"] = number_format($yestoday_total_in, 2, '.', ',');
			
			//上一年成本
			$total_out                      = $supply_liushui[$vv["gys_id"]]["total_out"];
			$total_out                      = empty($total_out)?0:$total_out;
			$list[$k]["total_out"]          = number_format($total_out, 2, '.', ',');
			
			//上一年利润
			$total_liushui                  = $supply_liushui[$vv["gys_id"]]["total_liushui"];
			$total_liushui                  = empty($total_liushui)?0:$total_liushui;
			$list[$k]["total_liushui"]      = number_format($total_liushui, 2, '.', ',');
			unset($total_liushui);
			unset($total_out);
			unset($yestoday_total_in);

			//月总流水
			$total_out                  = $gys_month_liushui[$v["gys_id"]]["total_out"];
			$total_out                  = empty($total_out)?0:$total_out;
			//根据月总流水评级
			$list[$k]["grade"] = $this->getGradesByLiushui($total_out);
			unset($total_out);

		}
		unset($arr);
		unset($all_business_list);

		$result = $list;
		unset($list);
		return $result;
	}

	/**
	 * 加载产品收入成本，利润，量级，日期
	 * @return [type] [description]
	 */
	function lazyProDataSer($params){
		$res = array("list"=>array(),"page"=>"","code"=>500);
		$where = "1=1";
		if(intval($params["ggz_name"])>0){
			$params["adverid"] = $params["ggz_name"];
		}
		if($params["adverid"]){
			$where .= " and in_adverid=".$params["adverid"];
		}
		$where .= " and adddate>='".$params["strtime"]."' and adddate<='".$params["endtime"]."'";
		$sql = "SELECT 
			  adddate,
			  SUM(in_newdata) AS in_newdata,
			  SUM(in_newmoney) AS in_newmoney,
			  SUM(out_newmoney) AS out_newmoney
			FROM
			  `boss_daydata_inandout` 
			WHERE {$where}
			GROUP BY adddate
			ORDER BY adddate  DESC ";

		$sql_count = "SELECT 
			  COUNT(DISTINCT adddate) as no
			FROM
			  `boss_daydata_inandout` 
			WHERE {$where}";

		$model = new \Think\Model();
		$count = $model->query($sql_count);
		$count = $count[0]["no"];
		$page  = new \Think\AjaxPage($count,10,"e.lazyProData");
		$show  = $page->show();

		//list
		$sql .= "LIMIT ".$page->firstRow.','.$page->listRows;
		// print_r($sql);exit;
		$list = $model->query($sql);
		if(!$list) return $res;
		foreach ($list as $k => $v) {
			$in_newdata               = empty($v["in_newdata"])?0:$v["in_newdata"];
			$list[$k]["in_newdata"]   = number_format($in_newdata,2, '.', ',');
			
			$in_newmoney              = empty($v["in_newmoney"])?0:$v["in_newmoney"];
			$list[$k]["in_newmoney"]  = number_format($in_newmoney,2, '.', ',');
			
			$out_newmoney             = empty($v["out_newmoney"])?0:$v["out_newmoney"];
			$list[$k]["out_newmoney"] = number_format($out_newmoney,2, '.', ',');
			
			$cb_money                 = $in_newmoney-$out_newmoney;
			$list[$k]["cb_money"]     = number_format($cb_money,2, '.', ',');

			unset($in_newdata);
			unset($in_newmoney);
			unset($out_newmoney);
			unset($cb_money);
		}
		$res = array("list"=>$list,"page"=>$show,"code"=>200);
		return $res;

	}

	/**
	 * 加载供应商 产品收入成本，利润，量级，日期
	 * @return [type] [description]
	 */
	function lazyGysProData($params){
		$res = array("list"=>array(),"page"=>"","code"=>500);
		$where = "1=1";
		if(intval($params["ggz_name"])>0){
			$params["gys_id"] = $params["ggz_name"];
		}
		if($params["gys_id"]){
			$where .= " and out_superid=".$params["gys_id"];
		}
		$where .= " and adddate>='".$params["strtime"]."' and adddate<='".$params["endtime"]."'";
		$sql = "SELECT 
			  adddate,
			  SUM(in_newdata) AS in_newdata,
			  SUM(in_newmoney) AS in_newmoney,
			  SUM(out_newmoney) AS out_newmoney
			FROM
			  `boss_daydata_inandout` 
			WHERE {$where}
			GROUP BY adddate
			ORDER BY adddate  DESC ";

		$sql_count = "SELECT 
			  COUNT(DISTINCT adddate) as no
			FROM
			  `boss_daydata_inandout` 
			WHERE {$where}";

		$model = new \Think\Model();
		$count = $model->query($sql_count);
		$count = $count[0]["no"];
		$page  = new \Think\AjaxPage($count,10,"e.lazyProData");
		$show  = $page->show();

		//list
		$sql .= "LIMIT ".$page->firstRow.','.$page->listRows;
		$list = $model->query($sql);
		if(!$list) return $res;
		foreach ($list as $k => $v) {
			$in_newdata               = empty($v["in_newdata"])?0:$v["in_newdata"];
			$list[$k]["in_newdata"]   = number_format($in_newdata,2, '.', ',');
			
			$in_newmoney              = empty($v["in_newmoney"])?0:$v["in_newmoney"];
			$list[$k]["in_newmoney"]  = number_format($in_newmoney,2, '.', ',');
			
			$out_newmoney             = empty($v["out_newmoney"])?0:$v["out_newmoney"];
			$list[$k]["out_newmoney"] = number_format($out_newmoney,2, '.', ',');
			
			$cb_money                 = $in_newmoney-$out_newmoney;
			$list[$k]["cb_money"]     = number_format($cb_money,2, '.', ',');

			unset($in_newdata);
			unset($in_newmoney);
			unset($out_newmoney);
			unset($cb_money);
		}
		$res = array("list"=>$list,"page"=>$show,"code"=>200);
		return $res;
	}
	
}

?>