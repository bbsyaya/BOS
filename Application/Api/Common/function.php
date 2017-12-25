<?php

/**
 * post提交
 * @param $url
 * @param $data  array
 * return mixed
 */
function postRequest($url,$data,$httpHeader=array()) {
	$postStr = '';
	foreach($data as $k => $v){
		$postStr .= $k."=".$v."&";
	}
	$postStr=substr($postStr,0,-1);
	return postUrl($url,$postStr,$httpHeader);
}

/**
 * post提交
 * @param $url
 * @param $data Str
 * return mixed
 */
function postUrl($url, $data, $httpHeader) {

	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch,  CURLOPT_FOLLOWLOCATION, 1);

	if(!empty($httpHeader) && is_array($httpHeader)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
	}

	$data = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	if(curl_errno($ch)){
		return $info;
	}
	return $data;
}

