<?php
/**
 * Created by PhpStorm.
 * User: owq
 * Date: 2017/4/11
 * Time: 11:20
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;

//对账
class DuizhangController extends BaseController {
	public function index(){
		if(I('get.advname')!='')$wheres[]="b.name like '%".I('get.advname')."%'";
		if(I('get.username')!='')$wheres[]="c.real_name like '%".I('get.username')."%'";
		$wheres[]="a.is_duizhang=0";
		//数据权限
		/*
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;
		*/

		$count=M('settlement_in')->join("a join boss_advertiser b on a.advid=b.id join boss_user c on a.salerid=c.id")->where(implode(' && ', $wheres))->group('b.id')->count();
		$this->getpagelist($count);
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$this->data=M('settlement_in')->field('b.name as advname,a.advid,count(a.id) as setnum')->join("a join boss_advertiser b on a.advid=b.id join boss_user c on a.salerid=c.id")->where(implode(' && ', $wheres))->group('b.id')->order('a.id desc')->limit($str.',10')->select();
		$this->display();
	}
	public function setlist(){
		//未对账结算单列表
		if(I('get.comname')!='')$wheres[]="d.name like '%".I('comname')."%'";
		if(I('get.username')!='')$wheres[]="c.real_name like '%".I('get.username')."%'";
		$wheres[]="a.advid = ".I('get.advid');
		$wheres[]="a.is_duizhang=0";
		//数据权限
		/*
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $wheres[]= $myrule_data;
		*/
		$count=M('settlement_in')->join("a join boss_advertiser b on a.advid=b.id join boss_user c on a.salerid=c.id join boss_product d on a.comid=d.id")->where(implode(' && ', $wheres))->count();
		$this->getpagelist($count);
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		$this->data=M('settlement_in')->field("a.id,d.name as comname,b.name as advername,concat(a.strdate,' - ',a.enddate) as date,e.name as jszt,a.status,c.real_name,a.settlementmoney")->join("a join boss_advertiser b on a.advid=b.id join boss_user c on a.salerid=c.id join boss_product d on a.comid=d.id join boss_data_dic e on a.jsztid=e.id")->where(implode(' && ', $wheres))->order('a.id desc')->limit($str.',10')->select();
		$this->display();
	}
	public function makeemail(){
		header('content-type:text/html;charset=utf-8');
		$fistdata=M('daydata')->join('a join boss_advertiser b on a.adverid=b.id')->where("a.jfid in(".I('get.jfid').") && a.adddate>='".I('get.strtime')."' && a.adddate<='".I('get.endtime')."'")->group('a.adverid')->select();
		if(count($fistdata)>1){
			$str='';
			foreach ($fistdata as $key => $value) {
				$str.=$value['name'].',';
			}
			exit('<script>alert("一次只能与一个广告主对账,你选择的数据包含了以下广告主：'.$str.' 请检查数据");window.close();</script>');
		}
		$id=implode(',',I('post.id'));
		$inputnum=0;
		$data=M('daydata')->field('sum(a.newmoney) as allmoney,min(a.adddate) as strdate,max(a.adddate) as enddate,b.name')->join("a join boss_product b on a.comid=b.id")->where("a.jfid in(".I('get.jfid').") && a.adddate>='".I('get.strtime')."' && a.adddate<='".I('get.endtime')."'")->group('a.comid')->select();
		$tongji=M('daydata')->field("sum(a.newmoney) as allmoney,min(a.adddate) as strtime,max(a.adddate) as endtime,b.real_name,b.mobile,c.name as advname,e.name as ztname,a.adverid")->join("a join boss_user b on a.salerid=b.id join boss_advertiser c on a.adverid=c.id join boss_product d on a.comid=d.id join boss_data_dic e on d.sb_id=e.id")->where("a.jfid in(".I('get.jfid').") && a.adddate>='".I('get.strtime')."' && a.adddate<='".I('get.endtime')."'")->find();
		$email=M('advertiser_contacts')->where("ad_id=".$tongji['adverid'])->find();
		$tongji['email']=$email['email'];
		if($tongji['email']==''){
			echo '<script>alert("此广告主当前业务线没有设置对接人邮箱，请前往设置！");window.location="/Home/Index/main?mainurl=/Home/Advertiser/index"</script>';
			exit();
		}
		$html="<style>
		*{font-size:14px;}
		#emailtb td {

		    border: 1px solid #d8d8d8;

		}
		#emailtb tr{
			border-spacing: 0;
		}
		#emailtb{
			    border-spacing: 0;
		    border-collapse: collapse;
		}
		</style>";
		$html.="<br/>尊敬的客户：<br/><br/>";

		$html.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;以下对账单信息为贵司与我司在".$tongji['strtime'].' - '.$tongji['endtime']."产生的收入对账信息，如本次对账信息无误，请回复确认。如有其他疑问，请及时联系我方。<br/>

<br/>详情请查看对账单：<br/>";
		$html.="<table id='emailtb' >
			<tr>
			<th colspan='4'>收入对账单</th>
			</tr>
			<tr>
			<td colspan='4'>合作伙伴名称：".$tongji['advname']."</td>
			</tr>
			<tr>
			<td colspan='4'>制表人：".$tongji['real_name']."<br/>
			制表时间：".date("Y-m-d")."
			</td>
			</tr>
			<tr>
			<td style='text-align:center;width:25%;'>产品名称</td>
			<td style='text-align:center;width:25%;'>结算时间段</td>
			<td style='text-align:center;width:25%;'>结算金额</td>
			<td style='text-align:center;width:25%;'>备注</td>
			</tr>";
			$alltype=C('option.charging_mode');
		foreach ($data as $key => $value) {
			$html.="<tr>
				<td style='text-align:center;width:25%;'><input type='text' name='advname".$inputnum."' value='".$value['name']."'/></td>
				<td style='text-align:center;width:25%;'><input type='text' name='time".$inputnum."' value='".$value['strdate'].' ~ '.$value['enddate']."'/></td>
				<td style='text-align:center;width:25%;'><input type='text' class='itemmoney' name='money".$inputnum."' value='".$value['allmoney']."'/></td>
				<td style='text-align:center;width:25%;'><input name='input".$inputnum."' type='text'/></td>
			</tr>";
			$inputnum++;
		}
		$html.="<tr><td>金额 合计(小写)</td><td colspan='3'><input id='money' type='text' name='allmoney' value='".$tongji['allmoney']."'/></td></tr>";
		$html.="<tr><td>金额 合计(大写)</td><td colspan='3'><input id='dxmoney' type='text' name='allmoneydx' value='".num2rmb($tongji['allmoney'])."'/></td></tr>";
		$html.="<tr>
		<td colspan='2'>
		甲方名称：".$tongji['advname']."<br/>
		(公司签章)<br/>
		联系人：<br/>
		联系电话：
		</td>
		<td colspan='2'>
		乙方名称：".$tongji['ztname']."<br/>
		(公司签章)<br/>
		联系人：<input type='text' name='username' value='".$tongji['real_name']."'/><br/>
		联系电话：<input type='text' name='tel' value='".$tongji['mobile']."'/></td>
		
		</tr>";
		$html.="</table><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;以上数据核对不符，差异说明：<textarea name='remark'></textarea>";
		$html.="<br/><br/>----------------------------------------------------------------------</br>";
		$html.="祝工作愉快！<br/>";
		$html.="<br/><img src='http://apk.yandui.com/201711/ewm.jpg' style='width:180px;border: none;' />";
		$html.="<br/>（我是优效公众号，欢迎扫我，了解更多优效信息！）<br/><br/><br/>";
		$html.="联系人：".$tongji['real_name']."</br>";
		$html.="联系方式：".$tongji['mobile']."</br>";
		$html.="公司名称：".$tongji['ztname']."</br>";
		$html.="公司地址：重庆市渝中区华盛路10号企业天地2号楼19层6-1  邮政编码 400043<br/>";
		$html.="商业贿赂投诉邮箱：sj@yandui.com</br>";
		$html.="服务投诉邮箱：yyjk@yandui,com</br>";
		$html.="信息安全声明：本邮件包含信息归发件人所在组织所有,请接收者注意保密,未经发件人书面许可,不得向任何第三方组织和个人                                 透露本邮件所含信息的全部或部分。</br>";
		$html_base=base64_encode($html);
		echo '<script src="/Public/Home/module/jquery-2.1.1.min.js" type="text/javascript"></script>';
		echo '<script type="text/javascript" src="/Public/OA/js/globle.js"></script>';
		echo '<script>
			$(function(){
				$("#money").change(function(){
					$("#dxmoney").val(DX($("#money").val()));
				})
				$(".itemmoney").change(function(){
					var am=0;
					$(".itemmoney").each(function(){
						am+=1*$(this).val();
					})
					$("#money").val(am);
					$("#money").change();
				})
			});
		</script>
		<div style="width:1200px;margin:0 auto;">
		';
		echo '<form action="/Home/Duizhang/makeemaildo" method="post"><input type="hidden" name="html" value="'.$html_base.'">';
		echo '<input type="hidden" name="setid" value="'.$id.'"/>';
		echo "发送给：<input type='text' name='email' value='".$tongji['email']."'/>";
		echo $html;
		echo "<input type='submit' value='确认发送'/></form></div>";
	}
	public function makeemaildo(){
		$html=base64_decode(I('post.html'));
		preg_match_all("/(<input name='input\d*' type='text'\/>)/", $html, $preg_arr);
		for($i=0;$i<count($preg_arr[1]);$i++){
			echo $i;
			if($i>20)break;
			$html=str_replace("<input name='input".$i."' type='text'/>", I('post.input'.$i), $html);
			$html=str_replace("<input type='text' class='itemmoney' name='money".$i."' type='text'/>", I('post.money'.$i), $html);
			$html=preg_replace("/<input type='text' name='advname".$i."' value='[^']*'\/>/", I('post.advname'.$i), $html);
			$html=preg_replace("/<input type='text' name='time".$i."' value='[^']*'\/>/", I('post.time'.$i), $html);
		}
		$html=str_replace("<textarea name='remark'></textarea>", I('post.remark'), $html);
		$html=preg_replace("/<input type='text' name='username' value='[^']*'\/>/", I('post.username'), $html);
		$html=preg_replace("/<input id='money' type='text' name='allmoney' value='[^']*'\/>/", I('post.allmoney'), $html);
		$html=preg_replace("/<input id='dxmoney' type='text' name='allmoneydx' value='[^']*'\/>/", I('post.allmoneydx'), $html);
		$html=preg_replace("/<input type='text' name='tel' value='[^']*'\/>/", I('post.tel'), $html);
		Vendor("PHPMailer.emailSend");
		C('EMAIL_USERNAME','youxiao@yandui.com');
		C('EMAIL_PWD','Yyyy2017');
		$mail    = new \emailSend();
		$pre_web = "http://".$_SERVER["SERVER_NAME"];
		$config["recpEmailAddress"] = I('post.email');
		$config["subject"]          = "收入对账通知";

		$config["body"]            = $html;
		
		//抄送
		$config["cc_user_list"][0] = "yyjk@yandui.com";

		$re                        = $mail->send($config);
		if($re["status"]==1){
			M('settlement_in')->where("id in (".I('post.setid').")")->save(array('is_duizhang'=>1));
			echo "ok<br>";
		}else{
			echo "fail<br>";
		}

		// print_r($re);
		
	}
	public function ydz(){
		$id=substr(I('post.id'), 0,-1);
		$pid=M('settlement_in')->where("id in ($id)")->save(array('is_duizhang'=>1));
		if($pid)echo json_encode(array('status'=>1));
		else echo json_encode(array('status'=>0));
	}
}