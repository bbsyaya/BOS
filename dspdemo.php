<?php
/*
//此文件用于快速测试UTF8编码的文件是不是加了BOM，并可自动移除
//By Bob Shen
$basedir="."; //修改此行为需要检测的目录，点表示当前目录
$auto=1; //是否自动移除发现的BOM信息。1为是，0为否。
//以 下不用改动
if ($dh = opendir($basedir)) {
while (($file = readdir($dh)) !== false) {
if ($file!='.' && $file!='..' && !is_dir($basedir."/".$file)) echo "filename: $file ".checkBOM("$basedir/$file")." <br>";
}
closedir($dh);
}
function checkBOM ($filename) {
global $auto;
$contents=file_get_contents($filename);
$charset[1]=substr($contents, 0, 1); 
$charset[2]=substr($contents, 1, 1); 
$charset[3]=substr($contents, 2, 1); 
if (ord($charset[1])==239 && ord($charset[2])==187 && ord($charset[3])==191) {
if ($auto==1) {
$rest=substr($contents, 3);
rewrite ($filename, $rest);
return ("<font color=red>BOM found, automatically removed.</font>");
} else {
return ("<font color=red>BOM found.</font>");
}
} 
else return ("BOM Not Found.");
}
function rewrite ($filename, $data) {
$filenum=fopen($filename,"w");
flock($filenum,LOCK_EX);
fwrite($filenum,$data);
fclose($filenum);
}
*/
header("Content-type: text/html; charset=utf-8");
$timestamp=time();
echo 'ssssssssssssssssss';


if(empty($_GET['type']))$remote_server='';
elseif($_GET['type']==1)$remote_server='http://devboss3.yandui.com/api/DspData/getSuperListApi';  //接口地址获取供应商列表
elseif($_GET['type']==2)$remote_server='http://devboss3.yandui.com/api/DspData/editSuperFinance';  //接口地址修改供就商财务信息
elseif($_GET['type']==3)$remote_server='http://devboss3.yandui.com/api/DspData/editAdverData';  //接口地址修改广告主信息
elseif($_GET['type']==4)$remote_server='http://devboss3.yandui.com/api/DspData/addOneDayInDataApi';  //接口地址同步收入数据
elseif($_GET['type']==5)$remote_server='http://devboss3.yandui.com/api/DspData/DelDspData';  //接口地址删除数据
elseif($_GET['type']==6)$remote_server='http://devboss3.yandui.com/api/DspData/addOneDayOutDataApi';  //接口地址同步成本数据
elseif($_GET['type']==7)$remote_server='http://devboss3.yandui.com/api/DspData/makeSettlementInDoApi';  //接口地址生成结算单
elseif($_GET['type']==8)$remote_server='http://devboss3.yandui.com/api/DspData/makeSettlementOutDoOkApi';  //接口地址确认结算单
elseif($_GET['type']==9)$remote_server='http://devboss3.yandui.com/api/DspData/getAdvFire';  //接口地址确认结算单
//$remote_server='http://bos3.yandui.com/api/data/getSettlementDetailInfo';  //接口地址核对结算单
echo $remote_server."<br />";
echo 'aaa';
if(!empty($remote_server)){
	$data['sign']=md5('b#a$b%s@v&*'.$timestamp);
	$data['ts']=$timestamp;
	$data['appid']='103';
	if($_GET['type']==2){
		$data['bl_id']=43;
		$data['sp_id']=2403;
		$data['invoice_type']=2;
		$data['object_type']=1;
		$data['payee_name']='不存在的';
		$data['opening_bank']='那啥银行';
		$data['bank_no']=324234;
		$data['financial_tax']=0.004;
	}elseif($_GET['type']==3){
		$data['bl_id']=43;
		$data['sp_id']=2403;
		$data['invoice_type']=2;
		$data['object_type']=1;
		$data['payee_name']='不存在的';
		$data['opening_bank']='那啥银行';
		$data['bank_no']=324234;
		$data['financial_tax']=0.004;
	}else{
		$json['type']=2;
		$json['username']=744;
		$json['businessid']=744;
		$json['jfid']=33172;
		$json['comid']=2701;
		$json['adddate']='2018-07-05';
		$json['price']=1;
		$json['newmoney']=11;
		$json['username']='chenyang';
		$json['remarks']='';
		$json['adverid']=984;
		$json['superid']=744;
		$json['sbid']=1;
		$json['salerid']=744;
		$json['lineid']=1;
		$json['reason']='';
		$dataarr[]=$json;

		$data['datajson']=json_encode($dataarr);
		//申请生成结算单
		$data['jfid']=33172;
		$data['sbid']=1;
		$data['adverid']=984;
		$data['strtime']=$_GET['strtime'];
		$data['endtime']=$_GET['endtime'];
		$data['cl_id']=$_GET['cl_id'];
		$data['superid']=$_GET['superid'];
		$data['trace']='1';
		//$data['bl_id']='1';
		//确认结算单
		$data['bl_id']=2;
		$data['advcontactsid']=$_GET['advcontactsid'];
		$data['id']=$_GET['id'];
		$data['username']=1;
	}

	
	$ch = curl_init();  
	curl_setopt($ch, CURLOPT_URL, $remote_server);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36");  
	$data = curl_exec($ch);  
	print_r($data);
	curl_close($ch);
	$json_data=json_decode($data,true);
	echo "<br />";
	echo var_dump($json_data);
	echo "<br />";
	echo '<a href="/test.php">返回</a>';
}else{
	?>
	<form action="" method="get">
		type:<select name="type"><option>1</option><option>2</option></select><br/>
		strtime:<input type="text" name="strtime"/><br/>
		endtime:<input type="text" name="endtime"/><br/>
		cl_id:<input type="text" name="cl_id"/><br/>
		superid:<input type="text" name="superid"/><br/>
		advcontactsid:<input type="text" name="advcontactsid"/><br/>
		id:<input type="text" name="id"/><br/>
		<input type="submit"/>
	</form>
	<?php
}

?>