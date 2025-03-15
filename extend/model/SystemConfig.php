<?php
//system_config表
namespace model;
use think\Model;
use think\Db;

class SystemConfig extends BaseModel
{
	protected $pk='key';
	protected $name="system_config";

    //返回true or false
	public function addKey($model){
      return  $model->replace()->save();
    }

    //返回所有配置
    public function getAll($where="1=1"){
        return  $this->where($where)->select();
    }
}
