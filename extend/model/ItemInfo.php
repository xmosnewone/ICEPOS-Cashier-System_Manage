<?php
//bd_item_info表
namespace model;
use think\Db;

class ItemInfo extends BaseModel {

	protected $pk='item_no';
	protected $name="bd_item_info";
    
    public function GetSingleItem($itemno = "", $discount = 1) {
        $where="i.item_no='$itemno' and i.display_flag='1' and i.status='1' and i.is_pifa='1' and b.branch_no='000001'";
        $one=Db::table($this->table)
        ->alias('i')
        ->field('i.item_no,i.item_subno,i.item_name,i.item_stock,c.code_name,' .
                'round(i.price,2) as price,i.unit_no,i.item_clsno,i.item_size,s.sp_company as main_supcust,' .
                'round(i.sale_price,2) as sale_price,round(i.price * ' . $discount . ',2) as vip_price1,i.product_area,' .
                'i.modify_date,i.img_src,i.item_size,i.content')
        		->join('bd_base_code c','c.code_id=i.item_brand',"LEFT")
        		->join('sp_infos s','s.sp_no=i.main_supcust',"LEFT")
        		->join('pos_branch_stock b',"i.item_no=b.item_no","LEFT")
        		->join('bd_item_cls a',"i.item_clsno=a.item_clsno","LEFT")
        		->where($where)
        		->find();
        
        return $one;
    }

    public function GetTop6Item($discount = 1) {
    	
        $where="s.display_flag=1 and s.status=1 and s.is_pifa='1' and a.is_pifa='1' and b.branch_no='000001'";
        $list=Db::table($this->table)
        ->alias('s')
        ->field('s.item_no,s.item_name,' .
                'round((s.price * ' . $discount . '),2) as vip_price1,round(s.sale_price,2) as sale_price,' .
                's.item_clsno,s.modify_date,s.img_src')
        		->join('pos_branch_stock b','s.item_no=b.item_no',"RIGHT")
        		->join('bd_item_cls a','s.item_clsno=a.item_clsno',"LEFT")
        		->where($where)
        		->order("rand(s.item_no)")
        		->limit(6)
        		->select();
        
        return $list;
    }


    public function GetNewItemInfoForIndex($discount = 1) {

        $where="s.display_flag='1' and s.status='1' and s.is_pifa='1' and b.branch_no='000001' ";
        $list=Db::table($this->table)
        ->alias('s')
        ->field("s.item_no,s.item_clsno,s.item_name," . 
                "round(s.price * " . $discount . ",2) as vip_price1,round(s.sale_price,2) as sale_price,s.img_src")
        		->join('pos_branch_stock b','s.item_no=b.item_no',"RIGHT")
        		->join('bd_item_cls a','s.item_clsno=a.item_clsno',"LEFT")
        		->where($where)
        		->order("s.build_date desc")
        		->limit(4)
        		->select();
        
        return $list;
    }


    public function GetItemInfoForLimit($limit, $discount = 1) {
        $where="s.display_flag=1 and s.status=1 and s.is_pifa='1' and a.is_pifa='1' and b.branch_no='000001' ";
        $list=Db::table($this->table)
        ->alias('s')
        ->field('s.item_no,s.item_name,round((s.price * ' . $discount . '),2) as vip_price1,' .
                'round(s.sale_price,2) as sale_price,s.item_clsno,s.modify_date,s.img_src')
        		->join('pos_branch_stock b','s.item_no=b.item_no',"RIGHT")
        		->join('bd_item_cls a','s.item_clsno=a.item_clsno',"LEFT")
        		->where($where)
        		->order("rand(s.item_no)")
        		->limit($limit)
        		->select();
        
        return $list;
    }


    public function GetItemInfosByNos($itemnos) {

        $where="display_flag='1' and status='1' and is_pifa='1' and item_no='$itemnos' ";
        $list=Db::table($this->table)
        		->field('item_no,item_clsno,item_name,round(sale_price) as sale_price,img_src')
        		->where($where)
        		->select();
        
        return $list;
        
    }

    public $brand_name;

    public function GetItemPager($item_clsno = "", $item_unit = "", $brand = "", $area = "", $startPrice = "", $endPrice = "", $selectSearch = "", $selectText = "", $discust = 1) {
       
    	$where="s.is_pifa='1'  and s.status='1'  and c.is_pifa='1' and b.branch_no='000001' ";
    	$orderby = "s.modify_date desc";
        
        $paramsArr = array();
        if (!empty($item_clsno) && empty($brand)) {
            $where.=" and s.item_clsno like '$item_clsno'%' and s.display_flag=1 and s.status=1 ";
        }
        if (!empty($item_unit)) {
            $where.=" and s.unit_no like '$item_unit%' ";
        }
        if (!empty($brand)) {
            $where.=" and s.item_brand = '$brand' ";
        }
        if (!empty($area)) {     
            $where.=" and s.product_area like '$area%' ";
        }
        if (!empty($startPrice) && preg_match("/^[0-9]*$/", $startPrice)) {
            $where.=" and round(s.sale_price *" . $discust . ",2) >= $startPrice";
        }
        if (!empty($endPrice) && preg_match("/^[0-9]*$/", $endPrice)) {
            $where.=" and round(s.sale_price *" . $discust . ",2) <= $endPrice";
        }
        if (!empty($selectSearch)) {
            if ($selectSearch == "title") {
                $where.=" and s.item_name like '%$selectText%' ";
            }
            if ($selectSearch == "itemno") {
                $where.=" and s.item_no like '%$selectText%' ";
            }
        }
        
        $list=Db::table($this->table)
        ->alias('s')
        ->field('s.item_no,s.item_subno,s.item_name,a.code_name as brand_name,s.item_clsno,' .
        		's.item_size,round(s.price * ' . $discust . ',2) as vip_price1,  round(s.sale_price,2) as sale_price,s.img_src,' .
        		'ifnull(b.stock_qty,0) as item_stock,s.status')
        		->join('bd_base_code a','a.code_id=s.item_brand',"LEFT")
        		->join('pos_branch_stock b','s.item_no=b.item_no',"RIGHT")
        		->join('bd_item_cls c','s.item_clsno=c.item_clsno',"LEFT")
        		->order($orderby)
        		->where($where)
        		->paginate(10);
        
        //分页渲染
        $page = $list->render();
        
        //统计数量
        $recordCount=Db::table($this->table)
        			->alias('s')
	        		->join('bd_base_code a','a.code_id=s.item_brand',"LEFT")
	        		->join('pos_branch_stock b','s.item_no=b.item_no',"RIGHT")
	        		->join('bd_item_cls c','s.item_clsno=c.item_clsno',"LEFT")
	        		->where($where)
	        		->count();
        
        return array("list" => $list, "count" => $recordCount, "page" => $page, "sort" => $orderby);
   
    }


    public function SearchItemPager($key_word = "", $startPrice = "", $endPrice = "", $selectSearch = "", $partText = "", $discust=1) {
    	
        $where="s.is_pifa='1'  and s.status='1'  and c.is_pifa='1' and b.branch_no='000001' ";
        $orderby = "s.item_no desc";
        
        
        $paramsArr = array();
        if (!empty($key_word)) {
            $where.=" and s.item_name like '%$key_word%' ";
        }
        if (!empty($startPrice) && preg_match("/^[0-9]*$/", $startPrice)) {
            $where.=" and round(s.sale_price *" . $discust . ",2) >= $startPrice";
        }
        if (!empty($endPrice) && preg_match("/^[0-9]*$/", $endPrice)) {
            $where.=" round(s.sale_price * " . $discust . ",2) <= $endPrice";
        }
        if (!empty($selectSearch)) {
            if ($selectSearch == "title") {
                $where.=" and s.item_name like '%$partText%' ";
            }
            if ($selectSearch == "itemno") {
                $where.=" and s.item_no like '%$partText%' ";
            }
        }
        
        $list=Db::table($this->table)
        ->alias('s')
        ->field('s.item_no,s.item_subno,s.item_name,a.code_name as brand_name,' .
                's.item_clsno,s.item_size,round(s.price * ' . $discust . ',2) as vip_price1,' .
                'round(s.sale_price,2) sale_price,' .
                's.img_src,ifnull(b.stock_qty,0) as item_stock,s.status')
        		->join('bd_base_code a','s.item_brand=a.code_id',"LEFT")
        		->join('pos_branch_stock b','s.item_no=b.item_no',"RIGHT")
        		->join('bd_item_cls c','s.item_clsno=c.item_clsno',"LEFT")
        		->order($orderby)
        		->where($where)
        		->paginate(10);
        
        //分页渲染
        $page = $list->render();
        
        //统计数量
        $recordCount=Db::table($this->table)
        ->alias('s')
        ->join('bd_base_code a','a.code_id=s.item_brand',"LEFT")
        ->join('pos_branch_stock b','s.item_no=b.item_no',"RIGHT")
        ->join('bd_item_cls c','s.item_clsno=c.item_clsno',"LEFT")
        ->where($where)
        ->count();
        
        return array("list" => $list, "count" => $recordCount, "page" => $page, "sort" => $orderby);
    }

}
