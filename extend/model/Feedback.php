<?php
//as_feedback表
namespace model;
use think\Db;

class Feedback extends BaseModel {
	
	protected $pk='id';
	protected $name="as_feedback";
	
	protected $validateRules;
	protected $validateMessage;
	
    public function rules() {
        $this->validateRules=array(
        		'content'=>'require|max:200',
        		'ip'	=>'max:16'
        );
        $this->validateMessage=array(
        		'content.require'=>'留言内容不能为空',
        		'content.max'=>'留言内容最多可填200个字符',
        		'ip.max'	=>'IP地址最多50位'
        );
    }
	
    public function search($condition=[]) {
    	$list=Db::table($this->table)
    	->where($condition)
    	->select();
    	return $list;
    }
	
    //$feedback是一个数组
    public function Add($feedback) {
        $result = $this->checkModel($feedback);
        if ($result != "ok") {
            return $result;
        } else {
            try {
            	$_DB=Db::table($this->table);
            	$ok=true;
            	if(isset($feedback['id'])){
            		//有ID则表示更新
            		$id=$feedback['id'];
            		unset($feedback['id']);
            		$ok=$_DB->where(array('id'=>$id))->update($feedback);
            	}else{
            		$ok=$_DB->insert($feedback);
            	}
            	
                if ($ok!==false) {
                    return "OK";
                } else {
                    return "FAIL";
                }
            } catch (\Exception $ex) {
                return $ex;
            }
        }
    }


    private function checkModel($feedback) {
        $len = getStrlen($feedback['content']);
        if ($len == 0 || $len > 200) {
            return "反馈内容不能为空并且限制200个字以内";
        }
        return "ok";
    }

}
