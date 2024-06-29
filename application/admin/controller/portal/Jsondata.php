<?php
namespace app\admin\controller\portal;
use app\admin\controller\Super;
use app\admin\components\BuildTreeArray;
use model\PortalChannel;
use model\PortalChannelExt;
use model\PortalContent;
use model\PortalContentType;
use model\PortalContentExt;
use think\Db;

class Jsondata extends Super {
	
	//获取栏目分类
    public function getChannels() {
    	
    	$PortalChannel=new PortalChannel();
    	$PortalChannelExt=new PortalChannelExt();
    	
        $sql = "SELECT c.channel_id as id, c.parent_id as pid, e.channel_name as name "
                . " FROM " . $PortalChannel->tableName() . " AS c "
                . " LEFT JOIN " . $PortalChannelExt->tableName() . " AS e ON c.channel_id=e.channel_id"
                . " ORDER BY c.priority asc";
        $data=Db::query($sql);
        
        //重新构造Dtree的数据
        if($data&&is_array($data)){
        	foreach($data as $k=>$value){
        		$value['title']=$value['name'];
        		$value['parentId']=$value['pid'];
        		$data[$k]=$value;
        	}
        }
        
        $bta = new BuildTreeArray($data, 'id', 'pid', 0);
        $tree = $bta->getTreeArray();
        $code=200;
        $message='';
        return treeJson($code, $msg, $tree,lang("channel_top"));
    }

	//获取子栏目分类
    public function getChannelChild() {
    	
    	$PortalChannelExt=new PortalChannelExt();
    	$PortalChannel=new PortalChannel();
    	
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $treenode = input("parent");
        if (empty($treenode) || $treenode == "top") {
            $treenode = "0";
        }
        
        //统计SQL
        $countSql="SELECT count(*) as totalnum FROM " . $PortalChannel->tableName() . " AS c "
                . "LEFT JOIN " . $PortalChannelExt->tableName() . " AS e ON c.channel_id=e.channel_id WHERE c.parent_id='" . $treenode . "'";
        $result = Db::query($countSql);
        $total=$result[0]['totalnum'];
        
       
        $offset = ($page - 1) * $rows;
        $rowIndex = ($page - 1) * $rows + 1;
        
        $sql = "SELECT c.channel_id, c.parent_id, e.channel_name, c.priority,c.is_display FROM " . $PortalChannel->tableName() . " AS c "
        		. "LEFT JOIN " . $PortalChannelExt->tableName() . " AS e ON c.channel_id=e.channel_id WHERE c.parent_id='" . $treenode . "'"
        		. "ORDER BY c.priority asc limit $offset,$rows";
        
        $model=Db::query($sql);
        
        $ary = array();
        foreach ($model as $k => $v) {
            $tt = array();
            $tt = $v;
            $tt["rowIndex"] = $rowIndex;
            array_push($ary, $tt);
            $rowIndex++;
        }
        
        return listJson(0,lang("success_data"),$total, $ary);
    }

	//获取栏目下面内容
    public function getPortalContentByChannel() {
    	
        $channelid = trim(input("channelno"));
        $title = trim(input("title"));
        $startTime = trim(input("startTime"));
        $endTime = trim(input("endTime"));
        $type = trim(input("type"));
        $recommend = trim(input("recommend"));
        $status = trim(input("status"));
        $approve = trim(input("approve"));
        $page = input('page') ? intval(input('page')) : 1;
        $rows = input('rows') ? intval(input('rows')) : 10;

        if (empty($channelid) || $channelid == "top") {
            $channelid = "0";
        }
        
        $PortalContent=new PortalContent();
        $PortalChannel=new PortalChannel();
        $PortalChannelExt=new PortalChannelExt();
        $PortalContentType=new PortalContentType();
        $PortalContentExt=new PortalContentExt();
        
        $countSql="SELECT count(*) as totalnum";
        $listSql="SELECT ctt.content_id,chle.channel_id,chle.channel_name, CASE ctt.status WHEN 0 THEN '草稿' WHEN 1 THEN '正在审核' WHEN 2 THEN '审核通过' WHEN 3 THEN '删除' END status ,"
                . " ctt.user_id,ctt.type_id,cty.type_name,ctt.top_level,"
                . " ctt.is_recommend, ctte.title,ctte.short_title,ctte.author,ctte.origin,ctte.origin_url,ctte.description,"
                . " ctte.release_date,ctte.title_img,ctte.content_img,ctte.link,ctte.txt";
        
        $sql =  " FROM " . $PortalContent->tableName() . " AS ctt "
                . " LEFT JOIN " . $PortalChannel->tableName() . " AS chl ON chl.channel_id=ctt.channel_id"
                . " LEFT JOIN " . $PortalChannelExt->tableName() . " AS chle ON chle.channel_id=ctt.channel_id"
                . " LEFT JOIN " . $PortalContentType->tableName() . " AS cty ON cty.type_id=ctt.type_id"
                . " LEFT JOIN " . $PortalContentExt->tableName() . " AS ctte ON ctt.content_id=ctte.content_id"
                . " WHERE ctt.content_id = ctt.content_id";
        
        if ($channelid != "0") {
            $sql .= " AND (ctt.channel_id='" . $channelid . "' or chl.parent_id='" . $channelid . "')";
        }
        if ($title != "") {
            $sql .= " AND  ctte.title like '%" . $title . "%'";
        }
        if ($startTime != "") {
            $sql .= " AND date(ctte.release_date) >= date('" . $startTime . "')";
        }
        if ($endTime != "") {
            $sql .= " AND date(ctte.release_date) <= date('" . $endTime . "')";
        }
        if ($type != "" && $type != "-1") {
            $sql .= " AND ctt.type_id=" . $type;
        }
        if ($recommend == "true") {
            $sql .= " AND ctt.is_recommend=1";
        }
        if ($status == "true") {
            $sql .= " AND ctt.status=0";
        }
        if ($approve == "1") {
            $sql .= " AND  ctt.status=2";
        }
        if ($approve == "-1") {
            $sql .= " AND  ctt.status=1";
        }
        
        $orderSql = " ORDER BY ctte.release_date DESC";
		
        $countsql=$countSql.$sql;
        $totalQuery=Db::query($countsql);
        $total=$totalQuery[0]['totalnum'];
        
        $offset = ($page - 1) * $rows;
        $rowIndex = ($page - 1) * $rows + 1;
       	$listSql=$listSql.$sql.$orderSql." limit $offset,$rows";
       	$model=Db::query($listSql);
        
        $ary = array();
        foreach ($model as $k => $v) {
            $tt = array();
            $tt = $v;
            $tt["rowIndex"] = $rowIndex;
            array_push($ary, $tt);
            $rowIndex++;
        }
        return listJson(0,lang("success_data"),$total, $ary);
    }

}
