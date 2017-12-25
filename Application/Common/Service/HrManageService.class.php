<?php
/**
* 人事管理
*/
namespace Common\Service;
use Think\Model;
use Common\Service;
class HrManageService extends BaseService
{
	
	/**
	 * 根据条件获取列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getHRListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow="",$is_use_sql=false){
		$list = false;
		if($is_use_sql){
			$sql = "SELECT 
					  {$fields_}
					FROM
					  `boss_oa_hr_manage` AS h 
					  left join boss_oa_position as p on p.id=h.duty
					  LEFT JOIN `boss_user_department` AS d ON d.id=h.`depart_id`
    				  LEFT JOIN `boss_user_department` AS d1 ON d1.id=d.`pid`
					WHERE {$where_} 
					order by {$order_}
					limit {$firstRow},{$lastRow} ";
			$model = new \Think\Model();
			$list = $model->query($sql);
		}else{
			$list = M("oa_hr_manage")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		}
		return $list;
	}

	/**
	 * 获取个数
	 * @param  [type] $where_ [description]
	 * @return [type]         [description]
	 */
	function getHRListCountByWhere($where_,$is_use_sql=false){
		$count = 0;
		if($is_use_sql){
			$sql = "SELECT 
					  COUNT(1) as num
					FROM
					  `boss_oa_hr_manage` AS h 
					WHERE {$where_} ";
			$model = new \Think\Model();
			$list = $model->query($sql);
			$count = $list[0]["num"];
		}else{
			$count = M("oa_hr_manage")->field("id")->where($where_)->count();
		}
		
		return $count;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneHRByWhere($where_,$fields_){
		$list = M("oa_hr_manage")->field($fields_)->where($where_)->find();
		return $list;
	}
	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveHRData($where_,$data){
		$row = M("oa_hr_manage")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addHRData($data){
		$row = M("oa_hr_manage")->add($data);
		return $row;
	}

	/**
	 * 添加日志
	 * @param [type] $data [description]
	 */
	function addHRLogData($data){
		$row = M("oa_hr_change_log")->add($data);
		return $row;
	}

	/**
	 * 获取日志
	 * @param  [type] $where_  [description]
	 * @param  string $fields_ [description]
	 * @param  string $orders  [description]
	 * @return [type]          [description]
	 */
	function getHrLogListByWhere($where_,$fields_="",$orders=""){
		$list = M("oa_hr_change_log")->field($fields_)->where($where_)->order($orders)->select();
		return $list;
	}


	/**
	 * 添加导入操作 20160406
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
    public function doExcelToArray($data,$type=""){
    	// print_r(3);
    	// print_r($data);exit;
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
            if($type=="importHrInfo"){
            	$doExcelDbResult        = $this->createHrData($toDbArray);
            }else{
            	$doExcelDbResult        = $this->makeExcelToProductsTableList($toDbArray);
            }
            // print_r($doExcelDbResult);
            // print_r(1);exit;
			$returnArray["status"] = $doExcelDbResult["status"];
			$returnArray["data"]    = $doExcelDbResult["data"];
        }else{
            $returnArray["status"] = false;
        }
        // print_r("---end---");exit;
        return $returnArray;
    }

    /**
     * 写入数据库
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function createHrData($data){
    	$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		if(!$data){ return false;}
		$depSer             = new Service\DepartSettingService();
		$educateTree        = getNameByEducateTree();
		$employeeStatusTree = getNameByStatusTree();
		$companyTree        = $depSer->getCompanyIDByNameTree();
		$userSer            = new Service\UserService();
		import("Org.Util.Pinyin");
		$py          = new \Pinyin(); 
		$com_Name    = "";
		foreach ($data as $k => $v) {
			if($k==0) continue;
			//检查当前用户是在存在在用户表中user;real_name,status=1,username(拼音)
			$save_["user_name"]       = iconv_($v["2"]);
			$body_no            = iconv_($v["9"]);
			if(!$save_["user_name"]) continue;
			if(!$body_no) continue;


			// $save_["username_pinyin"] = $py->getAllPY(iconv_($v["2"]));
			$py->convert(iconv_($v["2"]),"",$allWord,$firstWord);
			$save_["username_pinyin"] = $allWord[0];


			$save_["sex"]             = iconv_($v["8"])=="男"?0:1;
			$save_["is_marry"]        = iconv_($v["13"])=="已婚"?1:0;
			$save_["phone"]           = iconv_($v["28"]);
			$save_["qq"]              = iconv_($v["30"]);
			$save_["educate_name"]    = iconv_($v["12"]);
			$save_["nation"]          = iconv_($v["17"]);
			$save_["profession"]      = iconv_($v["27"]);
			$save_["adress"]          = iconv_($v["19"]);
			$status_name              = iconv_($v["58"]);
			$save_["status"]          = $employeeStatusTree[$status_name][0]["key"];
			$user_result              = $this->_checkSystemUserGetUserId($userSer,$save_);
			$user_id                  = $user_result["data"]["user_id"];
			$job_no                   = $user_result["data"]["employee_number"];
			if(($user_result["status"] != 200) || empty($user_id)){
				$rel["data"][] = $save_["user_name"]."同步用户表时出错：".$user_result["msg"].";";
				continue;
			}

			//需要优化
			$c_name = trim(iconv_($v["1"]));
			if(!$c_name){
				$c_name = $com_Name;
			}else{
				$com_Name = $c_name;
			}
			$save_["company_id"]         = $companyTree[$c_name]["id"];
			$save_["job_rating"]         = iconv_($v["4"]);
			
			$save_["grade_rank"]         = iconv_($v["5"]);
			$save_["id_card_address"]    = iconv_($v["20"]);
			
			$save_["level"]              = iconv_($v["6"]);
			$save_["emergen_user"]       = iconv_($v["21"]);
			$save_["birth"]              = iconv_($v["10"]);
			$save_["relation_me"]        = iconv_($v["22"]);
			$save_["age"]                = iconv_($v['11']);
			$save_["emergen_phone"]      = iconv_($v["23"]);

			$save_["start_work"]         = iconv_($v["12"]);
			$save_["educate"]            = $educateTree[iconv_($v["24"])];
			$save_["graduat_time"]       = iconv_($v["25"]);

			$save_["body_no"]            = iconv_($v["9"]);

			$save_["height"]             = iconv_($v["14"]);
			$save_["graduat_school"]     = iconv_($v["26"]);
			$save_["entry_time"]         = iconv_($v["31"]);
			$save_["weight"]             = iconv_($v["15"]);
			$save_["entry_salary"]       = iconv_($v["39"]);
			$save_["account_nature"]     = iconv_($v["16"]);
			$save_["turn_salary"]        = iconv_($v["40"]);
			$save_["back_phone"]         = iconv_($v["29"]);
			$save_["proposed_time"]      = iconv_($v["37"]);
			$save_["passby"]             = iconv_($v["18"]);
			$save_["provident_start"]    = iconv_($v["36"]);
			$save_["real_proposed"]      = iconv_($v["34"]);
			$save_["per_radio"]          = iconv_($v["38"]);
			$save_["social_card"]        = iconv_($v["37"]);
			$save_["company_age"]        = iconv_($v["32"]);
			$save_["per_pay"]            = iconv_($v["43"]);
			$save_["basic_pay"]          = iconv_($v["41"]);
			$save_["social_man"]         = iconv_($v["35"]);
			$save_["fist_visa_end"]      = iconv_($v["48"]);
			$save_["try_start"]          = iconv_($v["31"]);
			$save_["try_end"]            = iconv_($v["34"]);
			$save_["third_visa_start"]   = iconv_($v["51"]);
			$save_["sec_visa_start"]     = iconv_($v["49"]);
			$save_["job_salary"]         = iconv_($v["42"]);
			$save_["bank_cardno1"]       = iconv_($v["53"]);

			$save_["first_visa_start"]   = iconv_($v["47"]);
			$save_["bank_card2"]         = iconv_($v["56"]);
			$save_["bank_card1"]         = iconv_($v["54"]);
			$save_["sec_visa_end"]       = iconv_($v["50"]);
			$save_["rec_channel"]        = iconv_($v["60"]);
			$save_["sign_company"]       = iconv_($v["57"]);
			$save_["probihibe"]          = iconv_($v["52"]);
			$save_["job_change"]         = iconv_($v["61"]);
			$save_["bank_cardno2"]       = iconv_($v["55"]);
			$save_["departure_time"]     = iconv_($v["59"]);
			$save_["job_no"]             = $job_no;
			$save_["age_allowance"]      = iconv_($v["44"]);
			$save_["trasport_allowance"] = iconv_($v["45"]);
			$save_["commun_allowance"]   = iconv_($v["46"]);
			$save_["user_id"]            = $user_id;
			//绩效 2015-Q1-95;
			$per = array();
			$per[0] = "2014-Q4-".iconv_($v["62"]);
			$per[1] = "2015-Q1-".iconv_($v["63"]);
			$per[2] = "2015-Q2-".iconv_($v["64"]);
			$per[3] = "2015-Q3-".iconv_($v["65"]);
			$per[4] = "2015-Q4-".iconv_($v["66"]);
			$per[5] = "2016-Q1-".iconv_($v["67"]);
			$per[6] = "2016-Q2-".iconv_($v["68"]);
			$per[7] = "2016-Q3-".iconv_($v["69"]);
			$per[8] = "2016-Q4-".iconv_($v["70"]);
			$per[9] = "2017-Q1-".iconv_($v["71"]);
			$save_["performance"] = json_encode($per);

			//检查hr_manage表中数据是否存在:身份证号
			$where_ = array();
			$where_["body_no"]         = trim($save_["body_no"]);
			$hrOne                     = $this->getOneHRByWhere($where_,"id");
			if($hrOne){
				//修改
				$where_ = array();
				$where_["id"] = $hrOne["id"];
				$row = $this->saveHRData($where_,$save_);
				if($row){
					$rel["data"][] = "用户id:".$user_id."-用户姓名：".$save_["user_name"]."-身份证号：".$save_["body_no"]."修改信息成功;";
				}else{
					$rel["data"][] = "用户id:".$user_id."-用户姓名".$save_["user_name"]."-身份证号：".$save_["body_no"]."修改信息失败或者没修改，请检查;";
				}
			}else{
				//添加
				$save_["depart_name"]    = "研发组";//部门名称?
				$save_["depart_id"]      = "180";//部门id？
				$save_["creat_uid"]      = UID;
				$save_["third_visa_end"] = "";
				$save_["post_level"]     = "";//岗位层级
				$save_["start_insure"]   = "";//参保时间?
				$save_["dateline"]       = date("Y-m-d H:i:s",time());
				$row = $this->addHRData($save_);
				if($row){
					$rel["data"][] = "用户id:".$user_id."-用户姓名".$save_["user_name"]."-身份证号：".$save_["body_no"]."添加信息成功;";
				}else{
					$rel["data"][] = "用户id:".$user_id."-用户姓名".$save_["user_name"]."-身份证号：".$save_["body_no"]."添加信息失败，请检查;";
				}
			}
			
		}
		return $rel;
    }

    /**
     * 检查用户信息,存在=》修改；不存在=》添加；最终返回user_id
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    private function _checkSystemUserGetUserId($userSer_,$params){
		//如果重现重名且在职，那么提示用户重名
		$userSer = !$userSer_ ? new Service\UserService() : $userSer_;
		$where_["real_name"] = $params["user_name"];
		$where_["status"]    = 1;
		$where_["username"]  = $params["username_pinyin"];;
		$one                 = $userSer->getOneByWhere($where_);
		$result = array("status"=>0,"msg"=>"创建用户失败,请联系管理员","data"=>array());
		if($one){
			//修改信息
			$data["gender"]       =  $params["sex"];
			$data["mobile"]       =  $params["phone"];
			$data["qq"]           =  $params["qq"];
			$data["education"]    =  $params["educate_name"];
			$data["ethnic_group"] =  $params["nation"];
			$data["major"]        =  $params["profession"];
			$data["address"]      =  $params["adress"];
			$data["ismarried"]    =  $params["is_marry"];
			$data["status"]       =  $params["status"] == 1 ?0:1;
			$where_               = array();
			$where_["id"]         = $one["id"];
			$row                  = $userSer->saveUserData($where_,$user_);
			$result = array("status"=>200,"msg"=>"成功","data"=>array("user_id"=>$one["id"],"employee_number"=>$one["employee_number"]));
		}else{
			//添加信息
			$data["dept_id"]          =  1;
			$data["position_id"]      =  0;
			$data["real_name"]        =  $params["user_name"];
			$data["username"]         =  $params["username_pinyin"];
			$data["password"]         =  boss_md5(123456, UC_AUTH_KEY);
			$data["reg_time"]         =  time();
			$data["gender"]           =  $params["sex"];
			$data["mobile"]           =  $params["phone"];
			$data["qq"]               =  $params["qq"];
			$data["education"]        =  $params["educate_name"];
			$data["ethnic_group"]     =  $params["nation"];
			$data["major"]            =  $params["profession"];
			$data["address"]          =  $params["adress"];
			$data["ismarried"]        =  $params["is_marry"];
			$data["status"]           =  1;
			$user_id                  = $userSer->addData($data);
			//添加成功
			$where["id"]              = $user_id;
			$data_["employee_number"] = $userSer->generalOACode($user_id);
			$row                      = $userSer->saveUserData($where,$data_);
			if($row) $result = array("status"=>200,"msg"=>"成功","data"=>array("user_id"=>$user_id,"employee_number"=>$data_["employee_number"]));
		}
		return $result;
	} 




	/**
	* 将excel数据整理成表格形式---导入特殊工资到数据库中
	*/
	public function makeExcelToProductsTableList($data){
		// print_r("in");
		// print_r($data);exit;
		$rel  = false;
		$list = array();
		$row  = 0;
		$rel  = array(
			"status" =>true,
			"data"   =>array(),
		);
		$msg      = "";
		$userInfo = "";
		foreach ($data as $k => $v) {
			$body_no = trim($v["D"]);
			//获取员工基本信息
			$where["body_no"] = $body_no;
			$field            = "body_no,id,entry_salary,turn_salary,per_radio,per_pay,basic_pay,job_salary,status,level,age_allowance,trasport_allowance,user_name,user_id,entry_time";
			$hr_one           = $this->getOneHRByWhere($where,$field);
			if(!$hr_one){
				if($body_no=="") { continue; };
				$userInfo = "身份证号为：".$body_no."的员工在系统中不存，请联系管理员检查";
				$rel["data"][] = $userInfo;
				continue;
			}

			//检查重复--根据日期和用户id
			$salary_month = trim($v["B"]);//工资条所属月份，需转换--这个在操作excel的时候一定要注意
			// $time         = ($salary_month-25569)*24*60*60;
			// $salary_date  = date('Y-m', $time);//2017-04
			$salary_date = $salary_month;

			$datas        = array("hr_uid"=>$hr_one["user_id"],"salary_date"=>$salary_month);
			$count_s      = $this->checkMonthsalayIsRepeat($datas);
			if($count_s>0){
				$userInfo = "姓名：".$hr_one["user_name"].";身份证:".$body_no.";".$salary_month."特殊工资[".$hr_one["user_id"]."]-[".$salary_month."];";
				$msg      = "已存在,请仔细检查{$count_s};";
			}else{
				//保存数据
				$data["hr_uid"]              = $hr_one["user_id"];
				$data["entry_salary"]        = $hr_one["entry_salary"];//入职薪资
				$data["turn_salary"]         = $hr_one["turn_salary"];//转正薪资
				$data["per_radio"]           = $hr_one["per_radio"];//绩效考核比例
				$data["per_pay"]             = $hr_one["per_pay"];//绩效工资
				$data["basic_pay"]           = $hr_one["basic_pay"];//基本工资
				$data["job_salary"]          = $hr_one["job_salary"];//职务工资
				$data["stand_salary"]        = $hr_one["basic_pay"]+$hr_one["job_salary"]+$hr_one["per_pay"];//工资标准,(工资标准=基本工资+职务工资+绩效工资)
				$data["quarte_bonus"]        = $v["E"];//季度奖金
				$data["quarte_commi"]        = $v["F"];//季度提成
				$data["other_add"]           = $v["G"];//其它加钱金额
				$data["other_less"]          = $v["H"];//其他扣款

				//根据司龄重新计算，司龄已当前月的月底为截止时间，入职时间：entry_time
				//司龄--系统计算，根据入职时间
				$entry_time = $hr_one["entry_time"];
				$fact_days  = getMonthDays_com($salary_date);
				$end_time   = $salary_date."-".$fact_days;
				$diff_days  = getDatesDiff($entry_time,$end_time);
				$diff_year  = 0;
				$diff_month = 0;
				if(!empty($diff_days["y"]) && $diff_days["y"]!="00"){
					$diff_year = preg_replace('/^0*/', '', $diff_days["y"]);
				}
				if(!empty($diff_days["m"]) && $diff_days["m"]!="0"){
					$diff_month = $diff_days["m"];
				}
				$company_age         = $diff_year.".".$diff_month;
				$butie_list          = $this->getBuTieList($hr_one["level"],$company_age);
				$data["company_age"] = $company_age;
				$data["level"]       = $hr_one["level"];


				$data["age_allowance"]       = $butie_list["gl_money"];//工龄津贴,高层：300元/年；中层：200元/年；基层：100元/年。从第二年开始每年50元叠加。
				// print_r($butie_list);exit;
				//交通补助根据考勤算   实际交通补助=交通补助/应出勤天数（21.75）*实际出勤天数；
				$data["trasport_allowance"] = $butie_list["jt_money"];

				// $data["trasport_allowance"] = $trasport_allowance/21.75*
				$data["commun_allowance"]    = $butie_list["zx_money"];//通讯补助(高层：200元/月；中层：100元/月；基层：0）

				$data["perfect_award"]       = 0;//全勤奖  --接口调取
				$data["other_benefy"]        = trim($v["I"]);//其他福利
				$data["total_benefy"]        = 0;//福利合计，工资导出再计算，福利合计=工龄津贴+交通补助+通讯补助+全勤奖+其他福利
				$data["factpay_wages"]       = $hr_one["basic_pay"]+$hr_one["job_salary"]+$hr_one["per_pay"]+$v["E"]+$v["F"]+$v["G"]+$data["total_benefy"];//应发工资合计=基本工资+职务工资+绩效工资+季度奖金+季度提成+其他(加款)+福利合计。

				$data["widthlold_social"]    = trim($v["J"]);//代扣社保
				$data["withhold_next"]       = trim($v["K"]);//代扣下月社保
				$data["withhold_profund"]    = trim($v["L"]);//代扣公积金
				$data["compay_ass_socsec"]   = trim($v["M"]);//公司承担社保
				$data["company_ass_profund"] = trim($v["N"]);//公司承担公积金
				$data["tax_incom"]           = 0;//应纳税所得额 ??导出工资计算
				$data["tax_deduc"]           = 0;//个税扣款 ???
				$data["fine_widhhold"]       = trim($v["O"]);//罚款代扣
				$data["other_item"]          = trim($v["P"]);//其他项(不扣个税)
				$data["salary_date"]         = trim($v["B"]);//薪水所属年月份，例如：2017年5月
				$data["remark"]              = trim($v["Q"]);//备注
				$row              = $this->addHRMonthsalaryData($data);

				$userInfo = "姓名：".$hr_one["user_name"].";身份证:".$body_no.";".$data["salary_date"]."特殊工资";
				$msg          = "导入失败;(请联系管理员检查)；";
				if($row) $msg = "导入成功;";
			}
			// exit;
			$rel["data"][] = $userInfo.$msg;
		}
		return $rel;
	}

	/**
	 * [getBuTieList description]
	 * @param  [type] $level       [description]
	 * @param  [type] $company_age [description]
	 * @return [type]              [description]
	 */
	private function getBuTieList($level,$siling){
		$list         = array("gl_money"=>0,"jt_money"=>0,"zx_money"=>0);
		$siling_int   = intval($siling);
		$siling_int_1 = $siling_int-1;
		if($level==1 && $siling>1){
			$list["gl_money"] = 100+($siling_int_1*50);
			
		}
		if($level==1){
			$list["jt_money"] = 100;
		}

		if($level==2 && $siling>1){
			$list["gl_money"] = 200+($siling_int_1*50);
		}
		if($level==2){
			$list["jt_money"] = 200;
			$list["zx_money"] = 100;
		}

		if($level==3 && $siling>1){
			$list["gl_money"] = 300+($siling_int_1*50);
		}
		if($level==3){
			$list["jt_money"] = 200;
			$list["zx_money"] = 200;
		}
		return $list;
	}


	

	/**
	 * 检查每月月薪导入是否重复
	 * @return [type] [description]
	 */
	function checkMonthsalayIsRepeat($data){
		$where["hr_uid"] = $data["hr_uid"];
		$where["salary_date"] = $data["salary_date"];
		$count = $this->getMonthSalaryCountByWhere($where,"id");
		if($count>0) return true;
		return false; 
	}

	/**
	 * 根据条件获取条数
	 * @return [type] [description]
	 */
	function getMonthSalaryCountByWhere($where_,$fields_="id"){
		$count = M("oa_hr_monthsalary")->field($fields_)->where($where_)->count();
		return $count;
	}

	/**
	 *    
	 * 添加工资
	 * @param [type] $data [description]
	 */
	function addHRMonthsalaryData($data){
		$row = M("oa_hr_monthsalary")->add($data);
		return $row;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveHRMonthsalaryData($where_,$data){
		$row = M("oa_hr_monthsalary")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 获取员工考勤记录
	 * @param  [type] $where_  [description]
	 * @param  string $fields_ [description]
	 * @param  string $orders  [description]
	 * @return [type]          [description]
	 */
	function getUserAttendListByWhere($where_,$fields_="",$orders=""){
		$list = M("attendance_charge")->field($fields_)->where($where_)->order($orders)->select();
		return $list;
	}

	/**
	 * [makeAttendTree description]
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	function makeAttendTree($list){
		$newList = array();
		foreach ($list as $k => $v) {
			$newList[$v["date"]."=>".$v["user_id"]] = $v;
		}
		return $newList;
	}

	/**
	 * 每天执行--同步员工司龄
	 * @return [type] [description]
	 */
	function synHrUserCompanyAgeSer(){
		//更新在职员工的工龄
		$list = M("oa_hr_manage")->field("id,entry_time")->where(array("status"=>array("neq",1)))->select();
		foreach ($list as $k => $v) {
			$data["company_age"] = 0;
			if($v["entry_time"]!="0000-00-00 00:00:00"){
				$diff_days  = getDatesDiff($v["entry_time"],date("Y-m-d",time()));
				$diff_year  = 0;
				$diff_month = 0;
				if(!empty($diff_days["y"]) && $diff_days["y"]!="00"){
					$diff_year = preg_replace('/^0*/', '', $diff_days["y"]);
				}
				if(!empty($diff_days["m"]) && $diff_days["m"]!="0"){
					$diff_month = $diff_days["m"];
				}
				$data["company_age"] = $diff_year.".".$diff_month;
			}

			$row = M("oa_hr_manage")->where(array("id"=>$v["id"]))->save($data);
			if($row){
				echo $v["id"].'--'.$data["company_age"].'--'."--success<br/>";
			}else{
				echo $v["id"].'--'.$data["company_age"].'--'."--fail<br/>";
			}
			unset($data);
		}
	}

}
?>