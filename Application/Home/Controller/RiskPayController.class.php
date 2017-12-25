<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/12
 * Time: 9:59
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 已付成本未收款总表
 * Class RiskPayController
 * @package Home\Controller
 */
class RiskPayController extends BaseController {

    public function index(){
        $where =array();
        $list = $this->lists($this, $where);
        $this->assign('list', $list);
        $this->display();
    }

    /*列表*/
    public function getList($where, $field){

        $where = 'bd.status in (1,2,3,4) and bdo.status=4';
        $date = I('get.date');
        if($date){
            $where .=" and DATE_FORMAT(bd.adddate,'%Y-%m')='{$date}'";
        }else{
            $where .= " and DATE_FORMAT(bd.adddate,'%Y-%m')>='".date('Y-01')."' and  DATE_FORMAT(bd.adddate,'%Y-%m')<='".date('Y-m')."'";
        }


        //数据权限
        $arr_name=array();
        $arr_name['line']=array('bd.lineid','bdo.lineid');
        $arr_name['user']=array('bd.salerid','bdo.businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where .= " && $myrule_data";


        $model = M('daydata');

        /*求和*/
        $sumData = $model->field('
				  SUM(IFNULL(bdo.newmoney,0)) AS out_money,
				  SUM(if(bd.status=1,bd.newmoney,0)) AS inmoney,
				  SUM(if(bd.status in (1,2,3,4),bd.newmoney,0)) AS whk_money')
            ->alias('bd')->join('
				  RIGHT JOIN `boss_daydata_out` bdo
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate`')
            ->where($where)->select();
        $this->assign('sumData', $sumData);
        $ret = $model
            ->field('
				  DATE_FORMAT(bdo.adddate, \'%Y-%m\') AS dates,
				  SUM(IFNULL(bdo.newmoney,0)) AS out_money,
				  SUM(if(bd.status=1,bd.newmoney,0)) AS inmoney,
				  SUM(if(bd.status in (1,2,3,4),bd.newmoney,0)) AS whk_money')
            ->alias('bd')
            ->join('
				  RIGHT JOIN `boss_daydata_out` bdo
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate`')
            ->where($where)
            ->group("DATE_FORMAT(bdo.adddate, '%Y-%m')")
            ->order("DATE_FORMAT(bdo.adddate, '%Y-%m') desc")
            ->page($_GET['p'], C('LIST_ROWS'))
            ->select();

        $subQuery = $model
            ->field('
				  DATE_FORMAT(bdo.adddate, \'%Y-%m\') AS dates,
				  SUM(IFNULL(bdo.newmoney,0)) AS out_money,
				  SUM(if(bd.status=1,bd.newmoney,0)) AS inmoney,
				  SUM(if(bd.status in (1,2,3,4),bd.newmoney,0)) AS whk_money')
            ->alias('bd')
            ->join('
				  RIGHT JOIN `boss_daydata_out` bdo
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate`')
            ->where($where)
            ->group("DATE_FORMAT(bdo.adddate, '%Y-%m')")
            ->buildSql();
        $this->totalPage = $model->table($subQuery.' aa')->where()->count();
        return $ret;
    }

    /*点击业务线查询*/
    public function AjaxRisk(){
        $lineid = I('get.lineid');
        if($lineid){
            $where = 'bd.status in (1,2,3,4) and bdo.status=4';
            $date = I('get.date');
            if($date){
                $where .=" and DATE_FORMAT(bd.adddate,'%Y-%m')='{$date}'";
            }else{
                $where .= " and DATE_FORMAT(bd.adddate,'%Y-%m')>='".date('Y-01')."' and  DATE_FORMAT(bd.adddate,'%Y-%m')<='".date('Y-m')."'";
            }


            //数据权限
            $arr_name=array();
            $arr_name['line']=array('bd.lineid','bdo.lineid');
            $arr_name['user']=array('bd.salerid','bdo.businessid');
            $ruleser=new Service\RuleService();
            $myrule_data=$ruleser->getmyrule_data($arr_name);
            $where .= " && $myrule_data";


            $where .=" and bd.lineid =".$lineid;
            $model = M('daydata');

            $sumData = $model->field('
				  SUM(IFNULL(bdo.newmoney,0)) AS out_money,
				  SUM(if(bd.status=1,bd.newmoney,0)) AS inmoney,
				  SUM(if(bd.status in (1,2,3,4),bd.newmoney,0)) AS whk_money')
                ->alias('bd')->join('
				  RIGHT JOIN `boss_daydata_out` bdo
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate`')
                ->where($where)->select();

            $ret = $model
                ->field('
				  DATE_FORMAT(bdo.adddate, \'%Y-%m\') AS dates,
				  SUM(IFNULL(bdo.newmoney,0)) AS out_money,
				  SUM(if(bd.status=1,bd.newmoney,0)) AS inmoney,
				  SUM(if(bd.status in (1,2,3,4),bd.newmoney,0)) AS whk_money')
                ->alias('bd')
                ->join('
				  RIGHT JOIN `boss_daydata_out` bdo
				    ON bd.`jfid`=bdo.`jfid` && bdo.adddate = bd.`adddate`')
                ->where($where)
                ->group("DATE_FORMAT(bdo.adddate, '%Y-%m')")
                ->order("DATE_FORMAT(bdo.adddate, '%Y-%m') desc")
                ->page($_GET['p'], C('LIST_ROWS'))
                ->select();

            $this->ajaxReturn(array('sumData'=>$sumData,'res'=>$ret));exit;
        }
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', '');
        $list = $this->lists($this, $where);
        $title = array('dates' => '日期', 'out_money' => '成本支付金额', 'inmoney' => '未确认收入', 'whk_money' => '未回款收入');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '已付成本未收款'.date('Y-m-d-H:i:s',time()));
    }
}