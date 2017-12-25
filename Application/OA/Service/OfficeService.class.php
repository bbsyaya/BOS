<?php 
/**
* office service
*/
namespace OA\Service;
use Think\Model;
class OfficeService
{
	/**
	 * 申请逻辑
	 * @return [type] [description]
	 */
	function applyListSer($where="",$order=""){
		$result = array("msg"=>"","data"=>"","page"=>"","total"=>"");
		$sql = "select
				  a.id,
				  p.name,
				  p.format,
				  p.price,
				  p.stock,
				  p.unit,
				  a.depart_name,
				  u.real_name,
				  a.apply_no,
				  a.total_money,
				  a.dateline,
				  a.status,a.depart_id,d.pid, d1.name as firstName
				from
				  `boss_oa_office_apply` as a 
				  left join `boss_oa_office_product` as p 
				    ON a.product_id = p.id 
				  left join `boss_user` as u 
				    on u.id = a.uid
				  LEFT JOIN `boss_user_department` AS d ON d.id=a.`depart_id`
				  LEFT JOIN `boss_user_department` AS d1 ON d1.id=d.`pid`";
	    $sql_count = "SELECT 
					 COUNT(1) as num
					FROM
					  `boss_oa_office_apply` AS a 
					  LEFT JOIN `boss_oa_office_product` AS p 
					    ON a.product_id = p.id 
					  LEFT JOIN `boss_user` AS u 
					    ON u.id = a.uid ";
	    $sql_total = "SELECT 
					  SUM(a.apply_no) AS t_apply_no,
					  SUM(a.apply_no*p.price) AS t_total_money  
					FROM
					  `boss_oa_office_apply` AS a
					  LEFT JOIN `boss_user` AS u ON u.id = a.uid
					  left join `boss_oa_office_product` as p  ON a.product_id = p.id" ;
	    $model = new \Think\Model();
	    $sql_count .= $where;
	    $count = $model->query($sql_count);
	    $count = $count[0]["num"];

	    //总计
	    $sql_total .= $where;
	    $data_total = $model->query($sql_total);
	    // print_r($sql_total);exit;
	    if(I("showsql")=="showsql023"){
	    	print_r($sql_total);exit;
	    }

		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page = new \Think\Page($count, $listRows);
		$sql  .= $where.$order." limit ".$page->firstRow.",".$page->listRows;
		// print_r($sql);exit;
		$list     = $model->query($sql);
		// foreach ($list as $k => $v) {
		// 	if($v["stock"]-$v["apply_no"]<0){
		// 		$list[$k]["status"] = 2;
		// 	}
		// }
		//第二次筛选
		$secList = array();
		foreach ($list as $k => $v) {
			$secList[$v["depart_id"]]["childList"][] = $v;
		}
		foreach ($secList as $ks => $vs) {
			$sList       = $vs["childList"];
			$total_money = 0;
			$total_apply = 0;
			$total_count = count($sList);
			foreach ($sList as $kss => $vss) {
				$total_apply  = $total_apply+$vss["apply_no"];
				$total_money  = $total_money+$vss["total_money"];
			}
			$secList[$ks]["total_apply"] = $total_apply;
			$secList[$ks]["total_money"] = $total_money;
			$secList[$ks]["rowspan"] = $total_count;
		}
		
		
		//第三次筛选
		$thirdList  = array();
		$thirdIndex = 0;
		foreach ($secList as $k => $v) {
			$vlist       = $v["childList"];
			$vCount      = count($vlist);
			$total_money = $v["total_money"];
			$total_apply = $v["total_apply"];
			foreach ($vlist as $kv => $vv) {
				if($kv==0){
					$vv["total_money_row"]        = $total_money;
					$vv["total_apply_row"]        = $total_apply;
					$vv["rowspan"]                = $vCount;
				}else{
					$vv["real_name"] = "";
					$vv["depart_name"] = "";
				}
				
				$thirdList[$thirdIndex][] = $vv;
			}
		}
		// print_r($thirdList);exit;
		$result = array("data"=>$thirdList,"page"=>$page->show(),"total"=>$data_total);
		return $result;
	}	
}
?>