<?php
/**
* 逻辑处理
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class ExcelLogicService
{
	/**
	 * ，开头函数
	 * @return [type] [description]
	 */
	private function _explodeHead(){
		// Create new PHPExcel object    
	    $objPHPExcel = new \Org\Util\PHPExcel(); 
	    $objPHPExcel->getProperties()->setCreator("ctos")  
	            ->setLastModifiedBy("ctos")  
	            ->setTitle("Office 2007 XLSX Test Document")  
	            ->setSubject("Office 2007 XLSX Test Document")  
	            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
	            ->setKeywords("office 2007 openxml php")  
	            ->setCategory("Test result file");

	    // 设置行高度    
	    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);  
	  
	    // 字体和样式  
	    $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);    
	    $objPHPExcel->setActiveSheetIndex()->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->setActiveSheetIndex()->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
	    return $objPHPExcel;
	}

	/**
	 * 输出底部
	 * @param  [type] $objPHPExcel [description]
	 * @param  [type] $sheetTitle  [description]
	 * @return [type]              [description]
	 */
	private function _exploadFoot($objPHPExcel,$sheetTitle,$excelFileName){
		// Rename sheet    
		$objPHPExcel->getActiveSheet()->setTitle($sheetTitle);  

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet    
		$objPHPExcel->setActiveSheetIndex(0);  
		
		// 输出  
		ob_end_clean();  //清空缓存             
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition: attachment;filename="'.$excelFileName.'.xls"'); 
		header("Content-Transfer-Encoding:binary"); 
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
		$objWriter->save('php://output'); 
	}
	
	/**
	 * 导出产品修改记录
	 * @param  [type] $excelFileName [description]
	 * @return [type]                [description]
	 */
	function explortProductUpdateRec($expList,$sheetTitle,$excelFileName){
		$objPHPExcel = $this->_explodeHead();

		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '修改时间')  
					->setCellValue('B1', '修改用户名')
					->setCellValue('C1', '备注'); 
		// 内容  
		for ($i = 0, $len = count($expList); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $expList[$i]['dateline']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $expList[$i]['real_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $expList[$i]['remark']);
		}  
		
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$excelFileName);
	}

	/**
	 * 导出计费标识状态
	 * @return [type] [description]
	 */
	function exportCharLogStatusSer(){
		$sql = "SELECT 
				  e.bl_id AS pro_bl_id,
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
		if(!$list){ return $result["msg"]=="无数据"; }
		unset($sql);
		$lines = $this->makeBLidLists($list);
		$total_syz = 0;
		$total_wfp = 0;
		$total_yhs = 0;
		foreach ($list as $k => $v) {
			$list[$k]["bl_id_name"] = $lines[$v["pro_bl_id"]]["name"];

			$total_syz = $total_syz+$v['syz'];
			$total_wfp = $total_wfp+$v['wfp'];
			$total_yhs = $total_yhs+$v['yhs'];
		}
		unset($lines);
		$objPHPExcel = $this->_explodeHead();

		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '业务线名称')  
					->setCellValue('B1', '使用中')
					->setCellValue('C1', '未分配')
					->setCellValue('D1', '已回收'); 

		//总计
		$next_index        = count($list);
		$one["bl_id_name"] = "总计：";
		$one["syz"]        = $total_syz;
		$one["wfp"]        = $total_wfp;
		$one["yhs"]        = $total_yhs;
		$list[$next_index] = $one;
		unset($one);
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['bl_id_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['syz']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['wfp']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $list[$i]['yhs']); 
		}  
		unset($list);
		$sheetTitle = "导出计费标识状态--".time();
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}

	/**
	 * [makeBLidLists description]
	 * @return [type] [description]
	 */
	private function makeBLidLists($list){
		$line_list = array();
		if(!$list) return false;
		$lineids = "";
		foreach ($list as $k => $v) {
			if($v["pro_bl_id"]){
				$lineids .= $v["pro_bl_id"].",";
			}
		}
		if($lineids){
			$lineids = substr($lineids, 0,strlen($lineids)-1);
		}

		//读取
		$lineids = empty($lineids)?"0":$lineids;
		$lineList = M("business_line")->field("id,name")->where(array("id"=>array("in",$lineids)))->select();
		foreach ($lineList as $k => $v) {
			$line_list[$v["id"]] = $v;
		}
		unset($lineids);unset($lineList);
		return $line_list;
	}

	/**
	 * 【导出未上量计费标识(使用中且连续7天无数据产生
	 * @return [type] [description]
	 */
	function exportCharLogNotSer(){
		$start = date("Y-m-d", strtotime("-1week"));
		$end   = date("Y-m-d");
		$where = "b.id not in (SELECT a.jfid FROM boss_daydata_out a JOIN (SELECT MAX(id) AS id,cl_id FROM boss_charging_logo_assign WHERE `status`=1 GROUP BY cl_id) b ON a.jfid=b.cl_id AND adddate>='".$start."' AND adddate<'".$end."' GROUP BY a.jfid)";
		$sql = "SELECT 
				  b.name AS cl_name,
				  c.name AS bl_name,
				  d.name AS pro_name,
				  e.name AS adv_name,
				  f.real_name,
				  g.real_name sw_name,
				  h.name sup_name 
				FROM
				  `boss_charging_logo` b 
				  JOIN boss_charging_logo_assign a 
				    ON a.cl_id = b.id 
				    AND a.status = 1 
				  JOIN boss_business_line c 
				    ON c.id = a.bl_id 
				  JOIN boss_product d 
				    ON d.id = b.prot_id 
				  JOIN boss_advertiser e 
				    ON e.id = b.ad_id 
				  JOIN boss_user f 
				    ON f.id = d.saler_id 
				  JOIN boss_user g 
				    ON g.id = a.business_uid 
				  JOIN boss_supplier h 
				    ON h.id = a.sup_id 
				WHERE {$where}";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){ return $result["msg"]=="无数据"; }
		unset($sql);
		//导出
		$objPHPExcel = $this->_explodeHead();

		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '计费标识')  
					->setCellValue('B1', '业务线')
					->setCellValue('C1', '产品名称')
					->setCellValue('D1', '广告主')
					->setCellValue('E1', '销售')
					->setCellValue('F1', '商务')
					->setCellValue('G1', '供应商'); 

		//总计
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['cl_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['bl_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['pro_name']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $list[$i]['adv_name']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['real_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['sw_name']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['sup_name']); 
		}  
		unset($list);
		$sheetTitle = "导出未上量计费标识-".time();
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}

	/**
	 * 【导出可利用计费标识】
	 * @return [type] [description]
	 */
	function exportCharLogCanUseSer(){
		$sql = "SELECT 
				  d.`name`,
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
		if(!$list){ return $result["msg"]=="无数据"; }
		unset($sql);
		//导出
		$objPHPExcel = $this->_explodeHead();

		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '产品名称')  
					->setCellValue('B1', '未分配个数')
					->setCellValue('C1', '已回收个数'); 

		//总计
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['wfp']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['yhs']);; 
		}  
		unset($list);
		$sheetTitle = "导出可利用计费标识-".time();
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}

	/**
	 * 导出计费标识回收分析
	 * @return [type] [description]
	 */
	function exportCharLogAnalisySer(){
		$sql = "SELECT 
				  c.`name`,
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
		if(!$list){ return $result["msg"]=="无数据"; }
		unset($sql);
		//导出
		$objPHPExcel = $this->_explodeHead();

		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '供应商名称')  
					->setCellValue('B1', '产品下线')
					->setCellValue('C1', '渠道未上量')
					->setCellValue('D1', '渠道质量差')
					->setCellValue('E1', '渠道作弊')
					->setCellValue('F1', '渠道下线'); 

		//总计
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['cpxx']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['wsl']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $list[$i]['zlc']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['zb']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['qdxx']); 
		}  
		unset($list);
		$sheetTitle = "导出计费标识回收分析-".time();
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}

	/**
	 * 导出广告主
	 * @return [type] [description]
	 */
	function exportAdverListExcel($list){
		//导出
		$objPHPExcel = $this->_explodeHead();
		$year = date("Y",time());
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '产品ID')  
					->setCellValue('B1', '广告主名称')
					->setCellValue('C1', '产品名称')
					->setCellValue('D1', '计费模式')
					->setCellValue('E1', '合作状态')
					->setCellValue('F1', '标签')
					->setCellValue('G1', '业务线')
					->setCellValue('H1', '地域')
					->setCellValue('I1', '广告主等级')
					->setCellValue('J1', $year.'年总收入')
					->setCellValue('K1', $year.'年总利润')
					->setCellValue('L1', '前日流水')
					->setCellValue('M1', '跟进时间')
					->setCellValue('N1', '跟进结果')
					->setCellValue('O1', '销售人员')
					->setCellValue('P1', '联系人')
					->setCellValue('Q1', '联系方式'); 

		//总计
		$charging_mode_options = C('OPTION.charging_mode');

		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['id']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['ad_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['pro_name']);
			$charging_mode_str = $charging_mode_options[$list[$i]["charging_mode"]];
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $charging_mode_str);
			unset($charging_mode_str);

			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['cooperate_state_str']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['tag']); 

			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['line_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $list[$i]['region_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $list[$i]['ad_grade']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 2), $list[$i]['total_in']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 2), $list[$i]['total_liushui']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 2), $list[$i]['yestoday_total_in1']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('M' . ($i + 2), $list[$i]['visit_time']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('N' . ($i + 2), $list[$i]['f_result']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('O' . ($i + 2), $list[$i]['real_name']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('P' . ($i + 2), $list[$i]['adc_name']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('Q' . ($i + 2), $list[$i]['adc_mobile']);  
		}  
		unset($list);
		$sheetTitle = date("Y-m-dHis",time())."-已扩展广告主信息";
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}

	/**
	 * 导出供应商信息
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function exportSupplyListExcel($list){
		//导出
		$objPHPExcel = $this->_explodeHead();
		$year = date("Y",time());
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '产品ID')  
					->setCellValue('B1', '供应商名称')
					->setCellValue('C1', '产品名称')
					->setCellValue('D1', '计费模式')
					->setCellValue('E1', '合作状态')
					->setCellValue('F1', '标签')
					->setCellValue('G1', '业务线')
					->setCellValue('H1', '地域')
					->setCellValue('I1', '供应商等级')
					->setCellValue('J1', $year.'年总成本')
					->setCellValue('K1', $year.'年总利润')
					->setCellValue('L1', '前日流水')
					->setCellValue('M1', '跟进时间')
					->setCellValue('N1', '跟进结果')
					->setCellValue('O1', '商务人员')
					->setCellValue('P1', '联系人')
					->setCellValue('Q1', '联系方式'); 

		//总计
		$charging_mode_options = C('OPTION.charging_mode');

		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['pro_id']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['gys_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['pro_name']);
			$charging_mode_str = $charging_mode_options[$list[$i]["charging_mode"]];
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $charging_mode_str);
			unset($charging_mode_str);

			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['cooperate_state_str']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['tag']); 

			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['line_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $list[$i]['diqu_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $list[$i]['grade']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 2), $list[$i]['total_out']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 2), $list[$i]['total_liushui']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 2), $list[$i]['yestoday_total_in1']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('M' . ($i + 2), $list[$i]['visit_time']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('N' . ($i + 2), $list[$i]['f_result']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('O' . ($i + 2), $list[$i]['sw_name']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('P' . ($i + 2), $list[$i]['lxr_name']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('Q' . ($i + 2), $list[$i]['mobile']);  
		}  
		unset($list);
		$sheetTitle = date("Y-m-dHis",time())."-已扩展供应商信息";
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}

	/**
	 * 导出带拓展广告主信息
	 * @return [type] [description]
	 */
	function exportExtendAdverListToExcel($list){
		//导出
		$objPHPExcel = $this->_explodeHead();
		$year = date("Y",time());
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', 'ID')  
					->setCellValue('B1', '公司名称')
					->setCellValue('C1', '产品')
					->setCellValue('D1', '计费模式')
					->setCellValue('E1', '地域')
					->setCellValue('F1', '标签')
					->setCellValue('G1', '广告主优势')
					->setCellValue('H1', '体量化')
					->setCellValue('I1', '关联度')
					->setCellValue('J1', '历史合作案例')
					->setCellValue('K1', '指派部门跟进')
					->setCellValue('L1', '需求人')
					->setCellValue('M1', '联系人')
					->setCellValue('N1', '联系方式')
					->setCellValue('O1', '需求类型')
					->setCellValue('P1', '备注'); 

		//总计
		$charging_mode_options = C('OPTION.charging_mode');
		
		$demand_type_options = C('OPTION.demandType');
		// $lineList = getLineIDTree();
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['id']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['company_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['partner']);
			$charging_mode_str = $charging_mode_options[$list[$i]["bil_method"]];
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $charging_mode_str);
			unset($charging_mode_str);

			//业务线
			// $line_name = $lineList[$list[$i]['line_id']]["name"];
			// $objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2),$line_name); 
			// unset($line_name);

			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['area']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['target']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['adver_advan']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $list[$i]['volume']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $list[$i]['relevance']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 2), $list[$i]['history_case']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 2), $list[$i]['depart_names']); 

			//用户名
			$objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 2), $list[$i]['need_user']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('M' . ($i + 2), $list[$i]['contact_user']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('N' . ($i + 2), $list[$i]['contact_way']); 
			//需求类型
			$dtype = $demand_type_options[$list[$i]['demand_type']];
			$objPHPExcel->getActiveSheet(0)->setCellValue('O' . ($i + 2), $dtype); 
			unset($dtype);
			$objPHPExcel->getActiveSheet(0)->setCellValue('P' . ($i + 2), $list[$i]['remark']);  
		}  
		unset($list);
		unset($lineList);
		unset($userList);
		unset($buSer);
		unset($userSer);
		$sheetTitle = date("Y-m-dHis",time())."-待扩展广告主信息";
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}


	/**
	 * 导出带拓展广告主信息
	 * @return [type] [description]
	 */
	function explortUserMenusToExcel($list){
		//导出
		$objPHPExcel = $this->_explodeHead();
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '二级版块')  
					->setCellValue('B1', '三级版块')
					->setCellValue('C1', '用户'); 
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['title']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['child_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['user_name']); 
		}  
		unset($list);
		$sheetTitle = date("Y-m-dHis",time())."-Bos菜单对应用户";
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$sheetTitle);
	}


}

?>