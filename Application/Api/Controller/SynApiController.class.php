<?php
/**
* 同步接口 tgd 2017-03-02
*/
namespace Api\Controller;
use Common\Service;
use Think\Model;
class SynApiController extends ApiController {
	//通用返回参数
	private $result = array("errcode"=>"5000","msg"=>"处理失败","data"=>"0");
	private $chargingSer;//计费标识servers
	private $daySer;//收入数据对象service
	private $indanSer;//收入成本明细service
	private $dayOutSer;//成本明细service
	/**
	 * 同步广告主信息 tgd 2017-03-02
	 * [description] BOS系统接收到广告主信息进行去重判断，如广告主类型为个人，则直接添加；如类型为企业，则以广告主名称去重，即名称相同则视为同一广告主。
	 * @return [type] [返回广告主id]
	 */
	public function synAdInfo(){
		$bl_id          = !empty(I("bl_id")) ? I("bl_id") : "";//默认传营销平台业务线ID=43
		$ad_name        = !empty(I("name")) ? I("name") : ""; //广告主名称
		$ad_type        = !empty(I("ad_type")) ? I("ad_type") : "";//企业或个人（2-个人，1-企业）
		$account_name   = !empty(I("account_name")) ? I("account_name") : "";//发票抬头	对应bos系统中的“账户名称”
		$opening_bank   = !empty(I("opening_bank")) ? I("opening_bank") : "";//开户行名称
		$bank_no        = !empty(I("bank_no")) ? I("bank_no") : "";//开户行账号
		$taxpayer_num   = !empty(I("taxpayer_num")) ? I("taxpayer_num") : "";//纳税人识别号
		$invoice_type   = !empty(I("invoice_type")) ? I("invoice_type") : "";//发票类型
		$invoice_remark = !empty(I("invoice_remark")) ? I("invoice_remark") : "";//发票内容
		$reg_mobile     = !empty(I("reg_mobile")) ? I("reg_mobile") : "";//注册电话
		$reg_address    = !empty(I("reg_address")) ? I("reg_address") : "";//注册地址
		$rec_json_data  = !empty($_REQUEST["rec_json_data"]) ? trim($_REQUEST["rec_json_data"]) : "";//收件人json数据
		$con_json_data  = !empty($_REQUEST["con_json_data"]) ? trim($_REQUEST["con_json_data"]) : "";//联系人json数据

		//返回信息
		$this->result["data"] = array("ad_id"=>0);

		//类型为企业去重判断,存在直接返回ad_id
		$backData = $this->checkAdvertiserExists($ad_name,$ad_type);
		if($backData["status"]){
			$this->result = array(
				"errcode" =>"0",
				"msg"     =>"广告主已存在",
				"data"    =>array("ad_id"=>$backData["ad_id"]),
				);
			$this->responseExit($this->result);
			exit;
		}

		//添加广告主信息
		$data["ad_type"]        = $ad_type;
		$data["name"]           = $ad_name;
		$data["account_name"]   = $account_name;
		$data["opening_bank"]   = $opening_bank;
		$data["bank_no"]        = $bank_no;
		$data["taxpayer_num"]   = $taxpayer_num;
		$data["invoice_type"]   = $invoice_type;
		$data["invoice_remark"] = $invoice_remark;
		$data["reg_mobile"]     = $reg_mobile;
		$data["reg_address"]    = $reg_address;

		$ad_id = M("advertiser")->add($data);

		//添加广告主收件人
		$rec_user_list = json_decode($rec_json_data,true);
		foreach ($rec_user_list as $k => $v) {
			$recData["ad_id"]   = $ad_id;
			$recData["name"]    = $v["rec_name"];
			$recData["mobile"]  = $v["rec_phone"];
			$recData["address"] = $v["rec_address"];
			$rec_id = M("advertiser_fireceiver")->add($recData);
			unset($recData);
		}

		//添加广告主联系人信息
		$con_user_list = json_decode($con_json_data,true);
		foreach ($con_user_list as $k => $v) {
			$conData["ad_id"]  = $ad_id;
			$conData["name"]   = $v["con_name"];
			$conData["mobile"] = $v["con_phone"];
			$conData["qq"]     = $v["con_qq"];
			$conData["email"]  = $v["con_email"];
			$con_id = M("advertiser_contacts")->add($conData);
			unset($conData);
		}

		//添加成功
		if($ad_id){
			$this->result = array(
				"errcode" =>"0",
				"msg"     =>"广告主添加成功",
				"data"    =>array("ad_id"=>$ad_id),
				);
		}
		//记录日志
		$this->apiLogWrite($this->result);
		$this->responseExit($this->result);
	}

	/**
	 * [检查广告主是否存在]
	 * @param  [type] $ad_name [广告主名称]
	 * @param  [type] $ad_type [广告类型]
	 * @return [type]          [description]
	 */
	private function checkAdvertiserExists($ad_name,$ad_type){
		$_result = array("status"=>false,"ad_id"=>0);
		if($ad_type == 2){
			return $_result;exit;
		}
		$adOne = M("advertiser")->field("id")->where(array("name"=>$ad_name,"ad_type"=>$ad_type))->find();
		if($adOne){
			$_result = array("status"=>true,"ad_id"=>$adOne["id"]);
		}
		return $_result;
	}

	/**
	 * 同步产品
	 * [description] 根据产品名称查询，存在修改数据；不存在添加产品数据
	 * @return [type] [返回产品id
	 */
	public function synProducts(){
		$bl_id       = !empty(I("bl_id")) ? I("bl_id") : "";//默认传营销平台业务线ID=43
		$name        = !empty(I("name")) ? I("name") : ""; //产品名称
		$ad_id       = !empty(I("ad_id")) ? I("ad_id") : ""; //广告主ID
		$type        = !empty(I("type")) ? I("type") : ""; //产品类型
		$source_type = !empty(I("source_type")) ? I("source_type") : ""; //产品类型
		$category    = !empty(I("category")) ? I("category") : ""; //产品所属行业
		$saler_id    = !empty(I("saler_id")) ? I("saler_id") : ""; //取广告主的所属商务

		$where["name"] = $name;
		$where["ad_id"] = $ad_id;
		$productOne = M("product")->field("id")->where($where)->find();
		$data["ad_id"]       = $ad_id;
		$data["type"]        = $type;
		$data["source_type"] = $source_type;
		$data["category"]    = $category;
		$data["saler_id"]    = $saler_id;
		if($productOne){
			//修改信息
			$where_pro["id"] = $productOne["id"];
			$row = M("product")->where($where_pro)->save($data);
			if($row){
				$this->result["errcode"] = "0";
				$this->result["msg"]     = "修改产品信息成功";
				$this->result["data"]    = array("id"=>$productOne["id"]);
			}
		}else{
			//添加信息
			$data["name"] = $name;
			$row = M("product")->add($data);
			if($row){
				$this->result["errcode"] = "0";
				$this->result["msg"]     = "添加产品信息成功";
				$this->result["data"]    = array("id"=>$row);
			}
		}
		//记录日志
		$this->apiLogWrite($this->result);
		$this->responseExit($this->result);
	}

	/**
	 * 根据供应商名称查询供应商信息
	 * @return [type] [description]
	 */
	public function synSupplier(){
		$bl_id = !empty(I("bl_id")) ? I("bl_id") : "";//默认传营销平台业务线ID=43
		$name  = !empty(I("name")) ? I("name") : ""; //供应商名称
		$where["name"] = array("like","%{$name}%");
		$field = "id,code,name,email,type,mobile,region,address,contract_num";
		$list  = M("supplier")->field($field)->where($where)->select();
		if($list){
			$this->result["errcode"] = "0";
			$this->result["msg"]     = "返回成功";
			$this->result["data"]    = array("list"=>$list);
		}
		//记录日志
		$this->apiLogWrite($this->result);
		$this->responseExit($this->result);
	}
	
	
	/**
	 * 同步收入数据
	 * @return [type] [description]
	 */
	public function synIncommonData(){
		$data["paramList"] = !empty($_REQUEST["paramList"]) ? trim($_REQUEST["paramList"]) : ""; //收入发生日期
		//通过接口传入的广告主，供应商，产品，验证计费标识是否存在，不存在则创建
		$paramList = json_decode($data["paramList"],true);
		$paramListCount = count($paramList);
		$dealCount = 0;
		$jfid_array = array();
		//批量添加
		if($paramList){
			foreach ($paramList as $kp => $vp) {
				//参数
				$data["add_time"]       = $vp["add_time"];//收入发生日期
				$data["prot_id"]        = $vp["prot_id"];//产品ID
				$data["ad_id"]          = $vp["ad_id"];//广告主ID
				$data["superid"]        = $vp["superid"]; //供应商ID
				$data["price"]          = $vp["price"]; //金额
				$data["salerid"]        = $vp["salerid"]; //销售人员
				$data["operation_type"] = $vp["operation_type"]; //操作类型 1--//发布（即添加）;2-//回收;3--//封禁

				//逻辑处理
				$this->chargingSer = !$this->chargingSer ? new Service\ChargingLogoService() : $this->chargingSer;
				$where["ad_id"]   = $data["ad_id"];
				$where["superid"] = $data["superid"];
				$where["prot_id"] = $data["prot_id"];
				$field = "id";
				$dataOne = $this->chargingSer->getChargingOneByWhere($where,$field);
				$jfid = $dataOne["id"];
				if(!$dataOne){
					//添加数据
					$saveData["add_time"] = $data["add_time"];
					$saveData["prot_id"]  = $data["prot_id"];
					$saveData["ad_id"]    = $data["ad_id"];
					$saveData["superid"]  = $data["superid"];
					$saveData["price"]    = $data["price"];

					$charging_logoInfo = M("charging_logo");
					$charging_logoInfo->startTrans();//开始事务
					$jfid = $this->chargingSer->addChargingLog($saveData);
					if($jfid){
						//添加成功
						$saveData_["code"] = $this->chargingSer-> generalCodePub($jfid);
						$where_["id"] = $jfid;
						$row = $this->chargingSer->saveData($saveData_,$where_);
						if($row){
							$charging_logoInfo->commit();//提交事务
						}else{
							$charging_logoInfo->rollback();//提交事务
						}
					}else{
						//添加失败
						$charging_logoInfo->rollback();//回滚事务
						$this->result["msg"] = "处理失败,计费标识id不详";
						break;
					}
				}

				//返回计费标识id
				if($jfid){
					$jfid_array[$kp] = $jfid;
					$dealCount++;
					$data["jfid"] = $jfid;
					switch ($data["operation_type"]) {
						case 1:
							//发布（即添加）
							$this->addAndUpdateData($data);
							break;
						case 2:
							//回收
							$this->recyOperation($data);
							break;
						case 3:
							//封禁
							$this->updateBanned($data);
							break;	
					}
				}else{
					//回滚处理
					break;
				}
			}
		}else{
			$this->result["msg"]  = "接收同步收入数据json数据有误";
		}
		
		//返回处理
		if($dealCount==$paramListCount){
			$this->result["errcode"] = "0";
			$this->result["msg"]     = "返回成功";
			$this->result["data"]    = implode($jfid_array, ",");
		}
		//记录日志
		$this->apiLogWrite($this->result);
		$this->responseExit($this->result);
	}

	/**
	 * 修改封禁
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function updateBanned($param){
		$this->daySer = !$this->daySer ? new Service\DayDataService() : $this->daySer;
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$data["status"] = 9;

		$daydataInfo = M("daydata");
		$daydataInfo->startTrans();//开始事务
		$row = $this->daySer->updateDayData($data,$where);
		if($row){
			$daydataInfo->commit();//提交事务
		}else{
			$daydataInfo->rollback();//回滚事务
		}
	}

	/**
	 * 回收操作
	 * @param  [type] $param [description]
	 * @return [type]        [description]
	 */
	private function recyOperation($param){
		$this->daySer = !$this->daySer ? new Service\DayDataService() : $this->daySer;
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$data["newmoney"] = 0;
		$data["newdata"] = "";
		$daydataInfo = M("daydata");
		$daydataInfo->startTrans();//开始事务
		$row = $this->daySer->updateDayData($data,$where);
		if($row){
			$daydataInfo->commit();//提交事务
		}else{
			$daydataInfo->rollback();//回滚事务
		}
		//更新收入明细数据
		unset($where);
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$this->indanSer = !$this->indanSer ? new Service\DaydataInandoutService() : $this->indanSer;
		$data_indan["in_newmoney"] = 0;
		$data_indan["in_newdata"] = "";

		$daydataInfo = M("daydata_inandout");
		$daydataInfo->startTrans();//开始事务
		$row = $this->indanSer->updateInan($data_indan,$where);
		if($row){
			$daydataInfo->commit();//提交事务
		}else{
			$daydataInfo->rollback();//回滚事务
		}
	}


	/**
	 * [发布（即添加）数据]
	 * @param [type] $data [description]
	 */
	private function addAndUpdateData($param){
		//通过上一步返回的计费标识ID和日期到收入表进行唯一性验证，如果不存在则添加，存在则进行修改此表数据
		$this->daySer = !$this->daySer ? new Service\DayDataService(): $this->daySer ;
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$dayOne = $this->daySer->getDayDataOneByWhere($where);

		$dayDataOne = M("daydata");
		$dayDataOne->startTrans();//开始事务

		$saveData["jfid"]    = $param["jfid"];
		$saveData["adddate"] = $param["add_time"];
		$saveData["comid"]   = $param["prot_id"];
		$saveData["adverid"] = $param["ad_id"];
		$saveData["superid"] = $param["superid"];
		$saveData["money"]   = $param["price"];
		$saveData["salerid"] = $param["salerid"];
		$saveData["type"]    = $param["operation_type"];
		if($dayOne["id"]){
			//修改数据
			unset($where);
			$where["id"] = $dayOne["id"]; 
			$row = $this->daySer->updateDayData($saveData,$where);
			if($row){
				$dayDataOne->commit();//提交事务
			}else{
				$dayDataOne->rollback();//回滚事务
			}
		}else{
			//添加数据
			$day_id = $this->daySer->addDayData($saveData);
			if($day_id){
				$dayDataOne->commit();//提交事务
			}else{
				$dayDataOne->rollback();//回滚事务
			}
			$where["id"] = $day_id;
			$dayOne = $this->daySer->getDayDataOneByWhere($where);
		}

		//同步所有修改的收入数据至明细表（boss_daydata_inandout）
		unset($where);
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		
		unset($field);
		$field = "jfid,id";
		$this->indanSer = !$this->indanSer ? new Service\DaydataInandoutService()  : $this->indanSer;
		$indanOne = $this->indanSer->getInanDouOneByWhere($where,$field);

		$daydata_inandoutInfo = M("daydata_inandout");
		$daydata_inandoutInfo->startTrans();//开始事务

		unset($saveData);
		$saveData["jfid"]          = $param["jfid"];
		$saveData["adddate"]       = $param["add_time"];
		$saveData["in_id"]         = $dayOne["id"];
		$saveData["in_money"]      = $dayOne["money"];
		$saveData["in_newmoney"]   = $dayOne["newmoney"];
		$saveData["in_datanum"]    = $dayOne["datanum"];
		$saveData["in_newdata"]    = $dayOne["newdata"];
		$saveData["in_comid"]      = $dayOne["comid"];
		$saveData["in_status"]     = $dayOne["status"];
		$saveData["in_adverid"]    = $dayOne["adverid"];
		$saveData["in_lineid"]     = $dayOne["lineid"];
		$saveData["in_price"]      = $dayOne["price"];
		$saveData["in_remarks"]    = $dayOne["remarks"];
		$saveData["in_auditdate"]  = $dayOne["auditdate"];
		$saveData["in_salerid"]    = $dayOne["salerid"];
		$saveData["in_banimgpath"] = $dayOne["banimgpath"];
		$saveData["in_ztid"]       = $dayOne["ztid"];
		$saveData["in_ischeck"]    = $dayOne["is_check"];

		if($indanOne["id"]){
			//修改收入明细
			unset($where);
			$where["id"] = $indanOne["id"];
			$row = $this->indanSer->updateInan($saveData);
			if($row){
				$daydata_inandoutInfo->commit();//提交事务
			}else{
				$daydata_inandoutInfo->rollback();//回滚事务
			}
		}else{
			//添加明细
			$row = $this->indanSer->addInan($saveData);
			if($row){
				$daydata_inandoutInfo->commit();//提交事务
			}else{
				$daydata_inandoutInfo->rollback();//回滚事务
			}
		}
	}

	/**
	 * 同步成本
	 * @return [type] 
	 */
	public function synCost(){
		$data["paramList"] = !empty($_REQUEST["paramList"]) ? trim($_REQUEST["paramList"]) : ""; //参数json列表
		//通过接口传入的广告主，供应商，产品，验证计费标识是否存在，不存在则创建
		$paramList = json_decode($data["paramList"],true);
		$paramListCount = count($paramList);
		$dealCount = 0;
		$jfid_array = array();
		//批量添加
		if($paramList){
			foreach ($paramList as $kp => $vp) {
				//参数
				$data["add_time"]       = $vp["add_time"];//收入发生日期
				$data["prot_id"]        = $vp["prot_id"];//产品ID
				$data["ad_id"]          = $vp["ad_id"];//广告主ID
				$data["superid"]        = $vp["superid"]; //供应商ID
				$data["price"]          = $vp["price"]; //金额
				$data["salerid"]        = $vp["salerid"]; //销售人员
				$data["operation_type"] = $vp["operation_type"]; //操作类型 1--//发布（即添加）;2-//回收;3--//封禁

				//逻辑处理
				$this->chargingSer = !$this->chargingSer ? new Service\ChargingLogoService() : $this->chargingSer;
				$where["ad_id"]   = $data["ad_id"];
				$where["superid"] = $data["superid"];
				$where["prot_id"] = $data["prot_id"];
				$field = "id";
				$dataOne = $this->chargingSer->getChargingOneByWhere($where,$field);
				$jfid = $dataOne["id"];
				if(!$dataOne){
					//添加数据
					$saveData["add_time"] = $data["add_time"];
					$saveData["prot_id"]  = $data["prot_id"];
					$saveData["ad_id"]    = $data["ad_id"];
					$saveData["superid"]  = $data["superid"];
					$saveData["price"]    = $data["price"];

					$charging_logoInfo = M("charging_logo");
					$charging_logoInfo->startTrans();//开始事务
					$jfid = $this->chargingSer->addChargingLog($saveData);
					if($jfid){
						//添加成功
						$saveData_["code"] = $this->chargingSer-> generalCodePub($jfid);
						$where_["id"] = $jfid;
						$row = $this->chargingSer->saveData($saveData_,$where_);
						if($row){
							$charging_logoInfo->commit();//提交事务
						}else{
							$charging_logoInfo->rollback();//提交事务
						}
					}else{
						//添加失败
						$charging_logoInfo->rollback();//回滚事务
						$this->result["msg"] = "处理失败,计费标识id不详";
						break;
					}
				}
				//返回计费标识id
				if($jfid){
					$jfid_array[$kp] = $jfid;
					$dealCount++;
					$data["jfid"] = $jfid;
					switch ($data["operation_type"]) {
						case 1:
							//发布（即添加）
							$this->addAndUpdateCost($data);
							break;
						case 2:
							//回收
							$this->recyOperationCost($data);
							break;
						case 3:
							//封禁
							$this->updateBannedCost($data);
							break;	
					}
				}else{
					//回滚处理
					break;
				}
			}
		}else{
			$this->result["msg"]  = "接收同步收入数据json数据有误";
		}
		
		//返回处理
		if($dealCount==$paramListCount){
			$this->result["errcode"] = "0";
			$this->result["msg"]     = "返回成功";
			$this->result["data"]    = implode($jfid_array, ",");
		}

		//记录日志
		$this->apiLogWrite($this->result);
		$this->responseExit($this->result);
	}

	/**
	 * [封禁数据]
	 * @param  [type] $param [description]
	 * @return [type]        [description]
	 */
	private function updateBannedCost($param){
		$this->dayOutSer = !$this->dayOutSer ? new Service\DayDataOutService() : $this->dayOutSer;
		$where["jfid"]    = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$data["status"] = 9;

		$daydataInfo = M("daydata_out");
		$daydataInfo->startTrans();//开始事务
		$row = $this->dayOutSer->updateDayOut($data,$where);
		if($row){
			$daydataInfo->commit();//提交事务
		}else{
			$daydataInfo->rollback();//回滚事务
		}
	}

	/**
	 * [回收成本数据]
	 * @return [type] [description]
	 */
	private function recyOperationCost($param){
		$this->dayOutSer = !$this->dayOutSer ? new Service\DayDataOutService() : $this->dayOutSer;
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$data["newmoney"] = 0;
		$data["newdata"] = "";
		$daydataOutInfo = M("daydata_out");
		$daydataOutInfo->startTrans();//开始事务
		$row = $this->dayOutSer->updateDayOut($data,$where);
		if($row){
			$daydataOutInfo->commit();//提交事务
		}else{
			$daydataOutInfo->rollback();//回滚事务
		}

		//更新收入明细数据
		unset($where);
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$this->indanSer = !$this->indanSer ? new Service\DaydataInandoutService() : $this->indanSer;
		$data_indan["out_newmoney"] = 0;
		$data_indan["out_newdata"] = "";

		$daydataInfo = M("daydata_inandout");
		$daydataInfo->startTrans();//开始事务
		$row = $indanSer->updateInan($data_indan,$where);
		if($row){
			$daydataInfo->commit();//提交事务
		}else{
			$daydataInfo->rollback();//回滚事务
		}
	}

	/**
	 * 更新和添加成本
	 * @param [type] $param [description]
	 */
	private function addAndUpdateCost($param){
		//通过上一步返回的计费标识ID和日期到成本表进行唯一性验证，如果不存在则添加，存在则进行修改此表数据
		$this->dayOutSer = !$this->dayOutSer ? new Service\DayDataOutService() : $this->dayOutSer;
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		$outOne = $this->dayOutSer->getDayOutOneByWhere($where);

		$dayDataOutOne = M("daydata_out");
		$dayDataOutOne->startTrans();//开始事务

		$saveData["jfid"]       = $param["jfid"];
		$saveData["adddate"]    = $param["add_time"];
		$saveData["superid"]    = $param["superid"];
		$saveData["money"]      = $param["price"];
		$saveData["businessid"] = $param["salerid"];
		$saveData["type"]       = $param["operation_type"];
		if($outOne["id"]){
			//修改数据
			unset($where);
			$where["id"] = $outOne["id"]; 
			$row = $this->dayOutSer->updateDayOut($saveData,$where);
			if($row){
				$dayDataOutOne->commit();//提交事务
			}else{
				$dayDataOutOne->rollback();//回滚事务
			}
		}else{
			//添加数据
			$day_id = $this->dayOutSer->addDayDataOut($saveData);
			if($day_id){
				$dayDataOutOne->commit();//提交事务
			}else{
				$dayDataOutOne->rollback();//回滚事务
			}
			$where["id"] = $day_id;
			$outOne = $this->dayOutSer->getDayOutOneByWhere($where);
		}

		//同步所有修改的成本数据至明细表（boss_daydata_inandout）
		unset($where);
		$where["jfid"] = $param["jfid"];
		$where["adddate"] = $param["add_time"];
		unset($field);
		$field = "jfid,id";
		$this->indanSer = !$this->indanSer ? new Service\DaydataInandoutService() : $this->indanSer;
		$indanOne = $this->indanSer->getInanDouOneByWhere($where,$field);

		$daydata_inandoutInfo = M("daydata_inandout");
		$daydata_inandoutInfo->startTrans();//开始事务

		unset($saveData);
		$saveData["jfid"]           = $param["jfid"];
		$saveData["adddate"]        = $param["add_time"];
		$saveData["out_id"]         = $outOne["id"];
		$saveData["out_money"]      = $outOne["money"];
		$saveData["out_newmoney"]   = $outOne["newmoney"];
		$saveData["out_datanum"]    = $outOne["datanum"];
		$saveData["out_newdata"]    = $outOne["newdata"];
		$saveData["out_status"]     = $outOne["status"];
		$saveData["out_superid"]    = $outOne["superid"];
		$saveData["out_businessid"] = $outOne["businessid"];
		$saveData["out_auditdate"]  = $outOne["auditdate"];
		$saveData["out_price"]      = $outOne["price"];
		$saveData["out_lineid"]     = $outOne["lineid"];
		$saveData["out_sbid"]       = $outOne["sbid"];
		$saveData["out_remarks"]    = $outOne["remarks"];
		$saveData["out_addid"]      = $outOne["addid"];

		if($indanOne["id"]){
			//修改收入明细
			unset($where);
			$where["id"] = $indanOne["id"];
			$row = $this->indanSer->updateInan($saveData);
			if($row){
				$daydata_inandoutInfo->commit();//提交事务
			}else{
				$daydata_inandoutInfo->rollback();//回滚事务
			}
		}else{
			//添加明细
			$row = $this->indanSer->addInan($saveData);
			if($row){
				$daydata_inandoutInfo->commit();//提交事务
			}else{
				$daydata_inandoutInfo->rollback();//回滚事务
			}
		}
	}

	/**
	 * 获取广告主信息
	 * @return [type] [description]
	 */
	public function queryAdvers(){
		$adSer = new Service\AdvertiserService();
		$where = "1=1";
		$field = "id,name";
		$list  = $adSer->getListByWhere($where,$field);
		if($list){
			$this->result["errcode"] = "0";
			$this->result["msg"]     = "返回成功";
			$this->result["data"]    = $list;
		}

		//记录日志
		$this->apiLogWrite($this->result);
		$this->responseExit($this->result);
	}

	/**
	 * 查询boss销售，商务专员给dsp
	 * @return [type] [description]
	 */
	public function querySalerUsers(){
		$sql = 'SELECT 
				  u.id,
				  u.real_name 
				FROM
				  `boss_auth_group_access` AS a 
				  LEFT JOIN boss_user AS u 
				    ON a.uid = u.id 
				WHERE a.group_id IN (4, 5, 6, 7) 
				  AND u.status = 1 ';
		$model = new Model();
		$list = $model->query($sql);
		if($list){
			$this->result["errcode"] = "0";
			$this->result["msg"]     = "返回成功";
			$this->result["data"]    = $list;
		}

		//记录日志
		$this->apiLogWrite($this->result);
		$this->responseExit($this->result);
	}
}

?>