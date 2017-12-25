<?php
/**合同到期产品
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/23
 * Time: 9:30
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class RiskContractProductController extends BaseController
{
    protected $totalPage = 0;
    /*合同到期产品统计*/
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
            $where .= " and b.data_1 like '%".$sale_name."%'";
        }


        $ml = M('daydata');
        $resData = $ml->field('b.name,c.`name` as adv_name,e.real_name as data_1,sum(if(da.status=1,da.newmoney,0)) as newmoney,sum(if(da.status in (1,2,3,4),da.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney')
        ->join("da
JOIN boss_product b ON da.comid=b.id and b.cooperate_state=1 and b.contract_e_duration<date_format(now(),'%Y-%m-%d')
LEFT JOIN boss_advertiser c on c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=da.jfid and d.adddate=da.adddate
left join boss_user e on e.id=b.saler_id")
        ->where($where)->group('b.id')->order('sum(if(da.status=1,da.newmoney,0)) desc')->page($_GET['p'], C('LIST_ROWS'))->select();

        /*(SELECT a.id,a.name,a.ad_id,b.data_1,b.run_id FROM boss_product a
JOIN boss_flow_data_434 b ON a.contract_num=b.run_id and a.cooperate_state=1 and b.data_111< date_format(now(),'%Y-%m-%d') ) b*/

        $subQuery = $ml->field('b.name,c.`name` as adv_name,e.real_name as data_1,sum(if(da.status=1,da.newmoney,0)) as newmoney,sum(if(da.status in (1,2,3,4),da.newmoney,0)) as whk_money,sum(if(d.status=4,d.newmoney,0)) as out_newmoney')
        ->join("da
JOIN boss_product b ON da.comid=b.id and b.cooperate_state=1 and b.contract_e_duration<date_format(now(),'%Y-%m-%d')
LEFT JOIN boss_advertiser c on c.id=b.ad_id
LEFT JOIN boss_daydata_out d ON d.jfid=da.jfid and d.adddate=da.adddate
left join boss_user e on e.id=b.saler_id")
        ->where($where)->group('b.id')->buildSql();

        $this->totalPage = $ml->table($subQuery.' aa')->where()->count();
        return $resData;
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', ''); //不分页
        $data = $this->lists($this, $where);

        $list=array(array('name','产品名称'),array('adv_name','客户名称'),array('data_1','合同责任人'),array('newmoney','未确认金额'),array('whk_money','未回款金额'),array('out_newmoney','已支付成本'));
        $this->downloadlist($data,$list,'合同到期产品');
    }
}