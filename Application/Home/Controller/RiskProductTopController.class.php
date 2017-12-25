<?php
/**测试产品金额TOP10
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/23
 * Time: 16:47
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class RiskProductTopController extends BaseController
{
    protected $totalPage = 0;
    /*测试产品TOP10明细*/
    public function index()
    {

        $where = array();
        $list = $this->lists($this, $where);

        //$this->assign('op_order_test_type', C('OPTION.order_test_type'));
        $this->assign('list', $list);
        $this->display();

    }

    public function getList($where, $field) {
        $where = '1=1';
        $pro_name = I('get.pro_name');
        if($pro_name){
            $where .= " and b.name like '%".$pro_name."%'";
        }
        $adv_name = I('get.adv_name');
        if($adv_name){
            $where .= " and c.name like '%".$adv_name."%'";
        }
        $sale_name = I('get.sale_name');
        if($sale_name){
            $where .= " and e.real_name like '%".$sale_name."%'";
        }
        $ml = M('daydata');

        //汇总
        $sumData = $ml->field('b.`name`,c.`name` as adv_name,e.real_name,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney,sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) as newmoney')
        ->join("a
JOIN boss_product b ON a.comid=b.id AND b.cooperate_state=2
LEFT JOIN boss_user e ON e.id=b.saler_id
LEFT JOIN boss_advertiser c ON c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=a.jfid and d.adddate=a.adddate")
        ->where($where)->find();
        $this->assign('sumData',$sumData);

        $resData = $ml->field('b.`name`,c.`name` as adv_name,e.real_name,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney,sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) as newmoney')
            ->join("a
JOIN boss_product b ON a.comid=b.id AND b.cooperate_state=2
LEFT JOIN boss_user e ON e.id=b.saler_id
LEFT JOIN boss_advertiser c ON c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=a.jfid and d.adddate=a.adddate")
            ->where($where)->group('b.id')->order('sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) desc')->page($_GET['p'], C('LIST_ROWS'))->select();

        $subQuery = $ml->field('b.`name`,c.`name` as adv_name,e.real_name,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney,sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) as newmoney')
            ->join("a
JOIN boss_product b ON a.comid=b.id AND b.cooperate_state=2
LEFT JOIN boss_user e ON e.id=b.saler_id
LEFT JOIN boss_advertiser c ON c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=a.jfid and d.adddate=a.adddate")
            ->where($where)->group('b.id')->buildSql();
        $this->totalPage = $ml->table($subQuery.' aa')->where()->count();

        return $resData;
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', ''); //不分页
        $data = $this->lists($this, $where);

        $list=array(array('name','产品名称'),array('adv_name','客户名称'),array('real_name','合同责任人'),array('newmoney','总金额'),array('whk_money','未回款金额'),array('out_newmoney','已支付成本'));
        $this->downloadlist($data,$list,'测试产品明细');
    }

    public function detail(){
        $this->display();
    }
    /*产品top10*/
    public function AjaxAnalysis(){
        $where = '1=1';
        $pro_name = I('get.pro_name');
        if($pro_name){
            $where .= " and b.name like '%".$pro_name."%'";
        }
        $adv_name = I('get.adv_name');
        if($adv_name){
            $where .= " and c.name like '%".$adv_name."%'";
        }
        $sale_name = I('get.sale_name');
        if($sale_name){
            $where .= " and e.real_name like '%".$sale_name."%'";
        }
        $ml = M('daydata');
        $resData = $ml->field('b.`name`,c.`name` as adv_name,e.real_name,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney,sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) as newmoney')
            ->join("a
JOIN boss_product b ON a.comid=b.id AND b.cooperate_state=2
LEFT JOIN boss_user e ON e.id=b.saler_id
LEFT JOIN boss_advertiser c ON c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=a.jfid and d.adddate=a.adddate")
            ->where($where)->group('b.id')->order('sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) desc')->limit(10)->select();
        $this->ajaxReturn($resData);exit;
    }

    public function export_de(){
        $where = array();
        C('LIST_ROWS', ''); //不分页
        $data = $this->ex($where,1);
        $list=array(array('name','产品名称'),array('adv_name','客户名称'),array('real_name','合同责任人'),array('newmoney','总金额'),array('whk_money','未回款金额'),array('out_newmoney','已支付成本'));
        $this->downloadlist($data,$list,'测试产品top10');
    }
    public function ex(){
        $where = '1=1';
        $pro_name = I('get.pro_name');
        if($pro_name){
            $where .= " and b.name like '%".$pro_name."%'";
        }
        $adv_name = I('get.adv_name');
        if($adv_name){
            $where .= " and c.name like '%".$adv_name."%'";
        }
        $sale_name = I('get.sale_name');
        if($sale_name){
            $where .= " and e.real_name like '%".$sale_name."%'";
        }
        $ml = M('daydata');
        $resData = $ml->field('b.`name`,c.`name` as adv_name,e.real_name,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney,sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) as newmoney')
            ->join("a
JOIN boss_product b ON a.comid=b.id AND b.cooperate_state=2
LEFT JOIN boss_user e ON e.id=b.saler_id
LEFT JOIN boss_advertiser c ON c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=a.jfid and d.adddate=a.adddate")
            ->where($where)->group('b.id')->order('sum(if(a.status !=0 && a.status !=9,a.newmoney,0)) desc')->limit(10)->select();
        return $resData;
    }
}