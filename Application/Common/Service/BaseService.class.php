<?php
/**
* service 父类
*/
namespace Common\Service;
class BaseService
{
	/**
	 * 写入日志
	 * @param  [type] $data [description]
	 * @param  string $url  [description]
	 * @return [type]       [description]
	 */
	function writeLogs($data,$file_name="",$type=""){
		$contents = "";
		$lastBr   = $type=="html"?"<br/>":"";
		foreach ($data as $k => $v) {
			$contents .=$v.$lastBr;
		}
		$file_url = "./upload/log";
		switch ($type) {
			case 'html':
				$html = '<!doctype html>
				<html lang="zh">
				<head>
				<meta charset="UTF-8">
				<title>Document</title>
				</head>
				<body>'.$contents.'</body>
				</html>';
				break;
			default:
				$html = $contents;
				break;
		}
		if(!is_dir($file_url)) mkdir($file_url,0777,true);
		file_put_contents($file_url.$file_name,$html);
		return $url = $file_url.$file_name;
	}
}
?>