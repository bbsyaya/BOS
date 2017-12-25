<?php

namespace Think;


class Csv {

	//导出csv文件
	public function put_csv($list, $title, $file_name) {

		empty($file_name) && $file_name = "CSV" . date("mdHis", time());
		$file_name .= ".csv";

		header('Content-Disposition: attachment; filename='.$file_name);
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');

		$file = fopen('php://output', "a");
		fwrite($file, "\xEF\xBB\xBF"); //添加BOM
		$limit = 1000;
		$calc = 0;

		foreach ($title as $v) {
			$tit[] = $v;
		}

		fputcsv($file, $tit);

		foreach ($list as $v) {

			$calc++;

			if ($limit == $calc) {
				ob_flush();
				flush();
				$calc = 0;

			}

			foreach ($title as $key => $t) {
				$tarr[$key] = $v[$key];
			}

			/*foreach ($v as $t) {
				$tarr[] = $t;

			}*/

			fputcsv($file, $tarr);
			unset($tarr);

		}

		unset($list);
		fclose($file);
		exit();

	}

}