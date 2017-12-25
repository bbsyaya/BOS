<?php
 /**
 * 风控service 
 */
namespace Home\Service;
use Think\Model;
use Common\Service;
class RiskCheckService extends CommonService
{
	
	/**
	 * 发票核对 ---发票收入-service
	 * @return [type] [description]
	 */
	function invoiceCheckService($url,$where,$map){
		$result = array("msg"=>"","data"=>"","status"=>500,"page"=>"","total_boss_jiesuan_money"=>0.00,"total_yongyou"=>0.00);
		$sql = 'SELECT 
				  s.id,
				  l.name AS lineid,
				  a.name AS advername,
				  p.name AS pname,
				  s.settlementmoney,
				  d.name AS jsztid,
				  d.dic_type,
				  s.invoicetime,
				  s.strdate,
				  s.enddate,i.invoice_no
				FROM
				  boss_settlement_in AS s 
				  LEFT JOIN boss_advertiser AS a 
				    ON s.advid = a.id 
				  LEFT JOIN `boss_product` AS p 
				    ON p.id = s.comid 
				  LEFT JOIN `boss_invoice` AS i 
				    ON i.income_st_id = s.id 
				  LEFT JOIN boss_data_dic AS d 
				    ON d.id = s.`jsztid` 
				  LEFT JOIN boss_business_line AS l 
				    ON l.id = s.`lineid` ';
	    //条数
		$sql_count = "SELECT 
					 COUNT(1) as num
					FROM
					  boss_settlement_in AS s 
					  LEFT JOIN boss_advertiser AS a 
					    ON s.advid = a.id 
					  LEFT JOIN `boss_product` AS p 
					    ON p.id = s.comid 
					  LEFT JOIN `boss_invoice` AS i 
					    ON i.income_st_id = s.id 
					  LEFT JOIN boss_data_dic AS d 
					    ON d.id = s.`jsztid` 
					  LEFT JOIN boss_business_line AS l 
					    ON l.id = s.`lineid` ";
	    $model = new \Think\Model();
	    $sql_count .= $where;
	    $count = $model->query($sql_count);
	    $count = $count[0]["num"];

		//---------查询出用友数据，组成以billNo(单据编号(boss系统收款单据号))为键的数据组
		$postArray   = $this->setValidate();
		$responsData = array();

		//---------查询当前条件下所有发票id到用友查询总金额
		$sql_sum  = $sql.$where;
	    $boss_list_invoice_no = $model->query($sql_sum);
	    $boss_all_invoice_no = "";
	    if($boss_list_invoice_no){
			$boss_all_invoice_no = $this->getInvoiceIds($boss_list_invoice_no);
			$url                 = C("VOUCHER_IP")."/api/Voucher/getRBillDataSum";
			$postArray["where"]  = "BillNo in ({$boss_all_invoice_no})";
			$responsData         = bossPostData($url,$postArray);
			$yy_list             = json_decode($responsData,true);
			//日志
			$this->writeFinLogs($url,$postArray,$responsData,100);
			if($yy_list["code"] == "0" && $yy_list["message"]=="success"){
				$total_yongyong = $yy_list["data"][0]["totalMoney"];
				$total_yongyong = $total_yongyong<=0?0:$total_yongyong;
				$result["total_yongyou"] = number_format($total_yongyong, 2, '.', ',');
			}
	    }

	    //获取boss数据
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$sql  .= $where." limit ".$page->firstRow.",".$page->listRows;
		// print_r($sql);exit;
		$boss_list = $model->query($sql);

		//计算总结算单金额
		$sql = "SELECT 
				  sum(s.settlementmoney) as total_boss_jiesuan_money 
				FROM
				  boss_settlement_in AS s 
				  LEFT JOIN boss_advertiser AS a 
				    ON s.advid = a.id 
				  LEFT JOIN `boss_product` AS p 
				    ON p.id = s.comid 
				  LEFT JOIN `boss_invoice` AS i 
				    ON i.income_st_id = s.id 
				  LEFT JOIN boss_data_dic AS d 
				    ON d.id = s.`jsztid` 
				  LEFT JOIN boss_business_line AS l 
				    ON l.id = s.`lineid`";
	    $sql  .= $where;
	    $total_boss_jiesuan_money = $model->query($sql);
	    unset($sql);
	    $total_boss_jiesuan_money = $total_boss_jiesuan_money[0]["total_boss_jiesuan_money"];
	    $result["total_boss_jiesuan_money"] = number_format($total_boss_jiesuan_money, 2, '.', ',');
	    unset($total_boss_jiesuan_money);


		//---------------用boss系统查询到的发票号到用友去查询，返回进行核对
		$invoiceIds = $this->getInvoiceIds($boss_list);
		$isPost     = false;//是否提交接口
		$url        = C("VOUCHER_IP").C("VOUCHER_URL.queryRbillData_Url");
		if(!empty($invoiceIds)){
			$postArray["where"] = "BillNo in ({$invoiceIds})";
			$responsData        = bossPostData($url,$postArray);
			$isPost             = true;
		}
		//---------------end 用boss系统查询到的发票号到用友去查询，返回进行核对
		$yy_list     = json_decode($responsData,true);

		//日志
		$this->writeFinLogs($url,$postArray,$responsData,3);

		$newYongYou  = array();
		if($yy_list["code"] == "0" && $yy_list["message"]=="success"){
			$list = $yy_list["data"];
			foreach ($list as $k => $v) {
				//凭证数据
				$v["csign"] = empty($v["csign"])?"":$v["csign"]."-";
				$pz_one["ino_id"]   = $v["csign"].$v["ino_id"];//凭证号
				$pz_one["itemName"] = $v["ItemName"];//业务线
				$pz_one["CusName"]  = $v["CusName"];//广告主
				// $pz_one["ItemCode"] = $v["itemCode"];//产品
				$pz_one["money"]    = $v["Money"];//金额 	
				$pz_one["dDate"]    = $v["dDate"];//记账时间
				// $newYongYou[$v["BillNo"]]["money"] = $pz_one["money"];
				$newYongYou[$v["BillNo"]]["pz_list"][] = $pz_one;
			}
		}else{
			if($isPost){
				$result["msg"] = "接口异常,重新查询试试";
				if($yy_list["message"] && $yy_list["message"]!="fail"){
					$result["msg"] = $result["msg"]."--".$yy_list["message"];
				}
				return $result;
			}
		}

		//两个数组组合合并成一个数组
		// print_r($newYongYou);exit;
		$invoice_list = array();
		// $total_yongyong = 0;//总的用友金额
		foreach ($boss_list as $k => $v) {
			$invoice_list[$v["invoice_no"]]["fp_list"][]  = $v;
			$pz_list_count  = count($newYongYou[$v["invoice_no"]]);
			if($pz_list_count>0){
				$invoice_list[$v["invoice_no"]]["pz_list"]    = $newYongYou[$v["invoice_no"]]["pz_list"];
				// $pz_money_ = $newYongYou[$v["invoice_no"]]["money"];
				// $total_yongyong = $total_yongyong + $pz_money_;//总的用友金额
			}
			$invoice_list[$v["invoice_no"]]["invoice_no"] = $v["invoice_no"];
		}
		$result["data"]          = $invoice_list;
		$result["status"]        = 200; 
		$result["page"]          = $page->show();
		return $result;
	}

	/**
	 * [getInvoiceIds description]
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	private function getInvoiceIds($list){
		if(!$list){ return false; }
		$ids = "";
		foreach ($list as $k => $v) {
			if($v["invoice_no"]){
				$ids .= "'".$v["invoice_no"]."',";
			} 
		}
		if($ids){
			$ids = substr($ids,0,strlen($ids)-1);
		}
		return $ids;
	}

	/**
	 * 获取收入总和信息
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	function getRiskIncomByTimeArea($params){
		$result = array("totalIncom"=>0,"sureIncom"=>0,"yy_finaTotal"=>0,"bankPay"=>0);
		//boss总收入
		$model = new \Think\Model();
		$start_time = $params["start"];
		$end_time   = $params["end"];
		$sql = "SELECT 
				  SUM(newmoney) AS total_newmoney 
				FROM
				  `boss_daydata` 
				WHERE ADDDATE>= '{$start_time}' 
				  AND ADDDATE<= '{$end_time}' 
				  AND STATUS NOT IN (0, 9)";
	    $total_newmoney = $model->query($sql);
	    $result["totalIncom"] = !empty($total_newmoney[0]["total_newmoney"]) ? $total_newmoney[0]["total_newmoney"] : 0;
	    $result["totalIncom"] = number_format($result["totalIncom"], 2, '.', '');
	    //BOS确认收入
	    $sql = "SELECT 
				  SUM(newmoney) AS total_newmoney 
				FROM
				  `boss_daydata` 
				WHERE ADDDATE>= '{$start_time}' 
				  AND ADDDATE<= '{$end_time}' 
				  AND STATUS  IN (2,3,4,5,8)";
	    $total_newmoney = $model->query($sql);
	    $result["sureIncom"] = !empty($total_newmoney[0]["total_newmoney"]) ? $total_newmoney[0]["total_newmoney"]:0;
	    $result["sureIncom"] = number_format($result["sureIncom"], 2, '.', '');
	    //用友收入---用条件查询出来的结算单id到用友查询一批数据B，boss结算单A。
	    //循环A，判断a是否在b中存在，存在直接continue;
	    //不存在分为 1.不跨月=>{1--Q(BOS确认收入金额) - }。
	    //2.跨月（) - j()）
	    $sql = "SELECT 
					s.strdate,
					s.enddate,
					l.name AS line_name,
					a.name AS ad_name,
					s.settlementmoney,
					s.id,
					r.payid,s.alljfid,d.adverid,d.money as dk_money
				FROM
				  `boss_settlement_in` AS s 
				  LEFT JOIN `boss_rkrecord` AS r 
				    ON r.skjsdid = s.id 
				  LEFT JOIN boss_business_line AS l 
				    ON l.id = s.`lineid` 
				  LEFT JOIN boss_advertiser AS a 
				    ON s.advid = a.id 
				LEFT JOIN boss_dkrecord AS d ON s.id=d.skjsdid
				WHERE s.strdate <= '{$end_time}' 
				  AND s.enddate >= '{$start_time}' and s.status in (4,5)";
				  // print_r($sql);
		$payid_list                               = $model->query($sql);
		if(!$payid_list){ $result["yy_finaTotal"] = "暂无收入结算单数据，请联系管理员查看"; }
		$payid_s                                  = $this->getPayIds($payid_list);
		$url                                      = C("VOUCHER_IP").C("VOUCHER_URL.queryAddMoneyData_Url");
		$postArray                                = $this->setValidate();
		$responsData                              = array();
		$isPost                                   = false;//是否提交接口
		if($payid_s){
			$postArray["where"] = " BankCode in ({$payid_s})";
			$responsData        = bossPostData($url,$postArray);
			$isPost             = true;//是否提交接口
		}
		$yy_list     = json_decode($responsData,true);
		//日志
		$this->writeFinLogs($url,$postArray,$responsData,1);
		$payKeyList = array();
		if($yy_list["code"] == "0" && $yy_list["message"]=="success"){
			$list         = $yy_list["data"];
			//得到boss数据,做成以payid为键的数组
			$payKeyList = $this->getPayIdMakePayKeyList($list);
		}else{
			if($isPost){$result["yy_finaTotal"] = "接口异常,重新查询试试";}
		}

		//收入总金额
		// $sql = "SELECT 
		// 		  SUM(newmoney) as total_incom
		// 		FROM
		// 		  `boss_daydata` 
		// 		WHERE STATUS IN (5, 8) 
		// 		  AND ADDDATE>= '{$start_time}' 
		// 		  AND ADDDATE<= '{$end_time}' ";
	 // 	$total_incom = $model->query($sql);
	 // 	$total_incom = $total_incom[0]["total_incom"];//收入总金额

		// print_r($payKeyList);exit;
		// Array
		// (
		// [1501] => Array
		// (
		//     [0] => Array
		//         (
		//             [ID] => 3397
		//             [dDate] => 2017-05-22 00:00:00
		//             [BankCode] => 1501
		//             [BankAccID] => 重庆趣玩8284
		//             [CusCode] => GG0000872
		//             [CusName] => 天津快友世纪科技有限公司
		//             [AccID] => 201
		//             [Money] => 19838.27
		//             [iyear] => 2017
		//             [iperiod] => 5
		//             [csign] => 记
		//             [ino_id] => 8
		//             [CreateDate] => 2017-06-13 12:00:00
		//         )

		// )

		// )
		// print_r(count($payid_list));print_r("<br>");
		//循环银行收入结算单
		// $sureIncom = $total_incom;
		$sureIncom = 0;
		foreach ($payid_list as $k => $v) {
			// print_r("pid=".$v["payid"]."-----".$v["strdate"]."----".$v["enddate"]."-------<br>");
			$hasPayList = $payKeyList[$v["payid"]];
			//*********************加法逻辑
			if(count($hasPayList)>0 || $v["adverid"]){
				//判断当前结算的起止时间是否跨月是否跨越
				$isCro = $this->isCrossMonth(date("Y",time()),$params["month"],$v["strdate"],$v["enddate"]);
				if($isCro["isCross"]){
					//跨月,加上结算单在本月内的金额
					if($v["alljfid"]){//在收入表中查询部分金额
						$sql = "SELECT 
								  SUM(newmoney) as ky_total
								FROM
								  boss_daydata 
								WHERE jfid IN (".$v["alljfid"].") 
								  AND ADDDATE>= '".$isCro["data"]["j_star"]."' 
								  AND ADDDATE<= '".$isCro["data"]["j_end"]."' ";
					    $cross_money         = $model->query($sql);
						$cross_money         = $cross_money[0]["ky_total"];
						if($cross_money){
							$sureIncom = $sureIncom+$cross_money;
						}
					}
				}else if($isCro["isCross"]==false){
					//不跨月,要么加用友money或者抵扣money
					if($v["adverid"]){
						$sureIncom = $sureIncom+$v["dk_money"];
					}else{
						foreach ($hasPayList as $kh => $vh) {
							if($vh["Money"]){
								// print_r($vh["Money"]);exit;
								$sureIncom = $sureIncom+$vh["Money"];
							}
						}
					}
					
				}
			}else{
				continue;
			}
			//*********************end 加法逻辑

			//****************减法逻辑
			// if(count($hasPayList)>0){
			// 	// print_r("continue<br>");
			// 	continue;
			// }else{
			// 	if($v["adverid"]){ continue; }
			// 	//判断当前结算的起止时间是否跨月是否跨越
			// 	$isCro = $this->isCrossMonth(date("Y",time()),$params["month"],$v["strdate"],$v["enddate"]);
			// 	if($isCro["isCross"]){
			// 		//跨月,减去重复日期的结算单金额
			// 		if($v["alljfid"]){//在收入表中查询部分金额
			// 			$sql = "SELECT 
			// 					  SUM(newmoney) as ky_total
			// 					FROM
			// 					  boss_daydata 
			// 					WHERE jfid IN (".$v["alljfid"].") 
			// 					  AND ADDDATE>= '".$isCro["data"]["j_star"]."' 
			// 					  AND ADDDATE<= '".$isCro["data"]["j_end"]."' ";
			// 		    $cross_money         = $model->query($sql);
			// 			$cross_money         = $cross_money[0]["ky_total"];
			// 			if($cross_money){
			// 				$sureIncom = $sureIncom-$cross_money;
			// 				// print_r($sql."----;m=".$sureIncom."---".$k."<br>");
			// 			}
						
						
			// 		}
			// 	}else if($isCro["isCross"]==false){
			// 		//不跨月，直接减当条的结算金额
			// 		if($v["settlementmoney"]){
			// 			$sureIncom = $sureIncom-$v["settlementmoney"];
			// 			// print_r(";m=".$sureIncom."---".$k."<br>");
			// 		}
					
			// 	}

			// }
			//****************end 减法逻辑
		}
		if($sureIncom<0){
			$sureIncom = 0.00;
		}
		$result["yy_finaTotal"] = number_format($sureIncom, 2, '.', '');//用友金额

		//银行流水收入
		$sql = "SELECT 
				  SUM(newmoney) AS total_newmoney 
				FROM
				  `boss_daydata` 
				WHERE ADDDATE>= '{$start_time}' 
				  AND ADDDATE<= '{$end_time}' 
				  AND STATUS  IN (5,8)";
	    $total_newmoney = $model->query($sql);
	    $result["bankPay"] = !empty($total_newmoney[0]["total_newmoney"]) ? $total_newmoney[0]["total_newmoney"] : 0;
	    $result["bankPay"] = number_format($result["bankPay"], 2, '.', '');
	    // exit;
		return $result;
	}

	/**
	 * 得到boss数据,做成以payid为键的数组
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function getPayIdMakePayKeyList($list){
		if(!$list){return false;}
		$nList = array();
		foreach ($list as $k => $v) {
			$nList[$v["BankCode"]][] = $v;
		}
		return $nList;
	}

	/**
	 * [getPayIds description]
	 * @param  [type] $payid_list [description]
	 * @return [type]             [description]
	 */
	function getPayIds($payid_list){
		if(!$payid_list){ return false; }
		$ids = "";
		foreach ($payid_list as $k => $v) {
			if($v["payid"]){
				$ids .= $v["payid"].",";
			} 
		}
		if($ids){
			$ids = substr($ids,0,strlen($ids)-1);
		}
		return $ids;
	}

	/**
	 * 获取收款凭证数据service 
	 * @param  [type] $where [description]
	 * @param  [type] $map   [description]
	 * @return [type]        [description]
	 */
	function inmoneyCheckService($where,$map){
		$result = array("msg"=>"","data"=>"","status"=>500,"page"=>"","total_boss_jiesuan_money"=>0,"total_yongyong"=>0,"total_bank"=>0);
		//1.查询boss数据 （boss_settlement_in--收入结算单表 ；）
		$sql_count = "SELECT 
					  count(1) as num
					FROM
					  `boss_settlement_in` AS s 
					  LEFT JOIN `boss_rkrecord` AS r 
					    ON r.skjsdid = s.id 
					  LEFT JOIN boss_business_line AS l 
					    ON l.id = s.`lineid` 
					  LEFT JOIN boss_advertiser AS a 
					    ON s.advid = a.id ";

		$sql = "SELECT 
				  s.strdate,
				  s.enddate,
				  l.name AS line_name,
				  a.name AS ad_name,
				  s.settlementmoney,
				  s.id,
				  r.payid
				FROM
				  `boss_settlement_in` AS s 
				  LEFT JOIN `boss_rkrecord` AS r 
				    ON r.skjsdid = s.id 
				  LEFT JOIN boss_business_line AS l 
				    ON l.id = s.`lineid` 
				  LEFT JOIN boss_advertiser AS a 
				    ON s.advid = a.id";
		$model = new \Think\Model();
	    $sql_count .= $where;
	    $count = $model->query($sql_count);
	    $count = $count[0]["num"];

	    $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page = new \Think\Page($count, $listRows);
		$sql  .= $where." order by s.strdate asc "." limit ".$page->firstRow.",".$page->listRows;
		$boss_list = $model->query($sql);

		//1-1.查询出boss当月的总结算单金额---total_boss_jiesuan_money
		$sql = "SELECT 
				 sum(s.settlementmoney) as total_money
				FROM
				  `boss_settlement_in` AS s 
				  LEFT JOIN `boss_rkrecord` AS r 
				    ON r.skjsdid = s.id 
				  LEFT JOIN boss_business_line AS l 
				    ON l.id = s.`lineid` 
				  LEFT JOIN boss_advertiser AS a 
				    ON s.advid = a.id";
	    $boss_total_sql = $sql.$where;
	    $total_money = $model->query($boss_total_sql);
	    $total_money = $total_money[0]["total_money"];
	    $total_money = $total_money>=0?$total_money:0;
	    $result["total_boss_jiesuan_money"] = number_format($total_money, 2, '.', ',');
	    unset($sql);unset($total_money);unset($boss_total_sql);

		//2.查询出用友数据，组成以BankCode(银行流水单据编号)为键的数据组
		$boss_all_list_sql = "SELECT 
							  r.payid
							FROM
							  `boss_settlement_in` AS s 
							  LEFT JOIN `boss_rkrecord` AS r 
							    ON r.skjsdid = s.id 
							  LEFT JOIN boss_business_line AS l 
							    ON l.id = s.`lineid` 
							  LEFT JOIN boss_advertiser AS a 
							    ON s.advid = a.id";
	    $boss_all_list_sql .= $where;
	    $boss_all_list_payid = $model->query($boss_all_list_sql);
	    unset($boss_all_list_sql);

		$all_payid_s     = $this->getPayIds($boss_all_list_payid);//当前条件下所有payid
		unset($boss_all_list_payid);
		$url         = C("VOUCHER_IP").C("VOUCHER_URL.queryAddMoneyData_Url");
		$postArray   = $this->setValidate();
		$responsData = array();
		$isPost      = false;//是否提交接口
		if($all_payid_s){
			$postArray["where"] = "BankCode in ({$all_payid_s})";
			$responsData        = bossPostData($url,$postArray);
			$isPost             = true;//是否提交接口
		}
		$this->writeFinLogs($url,$postArray,$responsData,2);//日志
		$yy_list        = json_decode($responsData,true);
		$newYongYou     = array();
		$yongyou_total_ = 0;//总的用友金额
		if($yy_list["code"] == "0" && $yy_list["message"]=="success"){
			$list = $yy_list["data"];
			unset($yy_list);
			foreach ($list as $k => $v) {
				//凭证数据
				$yyone["csign_ino_id"]                 = empty($v["csign"])?"":$v["csign"]."-";
				$yyone["csign_ino_id"]                 = $yyone["csign_ino_id"].$v["ino_id"];//凭证号
				$yyone["cusName"]                      = $v["CusName"];//业务线
				$yyone["money"]                        = $v["Money"];//金额
				$yongyou_total_                        = $yongyou_total_+$v["Money"];
				$yyone["dDate"]                        = $v["dDate"];//记账时间
				$newYongYou[$v["BankCode"]]["pz_num"]  = $v["csign"];
				$newYongYou[$v["BankCode"]]["yy_list"] = $yyone;
				unset($yyone);
			}

		}else{
			if($isPost){
				$result["msg"] = "接口异常,重新查询试试";
				return $result;
			}
		}
		//2-1.查询用友满足条件的总金额
		$result["total_yongyong"] = number_format($yongyou_total_, 2, '.', ',');

		//两个数组组合合并成一个数组
		$invoice_list = array();
		foreach ($boss_list as $k => $v) {
			$invoice_list[$v["payid"]]["boss_list"][] = $v;
			if($newYongYou[$v["payid"]]["yy_list"]){
				$invoice_list[$v["payid"]]["pz_list"][]   = $newYongYou[$v["payid"]]["yy_list"];

				//判断当前结算的起止时间是否跨月是否跨越
				// $isCro = $this->isCrossMonth(date("Y",time()),$params["month"],$v["strdate"],$v["enddate"]);
				// if($isCro["isCross"]){
				// 	//跨月
				// 	$sql = "SELECT 
				// 			  SUM(s.settlementmoney) as ky_total
				// 			FROM
				// 			  `boss_settlement_in` AS s 
				// 			WHERE s.strdate >= '".$isCro["isCross"]."' 
				// 			  AND s.enddate <= '".$isCro["isCross"]."' ";
				// 	$cross_money         = $model->query($sql);
				// 	$cross_money         = $cross_money[0]["ky_total"];
				// 	$result["sureIncom"] = $result["sureIncom"]-$cross_money;
				// }else{
				// 	//不跨月，直接减当条的结算金额
				// 	$result["sureIncom"] = $result["sureIncom"]-$v["settlementmoney"];
				// }
			}
			$invoice_list[$v["payid"]]["csign"]     = $newYongYou[$v["payid"]]["pz_num"];
			$invoice_list[$v["payid"]]["payid"]     = $v["payid"];
			//获取银行流水信息
			$v["payid"] = !empty($v["payid"])?$v["payid"]:"0";
			$sql = "SELECT 
						  adddate,
						  paymentname,
						  receivablesname,
						  money 
						FROM
						  `boss_pay` 
						WHERE id =".$v["payid"];
			$list = $model->query($sql);
			unset($sql);
			$invoice_list[$v["payid"]]["bank_list"] = $list;
			unset($list);
		}

		//4.获取银行流水总额
		$sql = "SELECT 
					sum(money) as total_money
				FROM
				  `boss_pay` 
				WHERE id in ({$all_payid_s})";
		$all_bank_money = $model->query($sql);
		$all_bank_money = $all_bank_money[0]["total_money"];
		$all_bank_money = $all_bank_money>=0?$all_bank_money:0;
		$result["total_bank"] = number_format($all_bank_money, 2, '.', ',');
		unset($all_bank_money);unset($sql);unset($all_payid_s);

		$result["data"]   = $invoice_list;
		$result["status"] = 200; 
		$result["page"]   = $page->show();
		unset($invoice_list);
		return $result;
	}

	/**
	 * [获取成本数据 description]
	 * @return [type] [description]
	 */
	function getRiskOutcomByTimeArea($params){
		$result = array("totalIncom"=>0,"sureIncom"=>0,"yy_finaTotal"=>0,"bankPay"=>0);
		//boss总成本
		$model      = new \Think\Model();
		$start_time = $params["start"];
		$end_time   = $params["end"];
		$sql = "SELECT 
				  SUM(newmoney) AS total_newmoney 
				FROM
				  `boss_daydata_out` 
				WHERE ADDDATE>= '{$start_time}' 
				  AND ADDDATE<= '{$end_time}' 
				  AND STATUS NOT IN (0, 9)";
	    $total_newmoney = $model->query($sql);
	    $result["totalIncom"] = !empty($total_newmoney[0]["total_newmoney"]) ? $total_newmoney[0]["total_newmoney"] : 0;
	    $result["totalIncom"] = number_format($result["totalIncom"], 2, '.', '');

	    //BOS确认成本
	    $sql = "SELECT 
				  SUM(newmoney) AS total_newmoney 
				FROM
				  `boss_daydata_out` 
				WHERE ADDDATE>= '{$start_time}' 
				  AND ADDDATE<= '{$end_time}' 
				  AND STATUS  IN (2,3,4,5,6)";
	    $total_newmoney = $model->query($sql);
	    $result["sureIncom"] = !empty($total_newmoney[0]["total_newmoney"]) ? $total_newmoney[0]["total_newmoney"]:0;
	    $result["sureIncom"] = number_format($result["sureIncom"], 2, '.', '');

	    //boss成本结算单id 集合
	     $sql = "SELECT 
				  s.id,s.strdate,s.enddate,s.settlementmoney,s.alljfid,d.`adverid`,d.`time`
				FROM
				  `boss_settlement_out` as s
				   LEFT JOIN boss_dkrecord AS d ON s.id=d.skjsdid
				WHERE s.strdate<= '{$end_time}' AND s.enddate>= '{$start_time}'  and s.status in (4,5)";
	    $js_id_list = $model->query($sql);
		$out_ids_result = $this->getOutIds($js_id_list);
		$out_ids_where  = $out_ids_result["ids_where"];

	    //用友成本
		$url         = C("VOUCHER_IP").C("VOUCHER_URL.queryPamentData_Url");
		$postArray   = $this->setValidate();
		$responsData = array();
		$isPost      = false;//是否post
		if($out_ids_where){
			$postArray["where"] = $out_ids_where;
			$responsData        = bossPostData($url,$postArray);
			$isPost             = true;
		}
		//日志
		$this->writeFinLogs($url,$postArray,$responsData,5);
		$list            = json_decode($responsData,true);
		$pz_billCode_str = "";
		if($list["code"] == "0" && $list["message"]=="success"){
			$list         = $list["data"];
			//循环凭证list，组成凭证字符串
			foreach ($list as $k => $v) {
				$pz_billCode_str .= $v["BillCode"].",";
			}
		}else{
			if($isPost){
				$result["yy_finaTotal_msg"] = "接口异常,重新查询试试";
			}
		}
		
		$pz_code_list = explode(",", $pz_billCode_str);
		$jsd_where = "";//结算单where字符串
		$sureIncom = $result["sureIncom"];//用友确认成本
		// $sureIncom = 0.00;//用友确认成本
		//循环成本结算单
		foreach ($js_id_list as $k => $v) {
			$jsd_where .= "data_150 like '%".$v["id"].",%' or ";
			//****************加法
			// if(in_array($v["id"], $pz_code_list) || $v["adverid"]){
			// 	//不存在,判断当前结算的起止时间是否跨月是否跨越
			// 	$isCro = $this->isCrossMonth(date("Y",time()),$params["month"],$v["strdate"],$v["enddate"]);
			// 	if($isCro["isCross"]){
			// 	}else if($isCro["isCross"]==false){
			// 		//不跨月
			// 		if($v["settlementmoney"]>0){
			// 			$sureIncom = $sureIncom-$v["settlementmoney"];
			// 		}
			// 	}
			// }else{
			// 	continue;
			// }
			//****************加法
			//判断当前结算单是否存在在凭证中
			//****************减法
			if(in_array($v["id"], $pz_code_list)){
				continue;
			}else{
				//不存在,判断当前结算的起止时间是否跨月是否跨越
				$isCro = $this->isCrossMonth(date("Y",time()),$params["month"],$v["strdate"],$v["enddate"]);
				if($isCro["isCross"]){
					//跨月
					if($v["alljfid"]){//在成本表中查询部分金额
						$sql = "SELECT 
								  SUM(newmoney) as ky_total
								FROM
								  boss_daydata_out 
								WHERE jfid IN (".$v["alljfid"].") 
								  AND adddate>= '".$isCro["data"]["j_star"]."' 
								  AND adddate<= '".$isCro["data"]["j_end"]."' ";
					    $cross_money = $model->query($sql);
						$cross_money = $cross_money[0]["ky_total"];
						if($cross_money>0){
							$sureIncom   = $sureIncom-$cross_money;
						}
					}
				}else if($isCro["isCross"]==false){
					//不跨月，直接减当条的结算金额
					if($v["settlementmoney"]>0){
						$sureIncom = $sureIncom-$v["settlementmoney"];
					}
				}
			}
			//***************end减法
		}
		if($sureIncom<0){$sureIncom=0.00;}
		$result["yy_finaTotal"] = number_format($sureIncom, 2, '.', '');//用友金额

		//计算成本流水
		$result["bankPay"] = 0;//成本银行流水总额
		if($jsd_where){ 
			$jsd_where .= "1=1";
			$sql = "SELECT 
					  data_150,
					  pay_money,
					  pay_num,
					  pay_date 
					FROM
					  `boss_flow_data_432` where {$jsd_where}";
			$jsd_ls_list         = $model->query($sql);//成本结算单流程list
			$jsd_keys_list       = array();//成本流程所有的结算单list
			$jsd_data150key_list = array();//以data_150可以key的list
		    foreach ($jsd_ls_list as $k => $v) {
		    	if($v["data_150"]){
		    		//以data_150为key的数组
		    		$jsd_data150key_list[$v["data_150"]] = $v;
	    			array_push($jsd_keys_list, $v["data_150"]);
		    	}else{ continue; }
		    }
		    //循环成本结算单
		    $cur_has_lc_list = array();//当前已确定的成本流程list
		    foreach ($js_id_list as $k => $v) {
				$lc_data150_key    = "";
				$can_get_pay_money = fales;//是否可以获取成本流程的支付金额
		    	foreach ($jsd_keys_list as $kk => $vv) {
		    		$vv_list = explode(",", $vv);
		    		if(in_array($v["id"], $vv_list)){//当前结算单id存在在成本流程data_150中
		    			$lc_data150_key = $vv;
		    			//判断是否在
		    			if(in_array($vv, $cur_has_lc_list)==false){
		    				array_push($cur_has_lc_list, $vv);//将成本data_150字段放入list中
		    				$can_get_pay_money = true;
		    			}
		    		}else{ continue; }
		    	}
		    	if($can_get_pay_money){
					$lc_one            = $jsd_data150key_list[$lc_data150_key];
					$result["bankPay"] = $result["bankPay"]+$lc_one["pay_money"];
		    	}
		    }
		    $result["bankPay"] = number_format($result["bankPay"], 2, '.', '');//格式化
		}
		return $result;
	}


	/**
	 * [getInvoiceIds description]
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	private function getOutIds($list){
		if(!$list){ return false; }
		$result = array("ids_where"=>"","current_page_ids"=>"");
		foreach ($list as $k => $v) {
			if($v["id"]){
				$result['ids_where'] .= "BillCode like '%".$v["id"].",%' or ";
				$result['current_page_ids'] .= $v["id"].",";
			} 
		}
		if($result['ids_where']){
			$result['ids_where'] .= "1=1";
			$result['current_page_ids'] = substr($result['current_page_ids'], 0,strlen($result['current_page_ids'])-1);
		}
		return $result;
	}

	/**
	 * 获取成本凭证数据service 
	 * @param  [type] $where [description]
	 * @param  [type] $map   [description]
	 * @return [type]        [description]
	 */
	function outmoneyCheckService($where,$map){
		$result = array("msg"=>"","data"=>"","status"=>500,"page"=>"");
		//1.查询boss数据 boss_settlement_out---成本结算单表；）
		$sql_count = "SELECT 
						  COUNT(1) AS num 
						FROM
						  boss_settlement_out AS s 
						  LEFT JOIN `boss_business_line` AS l 
						    ON l.id = s.lineid 
						  LEFT JOIN boss_supplier AS su 
						    ON su.id = s.`superid` ";

		$sql = "SELECT 
				  s.strdate,
				  s.enddate,
				  l.name AS line_name,
				  s.settlementmoney AS yinshou_money,
				  s.id,su.name as suname
				FROM
				  boss_settlement_out AS s 
				  LEFT JOIN `boss_business_line` AS l 
				    ON l.id = s.lineid 
				  LEFT JOIN boss_supplier AS su 
				    ON su.id = s.`superid` ";
		$model = new \Think\Model();
	    $sql_count .= $where;
	    $count = $model->query($sql_count);
	    $count = $count[0]["num"];

	    $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page = new \Think\Page($count, $listRows);
		$sql  .= $where." order by s.strdate asc "." limit ".$page->firstRow.",".$page->listRows;
		$boss_list = $model->query($sql);
		unset($sql);



		//2.查询出用友数据 : BillCode-单据编号(银行付款数据ID)
		$url              = C("VOUCHER_IP").C("VOUCHER_URL.queryPamentData_Url");
		$out_ids_result   = $this->getOutIds($boss_list);
		$out_ids_where    = $out_ids_result["ids_where"];
		$current_page_ids = $out_ids_result["current_page_ids"];//当前页的结算单id集合
		$postArray        = $this->setValidate();
		$isPost           = false;
		if($out_ids_where){
			$postArray["where"] = $out_ids_where;
			$responsData        = bossPostData($url,$postArray);
			$isPost             = false;
		}
		$this->writeFinLogs($url,$postArray,$responsData,6);//日志
		
		$yy_list           = json_decode($responsData,true);
		$pz_list           = array();
		$pz_billCode_array = array();
		if($yy_list["code"] == "0" && $yy_list["message"]=="success"){
			$list = $yy_list["data"];
			foreach ($list as $k => $v) {
				//先获取凭证数据所有的billcodez值作为数组
				$pz_billCode_array[] = $v["BillCode"];

				// //凭证数据
				$yyone["csign_ino_id"]              = empty($v["csign"])?"":$v["csign"]."-";
				$yyone["csign_ino_id"]              = $yyone["csign_ino_id"].$v["ino_id"];//凭证号
				$yyone["cVenName"]                  = $v["cVenName"];//供应商名称
				$yyone["money"]                     = $v["Money"];//金额
				$yyone["dDate"]                     = $v["dDate"];//记账时间
				$pz_list[$v["BillCode"]]["pz_list"] = $yyone;
				$pz_list[$v["BillCode"]]["pz_num"]  = $v["csign"];
			}

		}else{
			if($isPost){
				$result["msg"] = "接口异常,重新查询试试";
				return $result;
			}
		}

		$invoice_list       = array();
		//循环成本结算单
		$noIndex            = 0;
		$cur_has_cbjsd_list = array();//存储已查询到的结算单id
		foreach ($boss_list as $k => $v) {
			//寻找boss中的id在哪一组$pz_billCode_array中
			$pz_billCode_key = "";
			foreach ($pz_billCode_array as $kk => $vv) {
				$vv_list = explode(",", $vv);
				if(in_array($v["id"], $vv_list)){
					$pz_billCode_key = $vv;
					break;
				}
			}

			if($pz_list[$pz_billCode_key]){
				//判断$pz_billCode_key是否已经存在在$cur_has_cbjsd_list，有：直接跳过
				if(in_array($pz_billCode_key, $cur_has_cbjsd_list)){
					continue;
				}
				array_push($cur_has_cbjsd_list, $pz_billCode_key);
				$cb_lc_data150 = $pz_billCode_key;

				//检查凭证号是否在当前页的结算单id中
				$pz_num_list           = explode(",",$pz_billCode_key);
				$current_page_ids_list = explode(",",$current_page_ids);
				$BillCode_ids          = "";
				foreach ($pz_num_list as $kb => $vb) {
					if(in_array($vb, $current_page_ids_list)){
						$BillCode_ids = $vb.",";
					}else{ continue; }
				}
				$BillCode_ids  = empty($BillCode_ids)?"0":rtrim($BillCode_ids,","); 
				$sql = "SELECT 
						  s.strdate,
						  s.enddate,
						  l.name AS line_name,
						  s.settlementmoney AS yinshou_money,
						  s.id,su.name as suname
						FROM
						  boss_settlement_out AS s 
						  LEFT JOIN `boss_business_line` AS l 
						    ON l.id = s.lineid 
						    LEFT JOIN boss_supplier AS su 
				    		ON su.id = s.`superid`
						WHERE s.id IN ({$BillCode_ids})";
				$invoice_list[$pz_billCode_key]["boss_list"] = $model->query($sql);
				if($pz_list[$pz_billCode_key]["pz_list"]){
					$invoice_list[$pz_billCode_key]["pz_list"][]   = $pz_list[$pz_billCode_key]["pz_list"];
				}
				
				$invoice_list[$pz_billCode_key]["csign"]     = $pz_list[$pz_billCode_key]["pz_num"];
				//获取成本流程银行流水
				$sql = "SELECT 
						  data_150,
						  pay_money,
						  pay_num,
						  pay_date 
						FROM
						  `boss_flow_data_432` where data_150='{$cb_lc_data150}'";
			    $invoice_list[$pz_billCode_key]["bank_list"] = $model->query($sql);
			    $invoice_list[$pz_billCode_key]["pay_id_str"] = $pz_billCode_key;
			}else{
				//未找到
				$invoice_list[$noIndex]["boss_list"][] = $v;
				$invoice_list[$noIndex]["pay_id_str"] = $noIndex;
				$noIndex++;
			}
		}
		// exit;
		$result["data"]   = $invoice_list;
		$result["status"] = 200; 
		$result["page"]   = $page->show();
		return $result;
	}

	/**
	 * [获取更多发票凭证 description]
	 * @param  [type] $invoinceNo [description]
	 * @return [type]             [description]
	 */
	function getMoreInvoinceDataSer($invoinceNo){
		if(!$invoinceNo){ return false; }
		$url                = C("VOUCHER_IP").C("VOUCHER_URL.queryRbillData_Url");
		$postArray          = $this->setValidate();
		$postArray["where"] = "BillNo='{$invoinceNo}'";
		$responsData        = bossPostData($url,$postArray);
		$yy_list            = json_decode($responsData,true);
		$result = array("msg"=>"","data"=>array(),"code"=>0);
		if($yy_list["code"] == "0" && $yy_list["message"]=="success"){
			$list = $yy_list["data"];
			$total_yy = 0;
			foreach ($list as $k => $v) {
				//凭证数据
				$yyone["csign_ino_id"] = empty($v["csign"])?"":$v["csign"]."-";
				$yyone["csign_ino_id"] = $yyone["csign_ino_id"].$v["ino_id"];//凭证号
				$yyone["cusName"]      = $v["CusName"];//业务线
				$yyone["itemName"]     = $v["ItemName"];//业务线
				$yyone["money"]        = $v["Money"];//金额
				$yyone["dDate"]        = date("m.d",strtotime($v["dDate"]));//记账时间
				$result["data"][]      = $yyone;
			}
		}else{
			$result["msg"] = "接口异常,重新查询试试";
			$result["code"] = 500;
			return $result;
		}
		return $result;

	}

	/**
	 * 获取成本总计
	 * @param  [type] $where [description]
	 * @param  [type] $map   [description]
	 * @return [type]        [description]
	 */
	function getOutMoneyDataTotalByWhere($where,$map){
		$result = array("msg"=>"加载失败，重新刷新尝试","status"=>500,"total_boss_jiesuan_money"=>0,"total_yongyong"=>0,"total_bank"=>0);
		$model = new \Think\Model();
		//1.获取boss总金额
		$sql = "SELECT 
				  sum(s.settlementmoney) as total_money
				FROM
				  boss_settlement_out AS s 
			    LEFT JOIN `boss_business_line` AS l 
				    ON l.id = s.lineid 
				LEFT JOIN boss_supplier AS su 
				    ON su.id = s.`superid` ";
	    $sql .= $where;
	    $boss_current_total_money = $model->query($sql);
	    unset($sql);
	    $boss_current_total_money = $boss_current_total_money[0]["total_money"];
	    $boss_current_total_money = $boss_current_total_money>=0?$boss_current_total_money:0;
	    $result["total_boss_jiesuan_money"] = number_format($boss_current_total_money, 2, '.', ',');
	    unset($boss_current_total_money);

	    //2.获取用友总金额
	    $sql = "SELECT 
				  s.strdate,
				  s.enddate,
				  l.name AS line_name,
				  s.settlementmoney AS yinshou_money,
				  s.id,su.name as suname
				FROM
				  boss_settlement_out AS s 
				  LEFT JOIN `boss_business_line` AS l 
				    ON l.id = s.lineid 
				  LEFT JOIN boss_supplier AS su 
				    ON su.id = s.`superid` ";
	    $sql .= $where;
	    $boss_all_list = $model->query($sql);

	    //查询用友
		$url                    = C("VOUCHER_IP").C("VOUCHER_URL.queryPamentData_Url");
		$out_ids_result         = $this->getOutIds($boss_all_list);
		$out_ids_where          = $out_ids_result["ids_where"];
		$current_conditions_ids = $out_ids_result["current_page_ids"];//当前条件下的结算单id集合
		$postArray              = $this->setValidate();
		$isPost                 = false;
		if($out_ids_where){
			$postArray["where"] = $out_ids_where;
			$responsData        = bossPostData($url,$postArray);
			$isPost             = false;
		}
		$this->writeFinLogs($url,$postArray,$responsData,106);//日志
		
		$yy_list           = json_decode($responsData,true);
		$pz_list           = array();
		$pz_billCode_array = array();
		if($yy_list["code"] == "0" && $yy_list["message"]=="success"){
			$list = $yy_list["data"];
			unset($yy_list);
			foreach ($list as $k => $v) {
				//先获取凭证数据所有的billcodez值作为数组
				$pz_billCode_array[] = $v["BillCode"];
				$yyone["money"]                     = $v["Money"];//金额
				$pz_list[$v["BillCode"]]["pz_list"] = $yyone;
				unset($yyone);
			}

		}else{
			if($isPost){
				$result["msg"] = "接口异常,重新查询试试";
				return $result;
			}
		}

		//循环成本结算单
		$cur_has_cbjsd_list = array();//存储已查询到的结算单id
		$total_yongyong_    = 0;
		$total_bank_        = 0;
		foreach ($boss_all_list as $k => $v) {
			//寻找boss中的id在哪一组$pz_billCode_array中
			$pz_billCode_key = "";
			foreach ($pz_billCode_array as $kk => $vv) {
				$vv_list = explode(",", $vv);
				if(in_array($v["id"], $vv_list)){
					$pz_billCode_key = $vv;
					break;
				}
			}

			if($pz_list[$pz_billCode_key]){
				//判断$pz_billCode_key是否已经存在在$cur_has_cbjsd_list，有：直接跳过
				if(in_array($pz_billCode_key, $cur_has_cbjsd_list)){
					continue;
				}
				array_push($cur_has_cbjsd_list, $pz_billCode_key);
				$cb_lc_data150 = $pz_billCode_key;


				//计算用友总金额
				if($pz_list[$pz_billCode_key]["pz_list"]["money"]){
					$yy_jsd_money   = $pz_list[$pz_billCode_key]["pz_list"]["money"];
					$total_yongyong_ = $total_yongyong_+ $yy_jsd_money;
					unset($yy_jsd_money);
				}

				//获取成本流程银行流水
				$sql = "SELECT 
						  pay_money
						FROM
						  `boss_flow_data_432` where data_150='{$pz_billCode_key}'";
				$bank_res    = $model->query($sql);
				$bank_res    = $bank_res[0]["pay_money"];
				$bank_res    = $bank_res>=0?$bank_res:0;
				$total_bank_ = $total_bank_+$bank_res;
			}
		}

		$result["total_yongyong"] = number_format($total_yongyong_, 2, '.', ',');
		$result["total_bank"] = number_format($total_bank_, 2, '.', ',');
		$result["status"] = 200; 
	    return $result;

	}

}
?>