<?php 
/**
* 计费标识service
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class ChargingLogoService 
{
	private $rec_url = "http://dist.youxiaoad.com/api.php/Alimamastop/alimamaRecovery";

	/**
	 * 计费标识-同步至分发--回收接口
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function alimamaRecoveryService($data){
		$responsData        = bossPostData($this->rec_url, $data);//同步至分发
		//记录日志
		$data_l["postUrl"]  = $this->rec_url;
		$data_l["postData"] = json_encode($data,JSON_UNESCAPED_UNICODE);
		$data_l["data"]     = $responsData;
		$data_l["type"]     = 100;
		$finLogSer          = new Service\FinanceLogService();
		$finLogSer->writeLog($data_l);
	}
	
	/**
	 * 获取所有业务线的计费标识总数
	 * @return [type] [description]
	 */
	function getTotalCharlogCountSer(){
		$result = array("total_syz"=>0,"total_yhs"=>0,"total_wfp"=>0);
		$sql = "SELECT 
				   COUNT(
				    CASE
				      WHEN d.`status` = 1 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS syz,
				  COUNT(
				    CASE
				      WHEN d.`status` = 2 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS wfp,
				  COUNT(
				    CASE
				      WHEN d.`status` = 3 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS yhs 
				FROM
				  `boss_charging_logo` c 
				  LEFT JOIN 
				    (SELECT 
				      cl_id,
				      `status`,
				      bl_id 
				    FROM
				      boss_charging_logo_assign a 
				    WHERE a.id IN 
				      (SELECT 
				        MAX(id) AS id 
				      FROM
				        boss_charging_logo_assign 
				      WHERE `status` > 0 
				        AND bl_id > 0 
				      GROUP BY CL_ID)) d 
				    ON c.id = d.cl_id 
				  LEFT JOIN boss_product e 
				    ON e.id = c.prot_id 
				    WHERE e.`bl_id`>0
				GROUP BY e.bl_id ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){ return $result; }
		unset($sql);
		foreach ($list as $k => $v) {
			$result["total_syz"] = $result["total_syz"]+$v['syz'];
			$result["total_wfp"] = $result["total_wfp"]+$v['wfp'];
			$result["total_yhs"] = $result["total_yhs"]+$v['yhs'];
		}
		unset($list);
		return $result;
	}

	/**
	 * 可利用计费标识总和
	 * @return [type] [description]
	 */
	function getTotalCharlogCanUseCountSer(){
		$result = array("total_yhs"=>0,"total_wfp"=>0);
		$sql = "SELECT 
				  COUNT(
				    CASE
				      WHEN a.`status` = 2 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS wfp,
				  COUNT(
				    CASE
				      WHEN a.`status` = 3 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS yhs 
				FROM
				  `boss_charging_logo_assign` a 
				  JOIN 
				    (SELECT 
				      MAX(id) AS id 
				    FROM
				      boss_charging_logo_assign 
				    WHERE (`status` = 2 
				        OR `status` = 3) 
				    GROUP BY cl_id) b 
				    ON a.id = b.id 
				  JOIN boss_charging_logo c 
				    ON c.id = a.cl_id 
				  JOIN boss_product d 
				    ON d.id = c.prot_id 
				GROUP BY d.id ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){ return $result; }
		unset($sql);
		foreach ($list as $k => $v) {
			$result["total_wfp"] = $result["total_wfp"]+$v['wfp'];
			$result["total_yhs"] = $result["total_yhs"]+$v['yhs'];
		}
		unset($list);
		return $result;
	}

	/**
	 * 计费标识分析总和
	 * @return [type] [description]
	 */
	function getTotalCharlogAnisylesCountSer(){
		$result = array("total_cpxx"=>0,"total_wsl"=>0,"total_zlc"=>0,"total_zb"=>0,"total_qdxx"=>0);
		$sql = "SELECT 
				  COUNT(
				    CASE
				      WHEN a.remark LIKE '%产品下线%' 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS cpxx,
				  COUNT(
				    CASE
				      WHEN a.remark LIKE '%渠道未上量%' 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS wsl,
				  COUNT(
				    CASE
				      WHEN a.remark LIKE '%渠道质量差%' 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS zlc,
				  COUNT(
				    CASE
				      WHEN a.remark LIKE '%渠道作弊%' 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS zb,
				  COUNT(
				    CASE
				      WHEN a.remark LIKE '%渠道下线%' 
				      THEN 1 
				      ELSE NULL 
				    END
				  ) AS qdxx 
				FROM
				  `boss_charging_logo_assign` a 
				  JOIN 
				    (SELECT 
				      MAX(id) AS id 
				    FROM
				      boss_charging_logo_assign 
				    WHERE `status` = 3 
				      AND remark != '' 
				    GROUP BY cl_id) b 
				    ON a.id = b.id 
				  JOIN boss_supplier c 
				    ON c.id = a.sup_id 
				GROUP BY c.id ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){ return $result; }
		unset($sql);
		foreach ($list as $k => $v) {
			$result["total_cpxx"] = $result["total_cpxx"]+$v['cpxx'];
			$result["total_wsl"] = $result["total_wsl"]+$v['wsl'];
			$result["total_zlc"] = $result["total_zlc"]+$v['zlc'];
			$result["total_zb"] = $result["total_zb"]+$v['zb'];
			$result["total_qdxx"] = $result["total_qdxx"]+$v['qdxx'];
		}
		unset($list);
		return $result;
	}
}
?>