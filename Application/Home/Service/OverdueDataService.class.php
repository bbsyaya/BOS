<?php 
/**
* 逾期数据service
*/
namespace Home\Service;
use Think\Model;
use Common\Service;
class OverdueDataService extends CommonService
{
	
	/**
	 * 结算周期内的逾期收入，成本不通知2017-11-22
	 * @return [type] [description]
	 */
	function synCheckOverdueDataSer(){
		//查找所有未结算收入的产品id集合
 		$email_list = array();//准备发送邮件
			$pro_list=M('daydata')
 			->field("a.comid,a.adddate,b.settle_cycle,a.status,a.salerid,a.reason_yq,DATE_FORMAT(a.adddate, '%Y-%m') AS yq_date,SUM(IF(a.status in (1,2,3,4), a.newmoney, 0)) AS newmoney")
 			->join("a join boss_product b ON a.comid = b.id ")
 			->where("DATE_FORMAT(a.adddate, '%Y-%m') >= '2017-08' && a.newmoney>0 && a.status in (1,2,3,4)")
 			->group("a.comid")
 			->select();
 		$proids = $this->getProductIds($pro_list);
 		$proids_arr=explode(',', $proids);
 		foreach ($pro_list as $key => $value) {
 			if(!in_array($value['comid'], $proids_arr))unset($pro_list[$key]);
 		}

 	
		//获取当前所有用户的职务，姓名，邮箱
		$userList   = $this->getUserInfos($pro_list);
		foreach ($pro_list as $k => $v) {
			//判断当前是收入逾期，还是成本逾期,根据人员职务来区分；销售对应收入逾期，商务对应成本逾期(陶艳,王敏,陈胜勤,洪凯阳)；
			$user = $userList[$v["salerid"]];
			//收入逾期提醒
			if($v["newmoney"]>0){
				$one["date"]                                            = $v["yq_date"];
				$one["email"]                                           = $userList[$v["salerid"]]["post_email"];
				$one["yq_money"]                                        = $v["newmoney"];
				$email_list[$v["salerid"]]["userInfo"]                  = $user;
				$email_list[$v["salerid"]]["dataList"][$v["yq_date"]][] = $one;
				unset($one);
			}
		}
 		if(I("showsql")=="showsql023"){
 			print_r($email_list);return;
 		}
 		$this->sendEmails($email_list);
	}

	/**
	 * [sendEmails description]
	 * @param  [type] $list [description]
	 * @return [type]       [description]
	 */
	private function sendEmails($list){
		if(!$list) return false;
		Vendor("PHPMailer.emailSend");
		$mail    = new \emailSend();
		$pre_web = C("WEB_URL");
		foreach ($list as $k => $v) {
			$config["recpEmailAddress"] = $v["userInfo"]["post_email"];
			$type_name                  = "收入";
			$user_name                  = $v["userInfo"]["user_name"];
			$config["subject"]          = $user_name.$type_name."逾期数据提醒";
			$config["imgUrl"]           = $pre_web."/Public/Home/img/yqmb.png";
			$body                       = "<font style='color:red;'>亲，Bos系统检测到您有   【".$type_name;
			$body                       .="逾期数据】  ，请您登录Bos系统或者点击下面链接检查并填写对应的【{$type_name}】逾期原因。</font><br/>";
			
			foreach ($v["dataList"] as $kv => $vv) {
				$body  .= "<a href='".$pre_web."/RiskOverdue/detail?mon=".$kv."' target='_blank'>".$pre_web."/RiskOverdue/detail?mon=".$kv."</a> <br><br>";
			}
			
			$body                      .= "<img src='".$config["imgUrl"]."' style='width:100%;'/>";
			$config["body"]            = $body;
			
			//抄送
			$config["cc_user_list"][0] = "yyjk@yandui.com";
			
			$re                        = $mail->send($config);
			if($re["status"]==1){
				echo "ok<br>";
			}else{
				echo "fail<br>";
			}
			unset($type_name);unset($user_name);
		}
	}

	/**
	 * [判断当前是收入逾期，还是成本逾期,根据人员职务来区分；销售对应收入逾期，商务对应成本逾期(陶艳,王敏,陈胜勤,洪凯阳)；
	 * @param  [type] $user [description]
	 * @return [type]       [description]
	 */
	private function getUserTypesByDatas($user){
		$type = 0;
		if(strstr($user["duty"], "销")){
			$type = 1;//收入
		}
		if(strstr($user["duty"], "商务")){
			$type = 2;//成本
		}
		if($user["user_name"]=="陶艳" || $user["user_name"]=="王敏" || $user["user_name"]=="陈胜勤" || $user["user_name"]=="洪凯阳"){
			$type = 2;//成本
		}
		return $type;
	}

	/**
	 * [获取当前所有用户的职务，姓名，邮箱 description]
	 * @param  [type] $yq_datas [description]
	 * @return [type]           [description]
	 */
	private function getUserInfos($yq_datas){
		if(!$yq_datas) return false;
		$user_ids = "";
		foreach ($yq_datas as $k => $v) {
			$user_ids .= $v["salerid"].",";
		}
		if($user_ids){
			$user_ids = substr($user_ids, 0,strlen($user_ids)-1);
		}
		$user_ids = empty($user_ids)?"0":$user_ids;

		$sql = "SELECT 
				  h.user_id,
				  h.`user_name`,
				  p.`name` as duty,
				  d.name AS depart1,
				  d1.name AS depart2,
				  h.post_email
				FROM
				  `boss_oa_hr_manage` AS h 
				  LEFT JOIN `boss_oa_position` AS p 
				    ON h.`duty` = p.`id` 
				  LEFT JOIN `boss_user_department` AS d 
				    ON d.id = h.`depart_id` 
				  LEFT JOIN `boss_user_department` AS d1 
				    ON d.pid = d1.id 
				WHERE h.`user_id` IN ({$user_ids}) AND h.status<>1
				ORDER BY h.user_name DESC ";
		$model = new \Think\Model();
		$user_list = $model->query($sql);
		$users = array();
		foreach ($user_list as $k => $v) {
			$users[$v["user_id"]] = $v;
		}
		unset($sql);
		unset($user_list);
		unset($user_ids);
		return $users;
	}

	/**
	 * [getProductIds description]
	 * @param  [type] $yq_productslists [description]
	 * @return [type]                   [description]
	 判断每一个产品是否逾期，返回所有逾期产品ID字符串
	 */
	private function getProductIds($yq_productslists){
		$proids = "";
		if(!$yq_productslists) return false;
		foreach ($yq_productslists as $k => $v) {
			switch ($v["settle_cycle"]) {
				case 1:////周
					if( strtotime("+1week",strtotime($v['adddate']))< strtotime(date("Y-m-d")) ){
                        $proids .= $v['comid'].",";
                    }
					break;
				
				case 2:////半月
					if( strtotime($v['adddate'].' +15 day') < strtotime(date("Y-m-d")) ){
                        $proids .= $v['comid'].",";
                    }
					break;
				case 3:////月
					if( strtotime("+1months",strtotime($v['adddate']))< strtotime(date("Y-m-d")) ){
                        $proids .= $v['comid'].",";
                    }
					break;
				case 4:////季度
					if( strtotime("+3months",strtotime($v['adddate']))< strtotime(date("Y-m-d")) ){
                        $proids .= $v['comid'].",";
                    }
					break;
				case 6:////季度
					if( strtotime("+2months",strtotime($v['adddate']))< strtotime(date("Y-m-d")) ){
                        $proids .= $v['comid'].",";
                    }
					break;
				case 7:////季度
					if( strtotime($v['adddate'].' +1 day') < strtotime(date("Y-m-d")) ){
                        $proids .= $v['comid'].",";
                    }
					break;	
			}
		}
		if($proids){
			$proids = substr($proids, 0,strlen($proids)-1);
		}
		return $proids;
	}
	Public function makedatastatusmsg(){
		$time=date("m-d");
		$arr_date=array('01-01','04-01','07-01','10-01');
		$data_p_j=$data_p_b=$data_p_z=$data_p_y=array();
		if(in_array($time,$arr_date)){
			//所有季度结算的
			$str_m='0'.((int)date("m")-3);//起点月份
			if($str_m==-2)$str_m=10;
			$str_d=date("Y-").$str_m.'-01';
			$end_d=date("Y-m-d",time()-24*60*60);
			$data_p_j=M('product')->field('a.*,left(b.adddate,7) as date,sum(newmoney) as newmoney,"'.$str_d.'" as str_d,"'.$end_d.'" as end_d')->join("a join boss_daydata b on a.id=b.comid")->where("a.settle_cycle=4 && b.status=1 && b.adddate>='$str_d' && b.adddate<='$end_d'")->group('a.id')->select();
		}
		if(date('d')=='01' || date('d')=='16'){
			//所有半月结算的
			if($date('d')=='01'){
				$str_d=date("Y-").date('m',time()-24*60*60)."-16";
			}else{
				$str_d=date("Y-m")."-01";
			}
			$end_d=date("Y-m-d",time()-24*60*60);
			$data_p_b=M('product')->field('a.*,left(b.adddate,7) as date,sum(newmoney) as newmoney,"'.$str_d.'" as str_d,"'.$end_d.'" as end_d')->join("a join boss_daydata b on a.id=b.comid")->where("a.settle_cycle=2 && b.status=1 && b.adddate>='$str_d' && b.adddate<='$end_d'")->group('a.id')->select();
		}
		if(date('w')==1){
			//所有周结算的
			$str_d=date("Y-m-d",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y")));
			$end_d=date("Y-m-d",mktime(23,59,59,date("m"),date("d")-date("w")+7-7,date("Y")));
			$data_p_z=M('product')->field('a.*,left(b.adddate,7) as date,sum(newmoney) as newmoney,"'.$str_d.'" as str_d,"'.$end_d.'" as end_d')->join("a join boss_daydata b on a.id=b.comid")->where("a.settle_cycle=1 && b.status=1 && b.adddate>='$str_d' && b.adddate<='$end_d'")->group('a.id')->select();
		}
		//所有月结算的且通知时间为三天后
		$str_d=date("Y-m-d",mktime(0, 0 , 0,date("m")-1,1,date("Y")));
		$end_d=date("Y-m-d",mktime(23,59,59,date("m") ,0,date("Y")));
		$red=(int)date('d',time()+24*60*60*3);
		$data_p_y=M('product')->field('a.*,left(b.adddate,7) as date,sum(newmoney) as newmoney,"'.$str_d.'" as str_d,"'.$end_d.'" as end_d')->join("a join boss_daydata b on a.id=b.comid")->where("a.settle_cycle=3 && b.status=1 && b.adddate>='$str_d' && b.adddate<='$end_d' && a.reconciliation_day=$red")->group('a.id')->select();
		$allplist=array();
		foreach ($data_p_j as $key => $value) {
			$allplist[]=$value;
		}
		foreach ($data_p_b as $key => $value) {
			$allplist[]=$value;
		}
		foreach ($data_p_z as $key => $value) {
			$allplist[]=$value;
		}
		foreach ($data_p_y as $key => $value) {
			$allplist[]=$value;
		}

		$this->sendEmails_2($allplist);
			exit;
		
		
	}

	/**
	 * 发送邮件，提醒确认数据
	 */
	private function sendEmails_2($list){
		if(!$list) return false;
		$prompt_information= M('prompt_information');
		foreach ($list as $key => $value) {
			$addData = array();
			$addData['date_time'] = date('Y-m-d H:i:s');
			$addData['send_user'] = $value['saler_id'];
			$addData['content'] = "您有收入数据还未确认:产品名称(".$value['name']."),时间周期".$value['str_d'].'~'.$value['end_d'];
			$addData['a_link'] = "/Makesettlement/makeSettlementIn?comname=".$value['name']."&strtime=".$value['str_d']."&endtime=".$value['end_d'];
			$prompt_information->add($addData);
		}

	}
}
?>