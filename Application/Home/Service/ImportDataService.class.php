<?php
/**
* import service
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class ImportDataService
{
	
	/**
	 * 导入个人提成信息
	 * @return [type] [description]
	 */
	function importUserRules(){
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		$upload->rootPath = "./upload/excel/";//保存根路径
		$upload->savePath = "";//保存根路径
		$upload->saveRule = 'uniqid';//是否自动命名
		if (! file_exists ( $upload->savePath )) {
			mkdir ( $upload->savePath );
		}
		$upload->uploadReplace = true;
		$info = $upload->upload ();
		if (! $info) {
			$result["msg"] = $upload->getError ();
			$this->ajaxReturn ($result);
		}else{
			$file = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$savename ['name'] = $file ['name'];

			//读取excel数据
			$PHPReader = new \PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($filePath)){
				$PHPReader = new \PHPExcel_Reader_Excel5();
				if(!$PHPReader->canRead($filePath)){
					$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
					return $result;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			// print_r(1);exit;
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();
			// print_r($allRow);exit;
			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "F"; $currentColumn++) {
					//数据坐标
					$address = $currentColumn.$currentRow;
					//读取到的数据，保存到数组$arr中
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();

				}
			}
			if(count($excelData)==0){
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				return $result;
			}

			//转换为数组
			$result = $this->doExcelToArray_($excelData,"importUserRules");
			//生成导入数据日志
			$logdata = implode(",", $result["data"]);
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
				return $result;
			}else{
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
				return $result;
			}
		}
	}

	/**
	* 添加导入操作 20160406
	* @param  [type] $data [description]
	* @return [type]       [description]
	*/
    function doExcelToArray_($data,$type){
        $returnArray = array(
						"status" =>"false",
						"data"   =>array()
        	);
        if(is_array($data)&&count($data)>0){
			$badArray   = array();//没查库就错误的数组
			$rightArray = array();//最后成功的数组
			$toDbArray  = array();//要去查库的数组
            foreach($data as $k=>$v){
                $isDoDb = true;
                if($isDoDb){//操作开关为真的时候，插入可以查库的数组
                    $toDbArray[$k] = $v;
                }
                else{//操作开关为假的时候，这行执行完啦，整理错误
                    $badArray[] = array($k,$msgStr);
                }
            }
            $doExcelDbResult = array();
            switch ($type) {
            	case 'importUserRules':
            		$doExcelDbResult        = $this->createimportUserRules($toDbArray);
            		break;
            	case 'importAdverInfoSer':
            		$doExcelDbResult        = $this->creatImportAdverInfoSer($toDbArray);
            		break;
            }
            
			$returnArray["status"] = $doExcelDbResult["status"];
			$returnArray["data"]    = $doExcelDbResult["data"];
        }else{
            $returnArray["status"] = false;
        }
        return $returnArray;
    }


    /**
     * 写入数据库
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function createimportUserRules($data){
    	// print_r($data);exit;
    	$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		$model = new \Think\Model();
		foreach ($data as $k => $v) {
			$name = trim($v["A"]);
			$sql = "SELECT 
					  hr.`user_name`,
					  dep.`name`,
					  po.`name` AS duty,hr.leve_depart_id,hr.user_id
					FROM
					  `boss_oa_hr_manage` AS hr 
					  LEFT JOIN `boss_user_department` AS dep 
					    ON dep.id = hr.`leve_depart_id` 
					  LEFT JOIN `boss_oa_position` AS po 
					    ON po.id = hr.`duty` 
					    WHERE hr.`user_name`='{$name}'";
		    $one = $model->query($sql);
		    if($one){
				$data["rule"]     = trim($v["B"]);
				$data["groupid"]  = $one["leve_depart_id"];
				$data["usertype"] = 2;
				$data["uid"]      = $one["user_id"];
				$data["in_num"]   = trim($v["C"]);
				$data["out_num"]  = trim($v["D"]);
				$id               = M("userrule")->add($data);//个人--添加
				if($id){
					$rel["data"][] = "用户名称:".$name."个人提成添加成功;";
				}else{
					$rel["data"][] = "用户名称:".$name."个人提成添加失败;";
				}
		    }else{
		    	$rel["data"][] = "用户名称:".$name."在boss系统未找到，请检查;";
		    }
		}
		return $rel;
    }

    /**
     * 导入带拓展广告主信息
     * @return [type] [description]
     */
    function importAdverInfoSer(){
    	import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		$upload->rootPath = "./upload/excel/";//保存根路径
		$upload->savePath = "";//保存根路径
		$upload->saveRule = 'uniqid';//是否自动命名
		if (! file_exists ( $upload->savePath )) {
			mkdir ( $upload->savePath );
		}
		$upload->uploadReplace = true;
		$info = $upload->upload ();
		if (! $info) {
			$result["msg"] = $upload->getError ();
			$this->ajaxReturn ($result);
		}else{
			$file = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$savename ['name'] = $file ['name'];

			//读取excel数据
			$PHPReader = new \PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($filePath)){
				$PHPReader = new \PHPExcel_Reader_Excel5();
				if(!$PHPReader->canRead($filePath)){
					$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
					return $result;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			// print_r(1);exit;
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();
			// print_r($allRow);exit;
			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= "Z"; $currentColumn++) {
					//数据坐标
					$address = $currentColumn.$currentRow;
					//读取到的数据，保存到数组$arr中
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();

				}
			}
			if(count($excelData)==0){
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				return $result;
			}

			//转换为数组
			$result = $this->doExcelToArray_($excelData,"importAdverInfoSer");
			//生成导入数据日志
			$logdata = implode(",", $result["data"]);
			if($result["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
				return $result;
			}else{
				$result = array("msg"=>"导入失败","code"=>500,"data"=>$savename."(".$filePath.")","logdata"=>$logdata);
				return $result;
			}
		}
    }

    /**
     * 导入带拓展广告主信息
     * @return [type] [description]
     */
    function creatImportAdverInfoSer($data){
    	// print_r($data);
    	$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		//计费模式
		$charging_mode_options = getChargingIdByName();
		//业务线
		$lineList = getLineNameTree();
		// print_r($charging_mode_options);
		$userSer = new Service\UserService();
		$depSer = new Service\DepartSettingService();
		$adSer = new Service\ExtendAdvService();
		$ext_list = getExtendStatusIdByName();

		// exit;

		foreach ($data as $k => $v) {
			$fj_msg = "";//附加信息
			$data_["company_name"] = trim($v["B"]);
			$data_["partner"]      = trim($v["C"]);
			$char_line = trim($v["D"]);
			$data_["bil_method"]   = $charging_mode_options[$char_line]["key"];
			if(!$data_["bil_method"]){
				$fj_msg .= "【计费方式为空】";
			}
			unset($char_line);

			$data_["line_id"]      = $lineList[trim($v["E"])]["id"];
			if(!$data_["line_id"]){
				$fj_msg .= "【业务线为空】";
			}
			$data_["area"]         = trim($v["F"]);
			$data_["target"]       = trim($v["G"]);
			$data_["adver_advan"]  = trim($v["H"]);
			$data_["volume"]       = trim($v["I"]);
			$data_["relevance"]    = trim($v["J"]);
			$data_["history_case"] = trim($v["K"]);
			$data_["demand_type"]  = trim($v["L"])=="供应商"?2:1;//需求类型
			//信息收集者
			$sj_name = trim($v["M"]);
			$user = $userSer->getOneByWhere(array("real_name"=>$sj_name),"id");
			$data_["create_uid"]   = $user["id"];
			if(!$data_["create_uid"]){
				$fj_msg .= "【信息收集者为空】";
			}

			//指派部门
			$dname = trim($v["N"]);
			$data_["depart_names"] = $dname;
			$dep_one = $depSer->getOneByWhere(array("name"=>$dname),"id");
			$data_["depart_id"]    = $dep_one["id"];
			if(!$data_["depart_id"]){
				$fj_msg .= "【分配部门为空】";
			}
			unset($dname);unset($dep_one);

			$data_["need_user"]    = trim($v["O"]);
			$data_["status"]       = 0;//默认待跟进
			
			$data_["contact_way"]  = trim($v["S"]);
			$data_["contact_user"] = trim($v["R"]);
			$data_["remark"]       = trim($v["T"]);
			$data_["dateline"]     = date("Y-m-d H:i:s",time());
			
			$row = $adSer->addData($data_);
			unset($data_);
			if($row){
				//跟进状态，跟进结果保存到跟进表中
				$data_["result"]     = trim($v["Q"]);
				$gj_msg = "";
				if($data_["result"]){
					$data_["type_id"]    = 1;
					$data_["visit_way"]  = "";
					
					$data_["status"]     = $ext_list[trim($v["P"])]["key"];
					$data_["remark"]     = "";
					$data_["expand_id"]  = $row;
					$vtime              = trim(I("visit_time"));
					$data_["visit_time"] = empty($vtime)?date("Y-m-d H:i:s",time()):$vtime;
					$data_["follow_uid"] = $user["id"];

					$row_ = $adSer->addFollowData($data_);
					if($row_){
						$gj_msg = "跟进信息添加成功";
					}else{
						$gj_msg = "跟进信息添加失败，请检查";
					}
				}
				
				$rel["data"][] = $data["company_name"]."添加成功;".$fj_msg."--".$gj_msg;
			}else{
				$rel["data"][] = $data["company_name"]."添加失败，请检查;".$fj_msg."--".$gj_msg;
			}
		}
		return $rel;
    }


}
?>