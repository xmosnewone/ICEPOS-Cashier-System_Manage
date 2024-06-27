<?php
namespace app\admin\controller;
use app\admin\controller\Super;

use model\News as NewsModel;
use model\NewsType;
/**
 * 新闻文章
 *
 */
class News extends Super {
	
	//获取所有分类
	private function getTypes(){
		$type=  new NewsType();
		$types=$type->order("id asc")->select();
		return $types;
	}
	
	//新闻首页
   public function index() {
	   	//新闻分类
	   	$types=$this->getTypes();
	   	$this->assign("types",$types);
   		return $this->fetch('news/index');
   }

   //json 数据
   public function getList(){
	   	$News = new NewsModel();
	   	$page =input('page') ? intval(input('page')) : 1;
	   	$rows =input('limit') ? intval(input('limit')) : 10;
	   	 
	   	$where="1=1";
	   	
	   	$title=input("title");
	   	if(!empty($title)){
	   		$where.=" and title like '%$title%'";
	   	}
	   	
	   	$type=input("type");
	   	if(!empty($type)){
	   		$where.=" and type='$type'";
	   	}
	   	
	   	$rowCount = $News->where($where)->count();
	   	$offset = ($page - 1) * $rows;
	   	$list = $News->where($where)->limit($offset,$rows)->select()->toArray();
	   	//获取分类名称
	   	$types=$this->getTypes();
	   	$temp=[];
	   	foreach($types as $k=>$value){
	   		$temp[$value['id']]=$value['name'];
	   	}
	   	foreach($list as $k=>$value){
	   		$list[$k]['category']=$temp[$value['type']];
	   	}
	   	return listJson(0,lang("success_data"),$rowCount, $list);
   }
   
   	//添加/编辑文章
   public function addNews(){
   		//新闻分类
   		$types=$this->getTypes();
   		$this->assign("types",$types);
   		//当前文章
	   	$id=input("id");
	   	if($id){
	   		$one=NewsModel::get($id);
	   		$this->assign("one",$one);
	   	}
   		return $this->fetch('news/view');
   }

   //保存文章
   public function save(){
	   	$title = input('title');
	   	$type = input("type");
	   	$username = input('username');
	   	$headimg = input('headimg');
	   	$headimg_small = input('headimg_small');
	   	$content = input('content','',"");
	   	$id=input("id");
	   	$error="";
	   	if (empty($title)) {
	   		$error = lang("news_title_empty");
	   	}
	   	if (empty($username)) {
	   		$error = lang("news_publisher_empty");
	   	}
	   	if (empty($content)) {
	   		$error = lang("news_content_empty");
	   	}
	   	if($type=='2'&&empty($headimg)){
	   		$error = lang("news_wechat_big");
	   	}
	   	if($type=='2'&&empty($headimg_small)){
	   		$error = lang("news_wechat_small");
	   	}
	   	
	   	if($error != ''){
	   		return ['code'=>false,'msg'=>$error];
	   	}
	   	
   		$new = new NewsModel();
   		$content = array(
   				'title' => $title,
   				'type' => $type,
   				'username' => $username,
   				'content' => $content,
   				'time' => date("Y-m-d H:i:s", $this->_G['time']),
   		);
   		
   		$content['headimg']=$headimg;
   		$content['headimg_small']=$headimg_small;
   		
   		
   		//保存
   		if(empty($id)){
   			$ok=$new->save($content);
   		}else{
   			$content['lastedit_time']=date("Y-m-d H:i:s", $this->_G['time']);
   			$ok=$new->save($content,['id'=>$id]);
   		}
   		if($ok){
   			$code=true;
   			$msg=lang("save_success");
   		
   		}else{
   			$code=false;
   			$msg=lang("save_error");
   		}
   		return ['code'=>$code,'msg'=>$msg];
	   	
   }
   
	//删除文章
   public function del() {
      
      $id = input('id');
      $code=false;
      if ($id) {
	       $rs = NewsModel::get($id);
	       $ok=$rs->delete();
	       if($ok){
	       	$code=true;
	       	$msg=lang("delete_success");
	       }else{
	       	$msg=lang("delete_error");
	       }
      }
      return ['code'=>$code,'msg'=>$msg];
   }
   
	//批量删除文章
   public function batchDelete() {
	   
	   	$id = input('id');
	   	$code=false;
	   	if ($id) {
	   		$model =new NewsModel();
	   		$ok=$model->where("id in ($id)")->delete();
	   		if($ok){
	   			$code=true;
	   			$msg=lang("delete_success");
	   		}else{
	   			$msg=lang("delete_error");
	   		}
	   	}
	   	return ['code'=>$code,'msg'=>$msg];
   }

	//上传图片
    public function upload() {
        //上传图片
    	$result=$this->uploadImage("file",[]);
    	//自动压缩图片
    	$this->compressImage($result['path']);
    	$result['path']=substr($result['path'], 1);
    	return array("code" => '0', "msg" =>'success','data'=>$result);
    }
}
