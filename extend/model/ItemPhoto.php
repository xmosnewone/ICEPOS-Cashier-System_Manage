<?php
//bd_item_photo表
namespace model;
use think\Db;

class ItemPhoto extends BaseModel{
    public $code_name;
    public $item_barcode;
    
    protected $pk='photo_id';
    protected $name="bd_item_photo";

    public function add($photo){
        if ($photo->save()) {
                return "OK";
        } 
        else {
                return "ERROR";
        }
    }
}
