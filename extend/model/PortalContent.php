<?php
//portal_content表
namespace model;
use model\PortalContentExt;
use model\PortalContentType;
use think\Db;

class PortalContent extends BaseModel {

	protected $pk='content_id';
	protected $name="portal_content";
	
	public function Add($cModel, $eModel) {
		Db::startTrans();
		try {
			if ($cModel->save()) {
				$eModel->content_id = $cModel->content_id;
				if ($eModel->Add($eModel)) {
					Db::commit();
					return TRUE;
				} else {
					Db::rollback();
					return FALSE;
				}
			} else {
				Db::rollback();
				return FALSE;
			}
		} catch (\Exception $ex) {
			Db::rollback();
			return FALSE;
		}
	}
	
	//单个删除
	public function DelById($contentid) {
		Db::startTrans();
		try {
			$delNum=$this->where("content_id='$contentid'")->delete();
			if ($delNum > 0) {
				$contentExt = new PortalContentExt();
				if ($contentExt->Del($contentid)) {
					Db::commit();
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;
			}
		} catch (\Exception $e) {
			Db::rollback();
			return FALSE;
		}
	}
	
	//多个删除
	public function BatchDelByContentId($idString) {
		Db::startTrans();
		try {
			$delNum=$this->where("content_id in($idString)")->delete();
			if ($delNum > 0) {
				$contentExt = new PortalContentExt();
				if ($contentExt->BatchDel($idString)) {
					Db::commit();
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;
			}
		} catch (\Exception $e) {
			Db::rollback();
			return FALSE;
		}
	}
	
	
	public function DelByChannelId($channelid) {
		Db::startTrans();
		$flag = FALSE;
		try {
			$all = $this->where("channel_id='$channelid'")->select();
			$contentExt = new PortalContentExt();
			foreach ($all as $k => $v) {
				$delNum=$this->where("content_id='{$v->content_id}' and channel_id='{$v->channel_id}'")->delete();
				if ($delNum > 0) {
					if ($contentExt->Del($contentid)) {
						$flag = TRUE;
					} else {
						return FALSE;
					}
				} else {
					return FALSE;
				}
			}
			if ($flag) {
				Db::commit();
			}
		} catch (\Exception $e) {
			Db::rollback();
			return FALSE;
		}
	}
	
	
	public function GetChannelContent($channelid) {
		return $this->where("channel_id='$channelid'")->select();
	}
	
	public function GetContentOneById($contentid){
		return $this->where("content_id='$contentid'")->find();
	}
	
	public function GetChannelContentById($channelid, $contentid){
		return $this->where("channel_id='$channelid' and content_id='$contentid'")->find();
	}

    public function GetContentByChannelId($channelid, $type = 0, $rows = 0, $currentPage = 0) {
        $table=$this->table;
        $PCExtModel=new PortalContentExt();
        $PCTypeModel=new PortalContentType();
        
        $PortalContentExt=$PCExtModel->tableName();
        $PortalContentType=$PCTypeModel->tableName();
        
        $sql = "SELECT c.content_id, ce.channel_name,e.title,e.short_title,e.author,e.origin,e.origin_url,"
        		. " e.description,e.release_date,e.title_img,e.title_img_width,e.title_img_height,e.content_img,"
        		. " e.content_img_width,e.content_img_height,e.link,e.txt"
        		. " FROM " . $table . " AS c"
        		. " LEFT JOIN " . $PortalContentExt. " AS e ON c.content_id=e.content_id"
        		. " LEFT JOIN " . $PortalContentExt. " AS ce ON ce.channel_id=c.channel_id"
        		. " LEFT JOIN " . $PortalContentType . " AS t ON c.type_id=t.type_id "
        		. " WHERE c.status=2 ";
        if ($channelid != "") {
        	$sql .= " AND c.channel_id=" . $channelid;
        }
        if ($type != 0) {
        	$sql .= " AND c.type_id=" . $type;
        }
        $sql .= " ORDER BY e.release_date DESC ";
        
        $start= $currentPage * $rows;
        $sql .= " limit  $start,$rows";
      
        $list=Db::query($sql);
        return $list;
    }

    public function GetContentById($contentid) {
        $table=$this->table;
        $PCExtModel=new PortalContentExt();
        $PCTypeModel=new PortalContentType();
        
        $PortalContentExt=$PCExtModel->tableName();
        $PortalContentType=$PCTypeModel->tableName();
        
        $sql = "SELECT c.content_id,ce.channel_name,e.title,e.short_title,e.author,e.origin,e.origin_url,"
                . " e.description,e.release_date,e.title_img,e.title_img_width,e.title_img_height,e.content_img,"
                . " e.content_img_width,e.content_img_height,e.link,e.txt"
                . " FROM " . $table . " AS c"
                . " LEFT JOIN " . $PortalContentExt . " AS e ON c.content_id=e.content_id"
                . " LEFT JOIN " . $PortalContentExt. " AS ce ON ce.channel_id=c.channel_id"
                . " LEFT JOIN " . $PortalContentType. " AS t ON c.type_id=t.type_id "
                . " WHERE c.status=2 AND c.content_id=" . $contentid;
       
        $sql .= " ORDER BY c.content_id DESC ";
        $list=Db::query($sql);
        return $list;
        
    }

    public function GetContentByChlIdForOnce($channelid) {
        $table=$this->table;
        $PCExtModel=new PortalContentExt();
        $PCTypeModel=new PortalContentType();
        
        $PortalContentExt=$PCExtModel->tableName();
        $PortalContentType=$PCTypeModel->tableName();
        
		$sql = "SELECT c.content_id, ce.channel_name,e.title,e.short_title,e.author,e.origin,e.origin_url,"
                . " e.description,e.release_date,e.title_img,e.title_img_width,e.title_img_height,e.content_img,"
                . " e.content_img_width,e.content_img_height,e.link,e.txt"
                . " FROM " . $table . " AS c"
                . " LEFT JOIN " . $PortalContentExt. " AS e ON c.content_id=e.content_id"
                . " LEFT JOIN " . $PortalContentExt. " AS ce ON ce.channel_id=c.channel_id"
                . " LEFT JOIN " . $PortalContentType . " AS t ON c.type_id=t.type_id "
                . " WHERE c.status=2 AND c.channel_id=" . $channelid;
        $sql .= " ORDER BY c.content_id DESC ";	
        $list=Db::query($sql);
        return $list;
    }
}
