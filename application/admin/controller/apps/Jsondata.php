<?php
namespace app\admin\controller\apps;
use app\admin\controller\Super;
use model\Item_info;

class Jsondata extends Super {

    public function getIteminfo(){
        $item_no=input("item_no");
        $model=  new Item_info();
        $result=  $model->GetOne($item_no);
        if(count($result)==1){
            $rule=array('item_name','item_subno','unit_no','price');
            $r['code']=true;
            foreach($rule as $k=>$v){
                if(in_array($v,$rule)){
                    $r['data'][$v]=$result[$v];
                }
            }
            return $r;
        }else{
            return ['code'=>false,'msg'=>lang("item_barcode_not_exist")];
        }
    }
    
}
