<?php
//bd_item_photo表
namespace model;
use think\Db;
class BdItemPhoto extends BaseModel
{
	protected $pk='photo_id';
	protected $name="bd_item_photo";

    public function search($where) {
    	return $list=Db::table($this->table)
    	->where($where)
    	->select();
    }

    public function GetPhotoByItemno($item_no) {
    	
    	$one=Db::table($this->table)
    	->where("item_no='$item_no'")
    	->find();
    	 
    	return $one;
    }


    public function AddPhotos($item_no, $images, $first_pic) {
        $result = 0;
        Db::startTrans();
        try {
        	$itemInfo=M("bd_item_info");
        	$item=$itemInfo->where("item_no='$item_no'")->find();
            
            $updateok=$itemInfo->where("item_no='$item_no'")->update(array("img_src"=>$first_pic));
            if ($updateok!==false) {
                $temp = 1;
            }
            
            M($this->name)->where("item_no='$item_no'")->delete();
         
            if ($temp > 0) {
                $i = 1;
                foreach ($images as $image) {
                    $img = array();
                    $img['item_no'] = $item_no;
                    $img['photo_url'] = $image;
                    $img['photo_order'] = $i;
                    $img['add_time'] = date(DATETIME_FORMAT);
                    if ($image === $first_pic) {
                        $img['photo_type'] = 1;
                    }
                    $i++;
                    if (M($this->name)->insert($img) === FALSE) {
                        $temp = 0;
                        break;
                    }
                }
            }
            if ($temp > 0) {
                $result = 1;
               	Db::commit(); 
            } else {
                $result = 0;
               Db::rollback();
            }
        } catch (\Exception $ex) {
            Db::rollback();
            $result = -2;
        }
        return $result;
    }

}
