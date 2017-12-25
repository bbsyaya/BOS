<?php

namespace Home\Model;
use Think\Model;
/**
 * 日志管理模型
 */
class LogManagerModel extends Model{
	Protected $autoCheckFields = false;
	public $totalPage = 0;
	public $resource = array(
		1 => 'seaslog',
		2 => 'db'
	);
	//列表
	public function getList($where) {

		if ($where['resource'] == 1) {
			$list = $this->bySeasLog($where);
		} else if ($where['resource'] == 2) {
			$list = $this->byDB($where);
		}

		return $list;
	}


	private function bySeasLog($where) {
		$page = empty($_GET['p']) ? 1 : $_GET['p'];
		$pageSize = C('LIST_ROWS');
		$offset = ceil($page-1) * $pageSize;
		$offset += 1;
		$pageSize -=1;
		$arr = actionlog_analyzer($where['module'], $where['level'], $where['date'], $where['kw'], $offset , $pageSize, SEASLOG_DETAIL_ORDER_DESC);
		//$arr = actionlog_analyzer('cron', SEASLOG_INFO, '*', NULL, 1 , 20, SEASLOG_DETAIL_ORDER_ASC);
		$data = $arr['data'];
		$list = array();
		//格式类似 ： info | 2578 | 1478240979.735 | 2016:11:04 14:29:39 | [[system]]--[[false]]--广告主等级更新完成14
		foreach ($data as $key => $val) {
			$items = explode(' | ', $val);
			$_tmp['id'] = $key;
			$_tmp['module'] = $where['module'];
			$_tmp['level'] = $items[0];
			$_tmp['datetime'] = $items[3];
			$info = $items[4];
			$infoArr = explode('--',$info);
			$_tmp['content'] = $infoArr[2];
			$_tmp['user'] = $infoArr[0];
			$_tmp['keyword'] = $infoArr[1];
			$_tmp['all'] = urldecode($val);
			$list[] = $_tmp;
		}
		$this->totalPage = $arr['total'];
		//没有数据
		if ($this->totalPage == 0) {
			return array();
		}
		return $list;
	}



	private function byDB($where) {

		$list[] = array(
			'id'=>'',
			'module'=>'',
			'level'=>'',
			'datetime'=>'',
			'content'=>'',
			'user'=>'',
		);
		return $list;
	}

}
