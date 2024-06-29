<?php
namespace app\admin\controller\portal;
use app\admin\controller\Super;

use model\PortalGuestbookCtg;
//留言类别
class Guestbookctg extends Super {
	
	//列表页面	
    public function index() {
       return $this->fetch("portal/guestbook/category");
    }

	//类别编辑
    public function view() {
        $ctgno = input("id");
        
        if (!empty($ctgno)) {
        		$PortalGuestbookCtg=new PortalGuestbookCtg();
                $category = $PortalGuestbookCtg->GetGuestbookCtgById($ctgno);
                if (empty($category)) {
                    return array("code" => false, "msg" => lang("gb_category_empty"));
                }
                $this->assign("one", $category);
        }
       
        return $this->fetch("portal/guestbook/addcategory");
    }

	//保存
    public function save() {
        $ctgno = input("txtCtgno");
        $ctgname = input("txtCtgName");
        $description = input("txtDescription");
        $priority = input("txtPriority");
       
        if (empty($ctgname)) {
            return array("code" => false, "msg" =>lang("gb_categoryname_empty"));
        }
        
        if (!is_numeric($priority)) {
            return array("code" => false, "msg" => lang("gb_priority_error"));
        }

        if (empty($ctgno)) {
            $category = new PortalGuestbookCtg();
            $category->ctg_name = $ctgname;
            $category->priority = $priority;
            $category->description = $description;
            if ($category->Add($category)) {
                return array("code" => true, "msg" => lang("save_data_success"));
            } else {
                return array("code" => false, "msg" => lang("save_data_error"));
            }
        }else{
        	
        	$PortalGuestbookCtg=new PortalGuestbookCtg();
        	$category = $PortalGuestbookCtg->GetGuestbookCtgById($ctgno);
        	if (empty($category)) {
        		return array("code" => false, "msg" => lang("gb_category_empty"));
        	}
        	$category->guestbook_ctg_id = $ctgno;
        	$category->ctg_name = $ctgname;
        	$category->priority = $priority;
        	$category->description = $description;
        	if ($category->Add($category)) {
        		return array("code" => true, "msg" => lang("save_data_success"));
        	} else {
        		return array("code" => false, "msg" =>lang("save_data_error"));
        	}
        }
       
    }

    //删除
    public function delete() {
        $ctgno = trim(input("id"));
        
        if (empty($ctgno)) {
            return array("code" => false, "msg" =>lang("gb_categoryid_empty"));
        }
        $PortalGuestbookCtg=new PortalGuestbookCtg();
        $category = $PortalGuestbookCtg->GetGuestbookCtgById($ctgno);

        if (empty($category)) {
            return array("code" => false, "msg" => lang("gb_category_empty"));
        }
        if ($category->Del($ctgno)) {
            return array("code" => true, "msg" => lang("gb_category_delete_ok"));
        } else {
            return array("code" => false, "msg" => lang("gb_category_delete_error"));
        }
    }

	//分类列表
    public function getGuestbookCtg() {
        $page = input('page') ? input('page') : 1;
        $rows = input('limit') ? input('limit') : 10;
    
        $field= "guestbook_ctg_id,ctg_name,priority,description";
        
        $model = new PortalGuestbookCtg();
        $total = $model->count();
        $offset = ($page - 1) * $rows;
        $list = $model->field($field)->select();
        
        $ary = array();
        $rowIndex = $offset + 1;
        foreach ($list as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $rowIndex++;
            $tt["categoryId"] = $v["guestbook_ctg_id"];
            $tt["categoryName"] = $v["ctg_name"];
            $tt["priority"] = $v["priority"];
            $tt["description"] = $v["description"];
            array_push($ary, $tt);
        }
        return listJson(0,'',$total,$ary);
    }

}
