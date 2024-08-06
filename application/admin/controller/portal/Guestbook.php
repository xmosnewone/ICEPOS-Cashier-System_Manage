<?php
namespace app\admin\controller\portal;
use app\admin\controller\Super;

use model\PortalGuestbook;
use model\PortalGuestbookCtg;
use model\PortalGuestbookExt;
use model\PosBranch;
use model\PosFeedback;
use model\PosStatus;
use think\Db;
use think\Controller;

class Guestbook extends Super {
	
	//显示列表页面
    public function index() {
    	$PortalGuestbookCtg=new PortalGuestbookCtg();
        $this->assign("category", $PortalGuestbookCtg->GetGuestbookCtg());
       	return $this->fetch("portal/guestbook/index");
    }
	
    //查看留言
    public function view() {
        
    	$gbno = input("id");

        $PortalGuestbookCtg=new PortalGuestbookCtg();
        $this->assign("category", $PortalGuestbookCtg->GetGuestbookCtg());
        
        if (!empty($gbno)) {
           		$PortalGuestbook=new PortalGuestbook();
            	$guestbook = $PortalGuestbook->GetGuestbookById($gbno);
            	if (empty($guestbook)) {
            		return array("code" => false, "msg" => lang("gb_empty"));
            	}
            	$this->assign("mGuestbook", $guestbook);
            	
            	$PortalGuestbookExt=new PortalGuestbookExt();
            	$this->assign("mGuestbookExt", $PortalGuestbookExt->GetGuestbookExtById($gbno));
        }
            
        return $this->fetch("portal/guestbook/view");
    }
	
    //更新或者添加留言
    public function save() {
        $txtGbno = input("txtGbno");
        $txtTitle = input("txtTitle");
        $category = input("sltCategory");
        $txtContent = input("txtContent");
        $txtReply = input("txtReply");
        $txtEmail = input("txtEmail");
        $txtPhone = input("txtPhone");
        $txtQQ = input("txtQQ");
        $isChecked = input("isChecked")==1?1:0;
        $isRecommend = input("isRecommend")==1?1:0;
        $option = input("option");
        if (empty($txtTitle)) {
            return array("code" =>false, "msg" => lang("gb_content_empty"));
        }
        if (empty($category)) {
            return array("code" => false, "msg" =>  lang("gb_cat_empty"));
        }
       
        if (empty($txtGbno)) {
            $gb = new PortalGuestbook();
            if(!empty($txtReply)){
            	$gb->reply_time = date("Y-m-d H:i:s");
            }
            $gb->guestbook_ctg_id = $category;
            $gb->is_checked = $isChecked;
            $gb->is_recommend = $isRecommend;
            $gb->create_time = date('Y-m-d H:i:s');
            $gbext = new PortalGuestbookExt();
            $gbext->title = $txtTitle;
            $gbext->content = $txtContent;
            $gbext->email = $txtEmail;
            $gbext->phone = $txtPhone;
            $gbext->qq = $txtQQ;
            $gbext->reply = $txtReply;
            if ($gb->Add($gb, $gbext)) {
                return array("code" => true, "msg" => lang("gb_save_success"));
            } else {
                return array("code" =>false, "msg" => lang("gb_save_error"));
            }
        }else{
   
            $PortalGuestbook = new PortalGuestbook();
            $gb = $PortalGuestbook->GetGuestbookById($txtGbno);
            if (empty($gb)) {
                return array("code" => false, "msg" => lang("empty_record"));
            }
         	if(!empty($txtReply)){
            	$gb->reply_time = date("Y-m-d H:i:s");
            }
            $gb->guestbook_ctg_id = $category;
            $gb->is_checked = $isChecked;
            $gb->is_recommend = $isRecommend;
            $PortalGuestbookExt=new PortalGuestbookExt();
            $gbext = $PortalGuestbookExt->GetGuestbookExtById($txtGbno);
            $gbext->title = $txtTitle;
            $gbext->content = $txtContent;
            $gbext->email = $txtEmail;
            $gbext->phone = $txtPhone;
            $gbext->qq = $txtQQ;
            $gbext->reply = $txtReply;
            if ($gb->Add($gb, $gbext)) {
                return array("code" => true, "msg" => lang("gb_save_success"));
            } else {
                return array("code" => false, "msg" =>lang("gb_save_error"));
            }
        }
    }
	
    //删除留言
    public function delete() {
        $gbno = input("id");

        if (empty($gbno)) {
            return array("code" => false, "msg" => lang("gb_id_empty"));
        }
        $PortalGuestbook=new PortalGuestbook();
        $guestbook = $PortalGuestbook->GetGuestbookById($gbno);

        if (empty($guestbook)) {
            return array("code" => false, "msg" =>lang("empty_record"));
        }
        if ($guestbook->Del($gbno)) {
            return array("code" => true, "msg" => lang("gb_delete_ok"));
        } else {
            return array("code" => false, "msg" => lang("gb_delete_error"));
        }
    }
    
    //批量删除留言
    public function batchDelete() {
    	$gbno = input("id");
    
    	if (empty($gbno)) {
    		return array("code" => false, "msg" => lang("gb_id_empty"));
    	}
    	$PortalGuestbook=new PortalGuestbook();
    	
    	$ids=explode(",",$gbno);
    	
    	foreach($ids as $id){
    		$guestbook = $PortalGuestbook->GetGuestbookById($id);
    		if(!empty($guestbook)){
    			$guestbook->Del($id);
    		}
    	}
    	
    	return array("code" => true, "msg" => lang("gb_delete_ok"));
    }

    //列表更新审核或者推荐
    public function updateCheck() {
    
    	$id = input("id");
    	$action=input("action");//审核或推荐
    	$status=is_numeric(input("status")) ? input("status") : 0;
    	if (empty($id)) {
    		return array("code" => false, "msg" =>lang("gb_id_empty"));
    	}
    
    	$PortalGuestbook=new PortalGuestbook();
    	$gb=$PortalGuestbook->GetGuestbookById($id);
    	if($action=='check'){
    		$gb->is_checked=$status;
    	}else{
    		$gb->is_recommend=$status;
    	}
    	
    	$flag=$gb->save();
    	if ($flag) {
    		return array("code" => true, "msg" => lang("save_data_success"));
    	} else {
    		return array("code" => false, "msg" => lang("save_data_error"));
    	}
    }
    
	//留言列表
    public function getGuestbookList() {
        $page = input('page') ? input('page') : 1;
        $rows = input('limit') ? input('limit') : 10;
        
        $PortalGuestbook=new PortalGuestbook();
        $PortalGuestbookExt=new PortalGuestbookExt();
        $PortalGuestbookCtg=new PortalGuestbookCtg();
        
        $countSql='SELECT count(*) as total';
        $fieldSql="SELECT g.guestbook_id, g.create_time, g.is_checked, g.is_recommend,e.title, e.content, e.phone, e.email, e.qq,e.reply, c.ctg_name";
        
        $sql = " FROM " . $PortalGuestbook->tableName() . " AS g "
                . " LEFT JOIN " . $PortalGuestbookExt->tableName() . " AS e ON e.guestbook_id=g.guestbook_id"
                . " LEFT JOIN " . $PortalGuestbookCtg->tableName() . " AS c ON c.guestbook_ctg_id=g.guestbook_ctg_id"
                . " WHERE 1=1";
        
        $title = input("title");
        $category = input("category");
        $approve = input("approve");
        $startTime = input("startTime");
        $endTime = input("endTime");
        if ($startTime != "") {
        	$sql .= " AND date(g.create_time) >= date('" . $startTime . "')";
        }
        if ($endTime != "") {
        	$sql .= " AND date(g.create_time) <=  date('" . $endTime . "')";
        }
        if ($title != "") {
            $sql .= " AND e.title like '%" . $title . "%'";
        }
       
        if ($category != "-1" && $category != "") {
            $sql .= " AND c.guestbook_ctg_id=" . $category;
        }
        if ($approve == "1") {
            $sql .= " AND g.is_checked=1";
        } else if ($approve == "-1") {
            $sql .= " AND g.is_checked=0";
        }
        $sql .= " ORDER BY g.guestbook_id DESC";
        $result=Db::query($countSql.$sql);
        $total=$result[0]['total'];
        
        $offset = ($page - 1) * $rows;
        $rowIndex = ($page - 1) * $rows + 1;
        $model = Db::query($fieldSql.$sql." limit $offset,$rows");

        $ary = array();
        foreach ($model as $k => $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["guestbookId"] = $v["guestbook_id"];
            $tt["title"] = $v["title"];
            $tt["content"] = $v["content"];
            $tt["reply"] = $v["reply"];
            $tt["category"] = $v["ctg_name"];
            $tt["createTime"] = $v["create_time"];
            $tt["checked"] = $v["is_checked"];
            $tt["recommend"] = $v["is_recommend"];
            $tt["phone"] = $v["phone"];
            $tt["email"] = $v["email"];
            $tt["qq"] = $v["qq"];
            array_push($ary, $tt);
            $rowIndex++;
        }
        $result["rows"] = $ary;
       	return listJson(0,'',$total,$ary);
    }

    //显示列表页面
    public function posFeed() {
        return $this->fetch("portal/guestbook/posfeed");
    }

    public function getPosFbList() {
        $page = input('page') ? input('page') : 1;
        $rows = input('limit') ? input('limit') : 10;

        $PortalGuestbook=new PosFeedback();

        $countSql='SELECT count(*) as total';
        $fieldSql="SELECT g.* ";

        $sql = " FROM " . $PortalGuestbook->tableName() . " AS g "
            . " WHERE 1=1";

        $content = input("content");
        $startTime = input("startTime");
        $endTime = input("endTime");
        if ($startTime != "") {
            $sql .= " AND date(g.oper_date) >= date('" . $startTime . "')";
        }
        if ($endTime != "") {
            $sql .= " AND date(g.oper_date) <=  date('" . $endTime . "')";
        }
        if ($content != "") {
            $sql .= " AND g.content like '%" . $content . "%'";
        }
        $sql .= " ORDER BY g.id DESC";
        $result=Db::query($countSql.$sql);
        $total=$result[0]['total'];

        $offset = ($page - 1) * $rows;
        $rowIndex = ($page - 1) * $rows + 1;
        $model = Db::query($fieldSql.$sql." limit $offset,$rows");

        $ary = array();
        foreach ($model as $k => $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["guestbookId"] = $v["id"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["posid"] = $v["posid"];
            $tt["reply"] = $v["reply"];
            $tt["content"] = $v["content"];
            $tt["oper_date"] = $v["oper_date"];
            array_push($ary, $tt);
            $rowIndex++;
        }
        $result["rows"] = $ary;
        return listJson(0,'',$total,$ary);
    }

    //查看留言
    public function posview() {

        $id = input("id");
        if (!empty($id)) {
            $fb=new PosFeedback();
            $feedback = $fb->GetById($id);
            if (empty($feedback)) {
                return array("code" => false, "msg" => lang("gb_empty"));
            }
            $branch_no=$feedback['branch_no'];
            $posid=$feedback['posid'];
            //查询门店和pos机
            $Posbranch=new PosBranch();
            $branch=$Posbranch->where(['branch_no'=>$branch_no])->find();

            $PosStatus=new PosStatus();
            $posInfo=$PosStatus->where(['branch_no'=>$branch_no,'posid'=>$posid])->find();

            $feedback['branch_name']=$branch['branch_name'];
            $feedback['posdesc']=$posInfo['posdesc'];

            $this->assign("feedback", $feedback);
        }

        return $this->fetch("portal/guestbook/posview");
    }

    //回复留言
    public function possave() {
        $reply = input("reply");
        $id=input("id");
        if (!empty($id)&&!empty($reply)) {
            $fb=new PosFeedback();
            $ok=$fb->save([
                'reply'  => $reply,
                'reply_date' => $this->_G['time']
            ],['id' => $id]);
            if ($ok) {
                return array("code" => true, "msg" => lang("rp_save_success"));
            }
        }

        return array("code" =>false, "msg" => lang("rp_save_error"));
    }

    //删除留言
    public function pdelete() {
        $id = input("id");

        if (empty($id)) {
            return array("code" => false, "msg" => lang("gb_id_empty"));
        }
        $posfb=new PosFeedback();
        $ok=$posfb->where(['id'=>$id])->delete();
        if ($ok) {
            return array("code" => true, "msg" => lang("gb_delete_ok"));
        } else {
            return array("code" => false, "msg" => lang("gb_delete_error"));
        }
    }

    //批量删除留言
    public function pbatchDelete() {
        $ids = input("id");

        if (empty($ids)) {
            return array("code" => false, "msg" => lang("gb_id_empty"));
        }
        $posfb=new PosFeedback();
        $ids=explode(",",$ids);

        foreach($ids as $id){
            $posfb->where(['id'=>$id])->delete();
        }

        return array("code" => true, "msg" => lang("gb_delete_ok"));
    }
}
