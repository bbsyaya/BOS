<?php
/**
* 逻辑处理
*/
namespace OA\Service;
use Think\Model;
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
	 * 导出员工信息
	 * @param  [type] $list          [description]
	 * @param  [type] $sheetTitle    [description]
	 * @param  [type] $excelFileName [description]
	 * @return [type]                [description]
	 */
	function explortHrEmployee($list,$sheetTitle,$excelFileName,$type="on"){
		
		$objPHPExcel = $this->_explodeHead();

		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '序号')  
					->setCellValue('B1', '归属公司')
					->setCellValue('C1', '各部门人数') 
					->setCellValue('D1', '部门及部门负责人') 
					->setCellValue('E1', '二级部门') 
					->setCellValue('F1', '姓名') 
					->setCellValue('G1', '职务') 
					->setCellValue('H1', '岗位评级') 
					->setCellValue('I1', '职等职级') 
					->setCellValue('J1', '层级') 
					->setCellValue('K1', '工号') 
					->setCellValue('L1', '性别') 
					->setCellValue('M1', '身份证号') 
					->setCellValue('N1', '出生年月') 
					->setCellValue('O1', '年龄') 
					->setCellValue('P1', '首次工作时间（全职）') 
					->setCellValue('Q1', '婚姻') 
					->setCellValue('R1', '身高(cm)') 
					->setCellValue('S1', '体重(kg)') 
					->setCellValue('T1', '户口性质') 
					->setCellValue('U1', '民族') 
					->setCellValue('V1', '籍贯') 
					->setCellValue('W1', '联系地址') 
					->setCellValue('X1', '紧急联系人') 
					->setCellValue('Y1', '与本人关系') 
					->setCellValue('Z1', '紧急联系人电话') 
					->setCellValue('AA1', '学历') 
					->setCellValue('AB1', '毕业时间') 
					->setCellValue('AC1', '毕业院校') 
					->setCellValue('AD1', '专业') 
					->setCellValue('AE1', '联系电话')
					->setCellValue('AF1', '备用电话')
					->setCellValue('AG1', 'QQ')
					->setCellValue('AH1', '入职日期')
					->setCellValue('AI1', '司龄(单位：年)')
					->setCellValue('AJ1', '拟转正日期')
					->setCellValue('AK1', '实转正日期')
					->setCellValue('AL1', '社保办理')
					->setCellValue('AM1', '公积金办理')
					->setCellValue('AN1', '社保卡办理')
					//---
					->setCellValue('AO1', '社保参保时间') 

					->setCellValue('AP1', '指纹编号')
					->setCellValue('AQ1', '绩效考核')
					// ->setCellValue('AR1', '个税')
					->setCellValue('AS1', '工资总额')
					->setCellValue('AT1', '基本工资')
					->setCellValue('AU1', '职务工资')
					->setCellValue('AV1', '绩效工资')
					->setCellValue('AW1', '薪水调整')
					->setCellValue('AX1', '第一次签订合同开始时间')
					->setCellValue('AY1', '第一次签订合同结束时间')
					->setCellValue('AZ1', '第二次签订合同开始时间')
					->setCellValue('BA1', '第二次签订合同结束时间')
					->setCellValue('BB1', '第三次签订合同开始时间')
					->setCellValue('BC1', '保密/竟业禁止')
					->setCellValue('BD1', '银行卡号1')
					->setCellValue('BE1', '银行卡1开户行')
					->setCellValue('BF1', '银行卡号2')
					->setCellValue('BG1', '银行卡2开户行')
					->setCellValue('BH1', '合同签订公司')
					->setCellValue('BI1', '属性')
					->setCellValue('BJ1', '招聘渠道')
					->setCellValue('BK1', '职务异动');  

		//离职
		if($type=="out"){
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('BL1', '离职日期'); 
		}

		// 内容  
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['id']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['company_id_name']);  
			 
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['depart_id_count']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $list[$i]['depart_parent_name_header']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['sec_depart_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['user_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['duty_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $list[$i]['job_rating']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $list[$i]['grade_rank']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 2), $list[$i]['level']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 2), $list[$i]['job_no']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 2), $list[$i]['sex']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('M' . ($i + 2), $list[$i]['body_no']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('N' . ($i + 2), $list[$i]['birth']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('O' . ($i + 2), $list[$i]['age']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('P' . ($i + 2), $list[$i]['start_work']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('Q' . ($i + 2), $list[$i]['is_marry']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('R' . ($i + 2), $list[$i]['height']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('S' . ($i + 2), $list[$i]['weight']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('T' . ($i + 2), $list[$i]['account_nature']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('U' . ($i + 2), $list[$i]['nation']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('V' . ($i + 2), $list[$i]['passby']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('W' . ($i + 2), $list[$i]['adress']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('X' . ($i + 2), $list[$i]['emergen_user']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('Y' . ($i + 2), $list[$i]['relation_me']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('Z' . ($i + 2), $list[$i]['emergen_phone']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AA' . ($i + 2), $list[$i]['educate']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AB' . ($i + 2), $list[$i]['graduat_time']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AC' . ($i + 2), $list[$i]['graduat_school']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AD' . ($i + 2), $list[$i]['profession']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AE' . ($i + 2), $list[$i]['phone']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AF' . ($i + 2), $list[$i]['back_phone']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AG' . ($i + 2), $list[$i]['qq']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AH' . ($i + 2), $list[$i]['entry_time']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AI' . ($i + 2), $list[$i]['company_age']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AJ' . ($i + 2), $list[$i]['proposed_time']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AK' . ($i + 2), $list[$i]['real_proposed']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AL' . ($i + 2), $list[$i]['social_man']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AM' . ($i + 2), $list[$i]['provident_start']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AN' . ($i + 2), $list[$i]['social_card']); 

			$objPHPExcel->getActiveSheet(0)->setCellValue('AO' . ($i + 2), $list[$i]['start_insure']); 

			$objPHPExcel->getActiveSheet(0)->setCellValue('AP' . ($i + 2), $list[$i]['finger_no']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AQ' . ($i + 2), $list[$i]['per_radio']);  
			// $objPHPExcel->getActiveSheet(0)->setCellValue('AR' . ($i + 2), $list[$i]['ge_shui']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AS' . ($i + 2), $list[$i]['total_salary']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AT' . ($i + 2), $list[$i]['basic_pay']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AU' . ($i + 2), $list[$i]['job_salary']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AV' . ($i + 2), $list[$i]['jixiao_salary']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AW' . ($i + 2), $list[$i]['salary_log']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AX' . ($i + 2), $list[$i]['first_visa_start']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AY' . ($i + 2), $list[$i]['fist_visa_end']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AZ' . ($i + 2), $list[$i]['sec_visa_start']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BA' . ($i + 2), $list[$i]['sec_visa_end']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BB' . ($i + 2), $list[$i]['third_visa_start']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BC' . ($i + 2), $list[$i]['probihibe']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BD' . ($i + 2), $list[$i]['bank_cardno1']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BE' . ($i + 2), $list[$i]['bank_card1']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BF' . ($i + 2), $list[$i]['bank_cardno2']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BG' . ($i + 2), $list[$i]['bank_card2']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BH' . ($i + 2), $list[$i]['sign_company']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BI' . ($i + 2), $list[$i]['user_attr']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BJ' . ($i + 2), $list[$i]['rec_channel']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('BK' . ($i + 2), $list[$i]['job_change']); 
			//离职
			if($type=="out"){
				$objPHPExcel->getActiveSheet(0)->setCellValue('BL' . ($i + 2), $list[$i]['departure_time']); 
			} 
		}  
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$excelFileName);
	}
	
	/**
	 * 导出当月工资表
	 * @param  [type] $list          [description]
	 * @param  [type] $sheetTitle    [description]
	 * @param  [type] $excelFileName [description]
	 * @return [type]                [description]
	 */
	function excelCurrentMonthSalaryData($list,$sheetTitle,$excelFileName){
		$objPHPExcel = $this->_explodeHead();
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '序号')  
					->setCellValue('B1', '归属公司')
					->setCellValue('C1', '部门') 
					->setCellValue('D1', '姓名') 
					->setCellValue('E1', '职位') 
					->setCellValue('F1', '职层') 
					->setCellValue('G1', '入职日期') 
					->setCellValue('H1', '工龄') 
					->setCellValue('I1', '转正日期') 
					->setCellValue('J1', '离职日期') 
					->setCellValue('K1', '当月状态') 
					->setCellValue('L1', '实际出勤天数') 
					->setCellValue('M1', '工资标准') 
					->setCellValue('N1', '基本工资') 
					->setCellValue('O1', '职务工资') 
					->setCellValue('P1', '绩效工资') 
					->setCellValue('Q1', '季度奖金') 
					->setCellValue('R1', '季度提成') 
					->setCellValue('S1', '其他(加款)') 
					->setCellValue('T1', '其他扣款') 
					->setCellValue('U1', '考勤扣款') 
					->setCellValue('V1', '工龄津贴') 
					->setCellValue('W1', '交通补助') 
					->setCellValue('X1', '通讯补助') 
					->setCellValue('Y1', '全勤奖') 
					->setCellValue('Z1', '其他福利') 
					->setCellValue('AA1', '福利合计') 
					->setCellValue('AB1', '应发工资合计') 
					->setCellValue('AC1', '代扣社保') 
					->setCellValue('AD1', '代扣下月社保') 
					->setCellValue('AE1', '代扣公积金') 
					->setCellValue('AF1', '公司承担社保')
					->setCellValue('AG1', '公司承担公积金')
					->setCellValue('AH1', '应纳税所得额')
					->setCellValue('AI1', '个税扣款')
					->setCellValue('AJ1', '罚款代扣')
					->setCellValue('AK1', '其他项')
					->setCellValue('AL1', '实发工资总额')
					->setCellValue('AM1', '银行卡号')
					->setCellValue('AM1', '银行卡开户行')
					->setCellValue('AO1', '备注');  
		// 内容  
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['id']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['company_id_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['depart_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $list[$i]['user_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['duty']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['level']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['entry_time']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $list[$i]['work_age']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $list[$i]['depart_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 2), $list[$i]['departure_time']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 2), $list[$i]['dangyue_status']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 2), parseFloat2($list[$i]['fact_chuqin_days']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('M' . ($i + 2), parseFloat2($list[$i]['gongzi_biaozhuan'])); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('N' . ($i + 2), parseFloat2($list[$i]['basic_pay'])); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('O' . ($i + 2), parseFloat2($list[$i]['job_salary']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('P' . ($i + 2), parseFloat2($list[$i]['per_pay'])); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('Q' . ($i + 2), parseFloat2($list[$i]['quarte_bonus']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('R' . ($i + 2), parseFloat2($list[$i]['quarte_commi']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('S' . ($i + 2), parseFloat2($list[$i]['other_add']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('T' . ($i + 2), parseFloat2($list[$i]['other_less'])); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('U' . ($i + 2), parseFloat2($list[$i]['attend_less']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('V' . ($i + 2), parseFloat2($list[$i]['age_allowance'])); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('W' . ($i + 2), parseFloat2($list[$i]['trasport_allowance']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('X' . ($i + 2), parseFloat2($list[$i]['commun_allowance']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('Y' . ($i + 2), parseFloat2($list[$i]['perfect_award']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('Z' . ($i + 2), parseFloat2($list[$i]['other_benefy']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AA' . ($i + 2), parseFloat2($list[$i]['total_benefy']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AB' . ($i + 2), parseFloat2($list[$i]['factpay_wages']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AC' . ($i + 2), parseFloat2($list[$i]['widthlold_social']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AD' . ($i + 2), parseFloat2($list[$i]['withhold_next'])); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('AE' . ($i + 2), parseFloat2($list[$i]['withhold_profund']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AF' . ($i + 2), parseFloat2($list[$i]['compay_ass_socsec']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AG' . ($i + 2), parseFloat2($list[$i]['company_ass_profund']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AH' . ($i + 2), parseFloat2($list[$i]['tax_incom']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AI' . ($i + 2), parseFloat2($list[$i]['tax_deduc']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AJ' . ($i + 2), parseFloat2($list[$i]['fine_widhhold']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AK' . ($i + 2), parseFloat2($list[$i]['other_item']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AL' . ($i + 2), parseFloat2($list[$i]['total_wages']));  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AM' . ($i + 2), $list[$i]['bank_cardno1']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AN' . ($i + 2), $list[$i]['bank_card1']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('AO' . ($i + 2), $list[$i]['remark']);  
		}  
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$excelFileName);
	}

	/**
	 * 导出办公用
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	function excelOfficeNeedBuy_($where){
		$result = array("msg"=>"","data"=>"","code"=>"500");
		$sql = "select 
				  sum(a.apply_no) as total_apply_no,
				  a.product_id,
				  p.`name`,
				  p.`stock`,
				  p.format,
				  p.price,
				  p.remark,p.unit
				from
				  `boss_oa_office_apply` as a 
				  left join `boss_oa_office_product` as p 
				    ON a.`product_id` = p.`id` 
				".$where." 
				group by product_id ";
				//HAVING p.`stock`-total_apply_no<=0 
				print_r($sql);exit;
		if(I("showsql")=="showsql023"){
			print_r($sql);exit;
		}
				
		$model          = new \Think\Model();
		$list           = $model->query($sql);
		if(!$list){
			$result = array("msg"=>"暂无需要采购的办公用品,请检查","data"=>"","code"=>"500");
			return $result;
		}
		$expList        = array();
		$total_apply_no = 0;
		$total_money    = 0;
		foreach ($list as $k => $v) {
			if($v["stock"]-$v["total_apply_no"] <= 0){
				$one["name"]           = $v["name"];
				$one["format"]         = $v["format"];
				$one["stock"]          = $v["stock"];
				$one["total_apply_no"] = $v["total_apply_no"];
				$one["diff_stock"]     = $v["stock"]-$v["total_apply_no"];
				$one["unit"]          =  $v["unit"];
				$one["price"]          = $v["price"];
				$one["price_total"]    = $v["total_apply_no"]*$one["price"];
				$one["remark"]         = $v["remark"];
				$expList[]             = $one;
				$total_apply_no        = $total_apply_no+$v["total_apply_no"];
				$total_money           = $total_money+$one["price_total"];
			}else{
				continue;
			}
		}
		//总计
		$next_index            = count($expList);
		$one["name"]           = " ";
		$one["format"]         = " ";
		$one["stock"]          = "总计";
		$one["total_apply_no"] = $total_apply_no;
		$one["diff_stock"]     = "";
		$one["unit"]           = "";
		$one["price"]          = "";
		$one["price_total"]    = $total_money." 元";
		$one["remark"]         = " ";
		$expList[$next_index]  = $one;
		// print_r($expList);exit;
		$objPHPExcel = $this->_explodeHead();
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '用品名称')  
					->setCellValue('B1', '规格')
					->setCellValue('C1', '现有库存') 
					->setCellValue('D1', '需要总库存') 
					->setCellValue('E1', '需及时购买库存') 
					->setCellValue('F1', '单位') 
					->setCellValue('G1', '单价') 
					->setCellValue('H1', '总价') 
					->setCellValue('I1', '备注'); 
		// 内容  
		for ($i = 0, $len = count($expList); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $expList[$i]['name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $expList[$i]['format']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $expList[$i]['stock']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $expList[$i]['total_apply_no']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $expList[$i]['diff_stock']);
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $expList[$i]['unit']);    
			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $expList[$i]['price']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $expList[$i]['price_total']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $expList[$i]['remark']); 
		}  
		$excelFileName = '需采购的办公用品-'.date('YmdHis',time());
		$this->_exploadFoot($objPHPExcel,$excelFileName,$excelFileName);
	}

	/**
	 * [导出办公用 description]
	 * @return [type] [description]
	 */
	function excelOfficeNeedBuy($where){
		$result = array("msg"=>"","data"=>"","code"=>"500");
		$sql = "SELECT 
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
				  a.status,
				  a.depart_id,
				  d.pid,
				  d1.name AS firstname
				FROM
				  `boss_oa_office_apply` AS a 
				  LEFT JOIN `boss_oa_office_product` AS p 
				    ON a.product_id = p.id 
				  LEFT JOIN `boss_user` AS u 
				    ON u.id = a.uid 
				  LEFT JOIN `boss_user_department` AS d 
				    ON d.id = a.`depart_id` 
				  LEFT JOIN `boss_user_department` AS d1 
				    ON d1.id = d.`pid` {$where} ";
				// print_r($sql);exit;
		if(I("showsql")=="showsql023"){
			print_r($sql);exit;
		}
				
		$model          = new \Think\Model();
		$list           = $model->query($sql);
		if(!$list){
			$result = array("msg"=>"无数据，请检查","data"=>"","code"=>"500");
			return $result;
		}
		$total_money_all = 0;
		$total_no = 0;
		foreach ($list as $k => $v) {
			$total_no = $total_no+$v['apply_no'];
			$total_money = $v['apply_no']*$v['price'];
			$list[$k]["total_money"] = $total_money;
			$total_money_all = $total_money_all+$total_money;
		}
		$lastIndex                             = count($list);
		$list[$lastIndex]["name"]              = "";
		$list[$lastIndex]["format"]            = "";
		$list[$lastIndex]["fist_departName"]   = "";
		$list[$lastIndex]["second_departName"] = "";
		$list[$lastIndex]["real_name"]         = "合计：";
		$list[$lastIndex]["apply_no"]          = $total_no;
		$list[$lastIndex]["unit"]              = "";
		$list[$lastIndex]["price"]             = "";
		$list[$lastIndex]["total_money"]       = $total_money_all;
		$list[$lastIndex]["dateline"]          = "";

		// print_r($list);exit;
		$objPHPExcel = $this->_explodeHead();
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '用品名称')  
					->setCellValue('B1', '规格')
					->setCellValue('C1', '一级部门') 
					->setCellValue('D1', '二级部门') 
					->setCellValue('E1', '申请人') 
					->setCellValue('F1', '申请数量') 
					->setCellValue('G1', '单位') 
					->setCellValue('H1', '单价') 
					->setCellValue('I1', '总金额')
					->setCellValue('J1', '申请时间'); 
		// 内容  
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['format']);
			$deaprtdata = getFirstLeveDepartName($list[$i]['firstname'],$list[$i]['depart_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2),$deaprtdata["fist_departName"]); 
			
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2),$deaprtdata["second_departName"]);
			unset($deaprtdata);
			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['real_name']);    
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['apply_no']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['unit']);
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $list[$i]['price']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $list[$i]['total_money']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 2), $list[$i]['dateline']); 
		}  
		$excelFileName = '办公用品-'.date('YmdHis',time());
		$this->_exploadFoot($objPHPExcel,$excelFileName,$excelFileName);
	}

	/**
	 * 导出当月工资条
	 * @param  [type] $list          [description]
	 * @param  [type] $sheetTitle    [description]
	 * @param  [type] $excelFileName [description]
	 * @return [type]                [description]
	 */
	function excelCurrentMonthSalaryDataLine($list,$sheetTitle,$excelFileName){
		$objPHPExcel = $this->_explodeHead();
		// 表头  
		$objPHPExcel->setActiveSheetIndex(0)  
					->setCellValue('A1', '邮箱')  
					->setCellValue('B1', '姓名')
					->setCellValue('C1', '职位') 
					->setCellValue('D1', '实际出勤天数') 
					->setCellValue('E1', '基本工资') 
					->setCellValue('F1', '职务工资') 
					->setCellValue('G1', '绩效工资') 
					->setCellValue('H1', '季度奖金') 
					->setCellValue('I1', '季度提成') 
					->setCellValue('J1', '其他') 
					->setCellValue('K1', '其他扣款') 
					->setCellValue('L1', '考勤扣款') 
					->setCellValue('M1', '工龄津贴') 
					->setCellValue('N1', '交通补助') 
					->setCellValue('O1', '通讯补助') 
					->setCellValue('P1', '全勤奖') 
					->setCellValue('Q1', '其他福利') 
					->setCellValue('R1', '代扣社保') //是社保总和吗？//??
					->setCellValue('S1', '代扣公积金') //是公积金总和吗？//??
					->setCellValue('T1', '个税扣款') 
					->setCellValue('U1', '罚款代扣') 
					->setCellValue('V1', '其他项') 
					->setCellValue('W1', '实发工资') 
					->setCellValue('X1', '备注')
					->setCellValue('Y1', '当月状态');  
		// 内容  
		for ($i = 0, $len = count($list); $i < $len; $i++) {  
			$objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 2), $list[$i]['post_email']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 2), $list[$i]['user_name']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 2), $list[$i]['duty']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 2), $list[$i]['fact_chuqin_days']); 
			$objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 2), $list[$i]['basic_pay']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 2), $list[$i]['job_salary']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 2), $list[$i]['per_pay']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 2), $list[$i]['quarte_bonus']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 2), $list[$i]['quarte_commi']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 2), $list[$i]['other_add']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 2), $list[$i]['other_less']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 2), $list[$i]['attend_less']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('M' . ($i + 2), $list[$i]['age_allowance']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('N' . ($i + 2), $list[$i]['trasport_allowance']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('O' . ($i + 2), $list[$i]['commun_allowance']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('P' . ($i + 2), $list[$i]['perfect_award']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('Q' . ($i + 2), $list[$i]['other_benefy']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('R' . ($i + 2), $list[$i]['widthlold_social']);  //??
			$objPHPExcel->getActiveSheet(0)->setCellValue('S' . ($i + 2), $list[$i]['withhold_profund']);  //??
			$objPHPExcel->getActiveSheet(0)->setCellValue('T' . ($i + 2), $list[$i]['tax_deduc']);  //??
			$objPHPExcel->getActiveSheet(0)->setCellValue('U' . ($i + 2), $list[$i]['fine_widhhold']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('V' . ($i + 2), $list[$i]['other_item']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('W' . ($i + 2), $list[$i]['total_wages']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('X' . ($i + 2), $list[$i]['remark']);  
			$objPHPExcel->getActiveSheet(0)->setCellValue('Y' . ($i + 2), $list[$i]['dangyue_status']); 
		}  
		$this->_exploadFoot($objPHPExcel,$sheetTitle,$excelFileName);
	}

}

?>