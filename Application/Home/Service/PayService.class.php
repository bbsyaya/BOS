<?php
/**
* 认款service
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class PayService extends CommonService
{
	
	/**
	 * [getPayCountByWhere description]
	 * @param  [type] $where_ [description]
	 * @param  string $field  [description]
	 * @return [type]         [description]
	 */
	function getPayCountByWhere($where_,$field=""){
		$count = M("pay")->field($field)->where($where_)->count();
		return $count;
	}

	/**
	 * 或股广告主支付流水对应系统的销售待认款记录，并通知该广告主下的产品的所有销售
	 * @param  [type] $limit [description]
	 * @return [type]        [description]
	 */
	function synReturnReceiptNoticeSer($limit){
		$sql = "SELECT 
				  p.id AS rk_id,
				  p.paymentname,
				  p.`adddate`,
				  p.`money`,
				  ad.id AS ad_id,
				  GROUP_CONCAT(pro.`saler_id`) as saler_ids
				FROM
				  boss_pay AS p 
				  LEFT JOIN `boss_advertiser` AS ad 
				    ON ad.`name` = p.`paymentname` 
				  LEFT JOIN `boss_product` AS pro 
				    ON pro.`ad_id` = ad.`id` 
				WHERE p.status = 1 AND ADDDATE>='2017-09-01'
				GROUP BY p.`paymentname` 
				ORDER BY p.adddate DESC
				{$limit} ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list) return false;
		//通知后勤 刘霞、黄榜杰、蔡静,王荣婷
		$user_n = M('user')->field('id')->where("username IN ('liuxia','huangbangjie','caijing','wangrongting')")->select();
		$usids  = '';
        foreach($user_n as $key2=>$val2){
            $usids .= $val2['id'].",";
        }
        unset($user_n);
		foreach ($list as $k => $v) {
			$add              = array();
			$add['date_time'] = date('Y-m-d H:i:s',time());
			$add['send_user'] = $usids.$v["saler_ids"];
			$add['content']   = "亲：".$v['paymentname']."广告主于".$v['adddate']."时间付款".$v["money"]."元，请及时处理认款事宜。";
			$add['a_link']    = '/Finance/adminMoney?payername='.$v["paymentname"].'&status=1&strtime='.$v["adddate"];
			M('prompt_information')->add($add);
			unset($add);
		}
		unset($list);
	}
}
?>