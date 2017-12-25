<?php
namespace Cron\Controller;
use Think\Controller;

/**
 * Class IndexController
 * @package Cron\Controller
 */
class AdvertiserController extends Controller  {

	//计算广告主等级
	public function adLevel() {
		$model = M('advertiser');
		$p = 1;
		$count = 50;
		do {
			$data = $model->page($p, $count)->field('id, rating_level, ad_type, established_time, register_capital, cooperation_time')->select();
			$flag = empty($data) ? false : true;
			if ($flag) {
				foreach ($data as $val) {
					$level = ad_rating($val['rating_level'],$val['ad_type'],$val['established_time'],$val['register_capital'],$val['cooperation_time']);
					if($model->save(array('id'=>$val['id'],'ad_grade'=>$level)) === false) {
						action_log('cron', SEASLOG_ERROR, 'system', "广告主等级更新错误id={$val['id']};-".$model->getError(),'adLevel'); //行为日志
						return $model->getError();
					}
				}
			}
			unset($data,$level,$val);
			$p++;
		} while($flag);
		action_log('cron', SEASLOG_INFO, 'system', "广告主等级更新完成",'adLevel'); //行为日志
		return true;
	}

}