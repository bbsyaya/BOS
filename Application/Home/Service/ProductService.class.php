<?php
/**
* 产品service
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class ProductService
{
	/**
	 * 修改产品监听日志记录
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	function listenProductsUpdates($params){
		$post_["product_id"]          = trim($params["id"]);//产品id
		$post_["name"]                = trim($params["name"]);//产品名称
		$post_["price"]               = trim($params["price"]);//价格
		$post_["package_return_type"] = trim($params["package_return_type"]);//返量方式
		$post_["price_type"]          = trim($params["price_type"]);//价格类型 ，2：页面显示0
		
		$post_["ad_id"]               = trim($params["ad_id"]);//广告主名称id
		$post_["show_ad_id"]          = trim($params["show_ad_id"]);//广告主名称
		
		$post_["show_sb_id"]          = trim($params["show_sb_id"]);//签订主体
		$post_["sb_id"]               = trim($params["sb_id"]);//签订主体id

		$sql = "SELECT 
				  p.name,
				  p.`package_return_type`,
				  p.price,
				  a.`name` as ad_name,
				  d.`name` as sb_name
				FROM
				  `boss_product` AS p 
				  LEFT JOIN `boss_advertiser` AS a 
				    ON p.`ad_id` = a.`id` 
				  LEFT JOIN `boss_data_dic` AS d 
				    ON d.id = p.`sb_id` 
				WHERE p.id = ".$post_["product_id"]."
				  AND d.`dic_type` = 4 limit 1";
		$model   = new \Think\Model();
		$proOne  = $model->query($sql);
		$proOne  = $proOne[0];
		$message = array();
		$update_time = date("Y-m-d H:i:s",time());
		$currentUser = M("user")->field("real_name")->where(array("id"=>$params["cur_uid"]))->find();
		if($proOne){
			//产品名称变动
			if(trim($proOne["name"]) != $post_["name"]){
				$str["msg"]     = $currentUser["real_name"]."在".$update_time."变更产品名称：".$proOne["name"]."=>".$post_["name"];
				$str["type_id"] = 1;
				$message[]      = $str;
			}

			//产品价格变动
			//如果类型为2,和html页面保持一致为0
			if($post_["price_type"]==2){
				$proOne["price"] = 0;
			}
			if(trim($proOne["price"]) != $post_["price"]){
				$str["msg"]     = $currentUser["real_name"]."在".$update_time."变更产品价格：".$proOne["price"]."=>".$post_["price"];
				$str["type_id"] = 2;
				$message[]      = $str;
			}

			//返量方式变动
			if($proOne["package_return_type"] != $post_["package_return_type"]){
				$str["msg"]     = $currentUser["real_name"]."在".$update_time."变更返量方式：".$proOne["package_return_type"]."=>".$post_["package_return_type"];
				$str["type_id"] = 3;
				$message[]      = $str;
			}

			//广告主名称变动
			if(trim($proOne["ad_name"]) != trim($post_["show_ad_id"])){
				$str["msg"]     = $currentUser["real_name"]."在".$update_time."变更广告主名称：".$proOne["ad_name"]."=>".$post_["show_ad_id"];
				$str["type_id"] = 4;
				$message[]      = $str;
			}

			//签订主体变动
			if(trim($proOne["sb_name"]) != $post_["show_sb_id"]){
				$str["msg"]     = $currentUser["real_name"]."在".$update_time."变更签订主体：".$proOne["sb_name"]."=>".$post_["show_sb_id"];
				$str["type_id"] = 5;
				$message[]      = $str;
			}
		}

		if($message){
			$logSer = new Service\SysOperationLogService();
			foreach ($message as $k => $v) {
				$one              = array();
				$one["remark"]    = $v["msg"];
				$one["type"]      = $v["type_id"];
				$one["uid"]       = $params["cur_uid"];
				$one["customize"] = $post_["product_id"];
				$row = $logSer->writeLog($one);
			}
		}

	}

	/**
	 * 导出产品修改记录
	 * @return [type] [description]
	 */
	function explortProductUpdateRecSer(){
		$product_id = empty(trim(I("proid")))?"0":trim(I("proid"));
		$sql = "SELECT 
				  s.dateline,
				  u.real_name,
				  s.remark 
				FROM
				  `boss_sys_operation_log` AS s 
				  LEFT JOIN `boss_user` AS u 
				    ON s.uid = u.id 
				WHERE customize = {$product_id} 
				ORDER BY dateline DESC ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){
			return $return["msg"] = "无修改记录";
		}

		$excSer = new ExcelLogicService();
		$filename = time()."--".$product_id."--修改记录";
		$excSer->explortProductUpdateRec($list,$filename,$filename);

	}

	/**
 	 * 逾期测试产品通知
 	 * 1.测试到期当天的通知
 	 * 2.测试逾期7天/金额超过5万的通知
 	 * @return [type] [description]
 	 */
	function synOverdueTestProductTipSer(){
		$userData = M('user')->field('id')->where("username='tanbo'")->find();
		$user_n   = M('user')->field('id')->where("username IN ('yaowang','liufanfan','wanglei','wangrongting')")->select();
		$usid     = '';
        foreach($user_n as $key2=>$val2){
            $usid .=$val2['id'].",";
        }
        $usid = rtrim($usid,",");

		$proData = M('product')->field('id,`name`,saler_id,order_test_type,order_test_quota')->where("cooperate_state=2")->select();

		foreach($proData as $key=>$val){
            $user_id = $val['saler_id'].','.$userData['id'];
            $content = "产品".'"'.$val['name'].'"'." 测试已到期，目前仍在测试，请您在BOS系统进行处理。";

            // print_r($val['order_test_type']);exit;
            if($val['order_test_type'] == 1){//测试类型为时间

                $day = M('daydata')->field('adddate')->where("comid=".$val['id'])->group('adddate')->order("adddate desc")->limit(1)->select();


                if(strtotime(date('Y-m-d',time())) >= strtotime($day[0]['adddate'].' +'.$val['order_test_quota'].' day')){//当前时间大于约定时间
                	// print_r(1);exit;
                    //入库约定测试时间+7天后还在测试的
                    if(strtotime(date('Y-m-d',time())) >=strtotime($day[0]['adddate'].' +'.($val['order_test_quota'] +7).' day')){
                        $user_id = $user_id.','.$usid;
                        $dq_time = date("Y-m-d",strtotime($day[0]['adddate'].' +'.($val['order_test_quota'] +7).' day'));
                        $content = "产品".'"'.$val['name'].'"'." 已超过入库约定的测试周期7天，到期时间:".$dq_time."，目前仍在测试，请您在BOS系统进行处理";
                        unset($dq_time);
                    }
                    //到期进行提示
                    // $dp = M('prompt_information')->field('id')->where("oa_number=".$val['id'])->find();
                    // if(empty($dp['id'])){
					$add              = array();
					$add['date_time'] = date('Y-m-d H:i:s',time());
					$add['send_user'] = $user_id;
					$add['content']   = $content;
					$add['a_link']    = '/Product/index?id='.$val['id']."&name=".$val["name"];
					$add['oa_number'] = $val['id'];
                    M('prompt_information')->add($add);
                    unset($add);
                    // }
                }
            }elseif($val['order_test_type'] == 2){//量级
                $dayNum = M('daydata')->field('SUM(newdata) AS newdata')->where("comid=".$val['id'])->find();
                if($dayNum['newdata'] >= $val['order_test_quota']){
                    //到期进行提示
                    $dp = M('prompt_information')->field('id')->where("oa_number=".$val['id'])->find();
                    if(empty($dp['id'])){
						$add              = array();
						$add['date_time'] = date('Y-m-d H:i:s',time());
						$add['send_user'] = $val['saler_id'].','.$userData['id'];
						$add['content']   = "产品".'"'.$val['name'].'"'." 测试已到期，目前仍在测试，请您在BOS系统进行处理";
						$add['a_link']    = '/Product/index?id='.$val['id']."&name=".$val["name"];
						$add['oa_number'] = $val['id'];
                        M('prompt_information')->add($add);
                        unset($add);
                    }
                }
            }elseif($val['order_test_type'] == 3){//金额
                $dayMon = M('daydata')->field('SUM(newmoney) AS newmoney')->where("comid=".$val['id'])->find();
                if($dayMon['newmoney']>=$val['order_test_quota']){
                    if($dayMon['newmoney'] - $val['order_test_quota']>50000){
                        $user_id = $user_id.','.$usid;
                        $content = "产品".'"'.$val['name'].'"'." 测试金额已超过5万元人民币，目前仍在测试，请您在BOS系统进行处理";
                    }
                    // $dp = M('prompt_information')->field('id')->where("oa_number=".$val['id'])->find();
                    // if(empty($dp['id'])){
					$add              = array();
					$add['date_time'] = date('Y-m-d H:i:s',time());
					$add['send_user'] = $user_id;
					$add['content']   = $content;
					$add['a_link']    = '/Product/index?id='.$val['id']."&name=".$val["name"];
					$add['oa_number'] = $val['id'];
                    M('prompt_information')->add($add);
                    unset($add);
                    // }
                }
            }
        }
        unset($proData);
	}

	/**
	 * 产品未出数据提醒通知
	 * @return [type] [description]
	 */
	function synProductNotRemindedSer($limit){
		//查询所有正在推广的计费标识
		$sql = "SELECT 
				  c.id as jfbs_id,
				  GROUP_CONCAT(cl.`status`) AS ss ,p.`name` as pro_name
				FROM
				  `boss_charging_logo` AS c 
				  LEFT JOIN `boss_charging_logo_assign` AS cl ON c.id = cl.`cl_id` 
				  LEFT JOIN boss_product AS p ON p.id=c.`prot_id`
				GROUP BY cl.`cl_id` 
				HAVING FIND_IN_SET(1, ss) 
				{$limit} ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		unset($sql);
		if(!$list){ return false;}
		//判断各个积分标识是否在7个工作日内未产生数据，则需要通知该产品或计费标识对应的商务人员
		$j_ids = $this->getAllJFBSId($list);
		$j_ids = empty($j_ids)?0:$j_ids;
		//查询所有计费标识的最近7天收益-形成tree数组
		$sql = "SELECT 
				  SUM(newmoney) as total_money,
				  jfid 
				FROM
				  `boss_daydata` 
				WHERE jfid IN ({$j_ids}) 
				  AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= ADDDATE
				GROUP BY jfid ";
		$sum_list = $model->query($sql);

		$shourRuTree = $this->getShouRuTree($sum_list);
		unset($sum_list);

		//收集哪些计费标识最近7天无收益
		foreach ($list as $k => $v) {
			$shouru = $shourRuTree[$v["jfbs_id"]]["total_money"];
			//最近7天无收入
			if(empty($shouru) || $shouru<=0){
				$jfbs_one = M("charging_logo_assign")->field("business_uid")->where(array("cl_id"=>$v["jfbs_id"],"status"=>1))->find();
				if($jfbs_one["business_uid"]){
					$add              = array();
					$add['date_time'] = date('Y-m-d H:i:s',time());
					$add['send_user'] = $jfbs_one["business_uid"].",784";//计费标识对应的商务id
					$add['content']   = "亲，你所负责的产品【".$v["pro_name"]."】或计费标识【".$v['jfbs_id']."】已有7天未产生数据，请及时核查原因！";
					$add['a_link']    = '/Report/monthReport?jfid[]='.$v['jfbs_id']."&check7data=true";
					M('prompt_information')->add($add);
                    unset($add);
				}
			}
		}
	}

	/**
	 * [getShouRuTree description]
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	private function getShouRuTree($list){
		if(!$list){ return false;}
		$tree = array();
		foreach ($list as $k => $v) {
			$tree[$v["jfid"]]["total_money"] = $v["total_money"];
		}
		return $tree;
	}

	/**
	 * [getAllJFBSId description]
	 * @return [type] [description]
	 */
	private function getAllJFBSId($list){
		$j_ids = "";
		if(!$list) return $j_ids;
		foreach ($list as $k => $v) {
			$j_ids .= $v["jfbs_id"].",";
		}
		if($j_ids){
			$j_ids = substr($j_ids,0,strlen($j_ids)-1);
		}
		return $j_ids;
	}

	/**
	 * 获取总条数
	 * @return [type] [description]
	 */
	function getsynProductNotRemindedCount(){
		$sql = "SELECT 
			  COUNT(1) AS NO 
			FROM
			  (SELECT 
			    c.id,
			    GROUP_CONCAT(cl.`status`) AS ss 
			  FROM
			    `boss_charging_logo` AS c 
			    LEFT JOIN `boss_charging_logo_assign` AS cl 
			      ON c.id = cl.`cl_id` 
			  GROUP BY cl.`cl_id` 
			  HAVING FIND_IN_SET(1, ss)) AS temp ";

		$model = new \Think\Model();
		$list = $model->query($sql);
		unset($sql);unset($model);
		return  $list[0]["no"];
	}
}

?>