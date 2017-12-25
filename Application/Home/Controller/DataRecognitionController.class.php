<?php
namespace Home\Controller;
use Common\Service;
use Common\Controller\BaseController;

/**
 * 数据确认率报表
 * Class DataRecognitionController
 * @package Home\Controller
 */
class DataRecognitionController extends BaseController
{

    public $totalPage = 0;

    public function index()
    {

        $where = array();
        $linelist = M('business_line')->field('id,name')->where('status=1')->select();
        $this->assign('linelist', $linelist);
        $list = $this->lists($this, $where);
        $this->assign('list', $list);
        $this->display();
    }

    //获取列表
    public function getList($where, $field)
    {

        $blIds = I('get.bl', '');
        if (!empty($blIds)) {
            $where['a.in_lineid'] = array('in', $blIds);
        }

        $date = I('get.start_date', '');
        $end_date = I('get.end_date', '');
        $date = empty($date) ? date('Y-m') : $date;
        $end_date = empty($end_date) ? date('Y-m') : $end_date;
        $where['_string'] = "DATE_FORMAT(a.adddate,'%Y-%m')>='{$date}' and DATE_FORMAT(a.adddate,'%Y-%m')<='{$end_date}'";

        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.in_lineid','a.out_lineid');
        $arr_name['user']=array('a.in_salerid','a.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where['_string'].= " && $myrule_data";


        $model = M('daydata_inandout');
        $ret = $model
            ->field('left(a.adddate,7) as month,
		    	  f.`name` AS bl_name,
		         sum(if(a.in_status!=0 && a.in_status!=9,a.in_newmoney,0)) AS inmoney,
				  sum(if(a.in_status>1 && a.in_status!=9,a.in_newmoney,0))/sum(if(a.in_status!=0 && a.in_status!=9,a.in_newmoney,0)) as inquerenlv,
				  sum(if(a.in_status in (5,8),a.in_newmoney,0))/sum(if(a.in_status in (1,2,3,4,5,8),a.in_newmoney,0)) as huikuanlv,
				  sum(if(a.out_status!=0 && a.out_status!=9,a.out_newmoney,0)) AS outmoney,
				  sum(if(a.out_status>1 && a.out_status!=9,a.out_newmoney,0))/sum(if(a.out_status!=0 && a.out_status!=9,a.out_newmoney,0)) as outquerenlv,
				  sum(if(a.out_status=4,a.out_newmoney,0))/sum(if(a.out_status in (1,2,3,4),a.out_newmoney,0)) as fukuanlv')
            ->alias('a')
            ->join('
				  JOIN boss_business_line f
				    ON (f.`id`=a.in_lineid or f.`id`=a.out_lineid)')
            ->group('left(a.adddate,7),IFNULL(a.in_lineid,a.out_lineid)')
            ->order('left(a.adddate,7) DESC')
            ->where($where)->page($_GET['p'], C('LIST_ROWS'))->select();
        //echo $model->getLastSql();exit;
        foreach ($ret as $key => $val) {
            if ($val['inmoney']) {
                $ret[$key]['inmoney'] = ($val['inmoney'] / 10000) . '万';
            }
            $ret[$key]['outmoney'] = ($val['outmoney'] / 10000) . '万';
            $ret[$key]['inquerenlv'] = ($val['inquerenlv'] * 100) . '%';
            $ret[$key]['outquerenlv'] = ($val['outquerenlv'] * 100) . '%';
            $ret[$key]['huikuanlv'] = ($val['huikuanlv'] * 100) . '%';
            $ret[$key]['fukuanlv'] = ($val['fukuanlv'] * 100) . '%';
            /*if (empty($blIds)) {
                $ret[$key]['bl_name'] = '全公司';
            }*/
        }
        $ca_ch = $model
            ->field('a.id')
            ->alias('a')
            ->join('
				  JOIN boss_business_line f
				    ON f.`id`=a.in_lineid')
            ->group('left(a.adddate,7),IFNULL(a.in_lineid,a.out_lineid)')
            ->where($where)
            ->buildSql();
        $this->totalPage = $model->table($ca_ch . ' bc')->where()->count();
        //echo $model->getLastSql();exit;
        //列求和
        $itemTotal = $model
            ->field('
				  f.`name` AS bl_name,
		         sum(if(a.in_status!=0 && a.in_status!=9,a.in_newmoney,0)) AS inmoney,
				  sum(if(a.in_status>1 && a.in_status!=9,a.in_newmoney,0))/sum(if(a.in_status!=0 && a.in_status!=9,a.in_newmoney,0)) as inquerenlv,
				  sum(if(a.in_status in (5,8),a.in_newmoney,0))/sum(if(a.in_status in (1,2,3,4,5,8),a.in_newmoney,0)) as huikuanlv,
				  sum(if(a.out_status!=0 && a.out_status!=9,a.out_newmoney,0)) AS outmoney,
				  sum(if(a.out_status>1 && a.out_status!=9,a.out_newmoney,0))/sum(if(a.out_status!=0 && a.out_status!=9,a.out_newmoney,0)) as outquerenlv,
				  sum(if(a.out_status=4,a.out_newmoney,0))/sum(if(a.out_status in (1,2,3,4),a.out_newmoney,0)) as fukuanlv
				  ')
            ->alias('a')
            ->join('
		    		JOIN boss_business_line f
				    ON (f.`id`=a.in_lineid or f.`id`=a.out_lineid)
				  ')
            ->where($where)
            ->select();
        foreach ($itemTotal as $key => $val) {
            if (empty($blIds)) {
                $itemTotal[$key]['bl_name'] = '全公司';
            }
            if ($val['inmoney']) {
                $itemTotal[$key]['inmoney'] = ($val['inmoney'] / 10000) . '万';
            }
            $itemTotal[$key]['outmoney'] = ($val['outmoney'] / 10000) . '万';
            $itemTotal[$key]['inquerenlv'] = ($val['inquerenlv'] * 100) . '%';
            $itemTotal[$key]['outquerenlv'] = ($val['outquerenlv'] * 100) . '%';
            $itemTotal[$key]['huikuanlv'] = ($val['huikuanlv'] * 100) . '%';
            $itemTotal[$key]['fukuanlv'] = ($val['fukuanlv'] * 100) . '%';
        }
        $this->assign('itemTotal', $itemTotal[0]);

        return $ret;

    }

    /**
     * 导出数据
     */
    public function export()
    {

        $where = array();
        C('LIST_ROWS', '');
        $list = $this->lists($this, $where);
        $title = array('month' => '时间', 'bl_name' => '业务线', 'inmoney' => '收入总额', 'inquerenlv' => '收入确认率', 'huikuanlv' => '收入回款率',
            'outmoney' => '成本总额', 'outquerenlv' => '成本确认率', 'fukuanlv' => '成本支付率');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '封禁数据' . date('Y-m-d-H:i:s'));

    }

    function chargingLogo()
    {
        $this->display();
    }

    private function setSession_(){
        ignore_user_abort();//脱离客户端
        set_time_limit(0);//不限时间执行
        session_write_close();//session解锁
    }

    /*未上量计费标识*/
    function  ajax_zt()
    {
        $this->setSession_();
        $row = 10;
        $model = M('charging_logo');
        $start = date("Y-m-d", strtotime("-1week"));
        $end = date("Y-m-d");
        $where = "b.id not in (SELECT a.jfid FROM boss_daydata_out a JOIN (SELECT MAX(id) AS id,cl_id FROM boss_charging_logo_assign WHERE `status`=1 GROUP BY cl_id) b ON a.jfid=b.cl_id AND adddate>='".$start."' AND adddate<'".$end."' GROUP BY a.jfid)";
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('d.bl_id');
        $arr_name['user']=array('d.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where.= " && $myrule_data";

        $ca_ch = $model->field('b.name as cl_name,c.name as bl_name,d.name as pro_name,e.name as adv_name,f.real_name,g.real_name sw_name,h.name sup_name')->join('b join boss_charging_logo_assign a on a.cl_id=b.id and a.status=1 join boss_business_line c on c.id=a.bl_id join boss_product d on d.id=b.prot_id join boss_advertiser e on e.id=b.ad_id  join boss_user f on f.id=d.saler_id join boss_user g on g.id=a.business_uid join boss_supplier h on h.id=a.sup_id')->where($where)->buildSql();
        //$count = $model->where($where)->alias('b')->count();
        $count = $model->table($ca_ch . ' bc')->where()->count();
        $page = new \Think\AjaxPage($count, $row, "J.ajaxChargStatus");
        $show = $page->show();

        $out =$model->field('b.name as cl_name,c.name as bl_name,d.name as pro_name,e.name as adv_name,f.real_name,g.real_name sw_name,h.name sup_name')->join('b join boss_charging_logo_assign a on a.cl_id=b.id and a.status=1 join boss_business_line c on c.id=a.bl_id join boss_product d on d.id=b.prot_id join boss_advertiser e on e.id=b.ad_id  join boss_user f on f.id=d.saler_id join boss_user g on g.id=a.business_uid join boss_supplier h on h.id=a.sup_id')->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
        // echo $model->getLastSql();exit;

        $ret = array(
            'wsl' => $out,
            'page' => $show
        );
        $this->ajaxReturn($ret);
    }

    /**
     * 【导出未上量计费标识(使用中且连续7天无数据产生】
     * @return [type] [description]
     */
    function exportCharLognNot(){
        $exSer = new \Home\Service\ExcelLogicService();
        $result = $exSer->exportCharLogNotSer();
        if($result["msg"]){
            $this->success("暂无数据！");exit;
        }
    }



    /*计费标识状态*/
    function ajax_ch()
    {
        $this->setSession_();
        $row = 10;
        $model = M('charging_logo');

        //数据权限
        $arr_name=array();
        $arr_name['line']=array('e.bl_id');
        $arr_name['user']=array('e.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where= "$myrule_data";

        $ca_ch = $model->field('IFNULL(d.bl_id, e.bl_id) AS bl_id,COUNT(case when d.`status`=1 then 1 else null end ) AS syz,COUNT(case when d.`status`=2 then 1 else null end ) AS wfp,COUNT(case when d.`status`=3 then 1 else null end ) AS yhs')->join('c LEFT JOIN ( SELECT cl_id, `status`, bl_id FROM boss_charging_logo_assign a JOIN ( SELECT MAX(id) AS id FROM boss_charging_logo_assign WHERE `status`>0 and bl_id>0 GROUP BY cl_id ) b ON a.id = b.id ) d ON c.id = d.cl_id JOIN boss_product e ON e.id = c.prot_id')->where($where)->group('IFNULL(d.bl_id, e.bl_id)')->buildSql();
        $count = $model->table($ca_ch . ' bc')->count();
        $page = new \Think\AjaxPage($count, $row, "ch.ajaxCh");
        $show = $page->show();

        $stData = $model->field('IFNULL(d.bl_id, e.bl_id) AS bl_id,COUNT(case when d.`status`=1 then 1 else null end ) AS syz,COUNT(case when d.`status`=2 then 1 else null end ) AS wfp,COUNT(case when d.`status`=3 then 1 else null end ) AS yhs')->join('c LEFT JOIN ( SELECT cl_id, `status`, bl_id FROM boss_charging_logo_assign a JOIN ( SELECT MAX(id) AS id FROM boss_charging_logo_assign WHERE `status`>0 and bl_id>0 GROUP BY cl_id ) b ON a.id = b.id ) d ON c.id = d.cl_id JOIN boss_product e ON e.id = c.prot_id')->where($where)->order("syz desc")->group('IFNULL(d.bl_id, e.bl_id)')->limit($page->firstRow . ',' . $page->listRows)->select();
         // echo $model->getLastSql();exit;
        foreach ($stData as $key => $val) {
            $bl = M('business_line')->field('name')->where("id=" . $val['bl_id'] . "")->find();// and status=1
            $stData[$key]['bl_id'] = $bl['name'];
        }

        $ret = array(
            'zt' => $stData,
            'page' => $show
        );
        $this->ajaxReturn($ret);
    }
    /**
     * 获取总条数
     * @return [type] [description]
     */
    function lazyCharlogCount(){
        $charSer = new \Home\Service\ChargingLogoService();
        $result = $charSer->getTotalCharlogCountSer();
        $this->ajaxReturn($result);
    }
    
    /**
     * 导出计费标识状态
     * @return [type] [description]
     */
    function exportCharLogStatus(){
        $exSer = new \Home\Service\ExcelLogicService();
        $result = $exSer->exportCharLogStatusSer();
        if($result["msg"]){
            $this->success("暂无数据！");exit;
        }
    }

    /*可利用计费标识*/
    function  ajaxKly(){
        $this->setSession_();
        $row = 10;
        $model = M('charging_logo_assign');
        $ca_ch = $model->field('d.`name`,COUNT(case when a.`status`=2 then 1 else null end ) AS wfp,COUNT(case when a.`status`=3 then 1 else null end ) AS yhs')->join('a JOIN (SELECT MAX(id) AS id FROM boss_charging_logo_assign WHERE (`status`=2 OR `status`=3) GROUP BY cl_id) b ON a.id=b.id JOIN boss_charging_logo c ON c.id=a.cl_id JOIN boss_product d ON d.id=c.prot_id')->group('d.id')->buildSql();
        $count = $model->table($ca_ch . ' bc')->where()->count();
        $page = new \Think\AjaxPage($count, $row, "kly.ajaxKly");
        $show = $page->show();
        $klData = $model->field('d.`name`,COUNT(case when a.`status`=2 then 1 else null end ) AS wfp,COUNT(case when a.`status`=3 then 1 else null end ) AS yhs')->join('a JOIN (SELECT MAX(id) AS id FROM boss_charging_logo_assign WHERE (`status`=2 OR `status`=3) GROUP BY cl_id) b ON a.id=b.id JOIN boss_charging_logo c ON c.id=a.cl_id JOIN boss_product d ON d.id=c.prot_id')->group('d.id')->limit($page->firstRow . ',' . $page->listRows)->select();
        // print_r($model->getLastsql());exit;
        foreach ($klData as $key => $val) {
            if ($val['wfp'] or $val['yhs']) {
                $klData[$key]['name'] = $val['name'];
                $klData[$key]['wfp'] = $val['wfp'];
                $klData[$key]['yhs'] = $val['yhs'];
            }
        }
        $ret = array(
            'kly' => $klData,
            'page' => $show
        );

        $this->ajaxReturn($ret);
    }

    /**
     * 可利用计费标识总和
     * @return [type] [description]
     */
    function lazyChartCanUseCount(){
        $charSer = new \Home\Service\ChargingLogoService();
        $result = $charSer->getTotalCharlogCanUseCountSer();
        $this->ajaxReturn($result);
    }

    /**
     * 【导出可利用计费标识】
     * @return [type] [description]
     */
    function exportCharLogCanUse(){
        $exSer = new \Home\Service\ExcelLogicService();
        $result = $exSer->exportCharLogCanUseSer();
        if($result["msg"]){
            $this->success("暂无数据！");exit;
        }
    }

    /**
     * 计费标识分析
     * @return [type] [description]
     */
    function  ajaxHs(){
        $this->setSession_();
        $model = M('charging_logo_assign');
        $row = 10;


        $ca_ch = $model->field("c.`name`,COUNT(case when a.remark like '%产品下线%' then 1 else null end ) AS cpxx,COUNT(case when a.remark like '%渠道未上量%' then 1 else null end ) AS wsl,COUNT(case when a.remark like '%渠道质量差%' then 1 else null end ) AS zlc,COUNT(case when a.remark like '%渠道作弊%' then 1 else null end ) AS zb,COUNT(case when a.remark like '%渠道下线%' then 1 else null end ) AS qdxx")->join("a JOIN (SELECT MAX(id) AS id FROM boss_charging_logo_assign WHERE `status`=3 AND remark!='' GROUP BY cl_id) b ON a.id=b.id JOIN boss_supplier c ON c.id=a.sup_id")->group('c.id')->buildSql();
        $count = $model->table($ca_ch . ' bc')->where()->count();
        $page = new \Think\AjaxPage($count, $row, "hs.ajaxHs");
        $show = $page->show();

        $hsData = $model->field("c.`name`,COUNT(case when a.remark like '%产品下线%' then 1 else null end ) AS cpxx,COUNT(case when a.remark like '%渠道未上量%' then 1 else null end ) AS wsl,COUNT(case when a.remark like '%渠道质量差%' then 1 else null end ) AS zlc,COUNT(case when a.remark like '%渠道作弊%' then 1 else null end ) AS zb,COUNT(case when a.remark like '%渠道下线%' then 1 else null end ) AS qdxx")->join("a JOIN (SELECT MAX(id) AS id FROM boss_charging_logo_assign WHERE `status`=3 AND remark!='' GROUP BY cl_id) b ON a.id=b.id JOIN boss_supplier c ON c.id=a.sup_id")->group('c.id')->limit($page->firstRow . ',' . $page->listRows)->select();
        // print_r($model->getLastsql());exit;
        $ret = array(
            'hs' => $hsData,
            'page' => $show
        );

        $this->ajaxReturn($ret);
    }

    /**
     * 导出计费标识回收分析
     * @return [type] [description]
     */
    function exportCharLogAnalisy(){
        $exSer = new \Home\Service\ExcelLogicService();
        $result = $exSer->exportCharLogAnalisySer();
        if($result["msg"]){
            $this->success("暂无数据！");exit;
        }
    }

    /**
     * 计费标识分析总和
     * @return [type] [description]
     */
    function layzCharlogAnisylesCount(){
        $charSer = new \Home\Service\ChargingLogoService();
        $result = $charSer->getTotalCharlogAnisylesCountSer();
        $this->ajaxReturn($result);
    }

}


