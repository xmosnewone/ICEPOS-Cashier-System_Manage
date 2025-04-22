<?php
namespace app\admin\controller\portal;
use app\admin\controller\portal\Jsondata;
use app\admin\controller\Super;
use model\PortalChannel;
use model\PortalChannelExt;
use model\PortalContentType;
use model\PortalModel;
use model\PortalContent;
use model\PortalContentExt;
use think\Db;

class Content extends Super {
	
	//列表
    public function index() {
    	$PortalContentType=new PortalContentType();
        $this->assign("type", $PortalContentType->GetAllType());
        return $this->fetch("portal/content/index");
    }

	//查看详情
    public function view() {
    	
        $channelid = input("channelno");
        $option = input("option");
        if (empty($channelid)) {
            return array("code" => false, "msg" => lang("channel_code_empty"));
        }
        if (empty($option)) {
        	return array("code" => false, "msg" => lang("channel_tag_empty"));
        }
        
        //获取多级栏目树
        $Jsondata=new Jsondata();
        $data=$Jsondata->getChannels();
        $tree=$data['data'][0];
        $this->assign("tree",$tree);
        
        $PortalChannelExt=new PortalChannelExt();
        $PortalContentType=new PortalContentType();
        $PortalContent=new PortalContent();
        $PortalContentExt=new PortalContentExt();
        
        $isOption = array("add", "update");
        if (in_array($option, $isOption)) {
        	
        	$this->assign("option", $option);
        	$this->assign("parent",$channelid);
        	$this->assign("type", $PortalContentType->GetAllType());
        	
            if ($option == "add") {
                $this->assign("chlInfo", $PortalChannelExt->GetChannelExtByOnce($channelid));
                
                
               	return $this->fetch("portal/content/view");
            }
            if ($option == "update") {
                $contentid = input("contentno");
                if (empty($contentid)) {
                	return array("code" => false, "msg" => lang("channel_content_empty"));
                }
                $sql = "SELECT ctt.content_id,chle.channel_id,chle.channel_name, ctt.status,"
                        . " ctt.user_id,ctt.type_id,cty.type_name,ctt.top_level,ctt.sort_date,ctte.link,"
                        . " ctt.is_recommend, ctte.title,ctte.short_title,ctte.author,ctte.origin_author,ctte.origin_url,ctte.description,"
                        . " ctte.release_date,ctte.title_img,ctte.title_img_width,ctte.title_img_height,ctte.content_img,"
                        . " ctte.content_img_width,ctte.content_img_height,ctte.txt"
                        . " FROM " . $PortalContent->tableName() . " AS ctt "
                        . " LEFT JOIN " . $PortalChannelExt->tableName() . " AS chle ON chle.channel_id=ctt.channel_id"
                        . " LEFT JOIN " . $PortalContentType->tableName() . " AS cty ON cty.type_id=ctt.type_id"
                        . " LEFT JOIN " . $PortalContentExt->tableName() . " AS ctte ON ctt.content_id=ctte.content_id WHERE ctt.content_id='" . $contentid . "'"
                        . " ORDER BY ctte.release_date DESC";
                $channelList=Db::query($sql);
                $pm = $channelList[0];
                $pm['txt']=str_replace(PHP_EOL, '', $pm['txt']);
                $this->assign("chlInfo", array("channel_id" => $pm["channel_id"], "channel_name" => $pm["channel_name"]));
                $this->assign("one", $pm);
                return $this->fetch("portal/content/view");
            }
        } else {
            return array("code" => false, "msg" => lang("channel_tag_error"));
        }
    }

	//保存文章
    public function save() {
       
        $txtChannelid = trim(input("txtChannelno"));
        $txtTitle = trim(input("txtTitle"));
        $txtLinkUrl = trim(input("txtLinkUrl"));
        $txtShortTitle = trim(input("txtShortTitle"));
        $txtAuthor = trim(input("txtAuthor"));
        $txtDescription = trim(input("txtDescription"));
        $sltType = trim(input("sltType"));
        $chkIsRecommend = trim(input("chkIsRecommend"));
        $chkStatus = trim(input("chkStatus"));
        $txtOrgin = trim(input("txtOrgin"));
        $txtOrginUrl = trim(input("txtOrginUrl"));
        $txtTopLevel = trim(input("txtTopLevel"));
        $txtSortDate = date('Y-m-d H:i:s');
        $txtReleaseDate = trim(input("txtReleaseDate"));
        $txtTitleImg = trim(input("txtTitleImgP"));
        $txtContentImg = trim(input("txtContentImgP"));
        $txtContent = input("txtContent",'',"trim");
        $option = trim(input("option"));
        
        //获取图片文件信息
        $txtTitleImgW = 0;
        $txtTitleImgH = 0;
        if($txtTitleImg!=''&&file_exists(".".$txtTitleImg)){
        	list($width, $height, $type, $attr) = getimagesize(".".$txtTitleImg);
        	$txtTitleImgW =$width;
        	$txtTitleImgH = $height;
        }
        
        //获取图片文件信息
        $txtContentImgW = 0;
        $txtContentImgH = 0;
        if($txtContentImg!=''&&file_exists(".".$txtContentImg)){
        	list($width, $height, $type, $attr) = getimagesize(".".$txtContentImg);
        	$txtContentImgW =$width;
        	$txtContentImgH = $height;
        }

        if (empty($txtChannelid)) {
            return array("code" => false, "msg" => lang("channel_empty"));
        }
        if (empty($txtTitle)) {
            return array("code" => false, "msg" => lang("channel_title_empty"));
        }
        if (empty($txtReleaseDate)) {
            $txtReleaseDate = date("Y-m-d H:m:s");
        }
       
        if ($chkStatus == "1") {
            $chkStatus = 0;
        } else {
            $chkStatus = 2;
        }
        
        $PortalChannel=new PortalChannel();
        $PortalContentType=new PortalContentType();
        $PortalContent=new PortalContent();
        $PortalContentExt=new PortalContentExt();
        $PortalModel=new PortalModel();
        
        $isOption = array("add", "update");
        if (!in_array($option, $isOption)) {
            return array("code" => false, "msg" => lang("channel_tag_error"));
        }

        if (intval($sltType)) {
            $type = $PortalContentType->GetTypeById($sltType);
            if (empty($type)) {
                return array("code" => false, "msg" => lang("channel_cmodel_not_exists"));
            }
        }
        
        $chlModel = $PortalChannel->GetChannelByOnce($txtChannelid);
        if (empty($chlModel)) {
            return array("code" => false, "msg" => lang("channel_not_exists"));
        }
        $chlChildModel = $PortalChannel->GetChildChannel($txtChannelid);
        if (!empty($chlChildModel)&&count($chlChildModel)>0) {
            return array("code" => false, "msg" => lang("channel_last_id"));
        }
        if ($option == "add") {

            $channelModel = $PortalChannel->GetChannelByOnce($txtChannelid);
            if (empty($channelModel)) {
                return array("code" => false, "msg" => str_replace('channelid', $txtChannelid, lang("channel_id_not_exists")));
            }
            $pmodel =$PortalModel->GetModelById($channelModel->model_id);
            if (empty($pmodel)) {
            	return array("code" => false, "msg" =>lang("channel_model_not_exists"));
            }
            if ($pmodel->has_content == "0") {
                $contentModel = $PortalContent->GetChannelContent($txtChannelid);
                if (count($contentModel) >= 1) {
                	return array("code" => false, "msg" =>lang("channel_one_limit"));
                }
            }
            $addCon = new PortalContent();
            $addCon->channel_id = $txtChannelid;
            $addCon->user_id = session("uid");
            $addCon->type_id = $sltType;
            $addCon->top_level = $txtTopLevel;
            $addCon->is_recommend = $chkIsRecommend;
            $addCon->sort_date = $txtSortDate;
            $addCon->status = $chkStatus;
            $addConExt = new PortalContentExt();
            $addConExt->title = $txtTitle;
            $addConExt->short_title = $txtShortTitle;
            $addConExt->author = $txtAuthor;
            $addConExt->origin_author = $txtOrgin;
            $addConExt->origin_url = $txtOrginUrl;
            $addConExt->description = $txtDescription;
            $addConExt->release_date = $txtReleaseDate;
            $addConExt->title_img = $txtTitleImg;
            $addConExt->title_img_width = empty($txtTitleImgW) ? 0 : $txtTitleImgW;
            $addConExt->title_img_height = empty($txtTitleImgH) ? 0 : $txtTitleImgH;
            $addConExt->content_img = $txtContentImg;
            $addConExt->content_img_width = empty($txtContentImgW) ? 0 : $txtContentImgW;
            $addConExt->content_img_height = empty($txtContentImgH) ? 0 : $txtContentImgH;
            $addConExt->link = $txtLinkUrl;
            $addConExt->txt = $txtContent;
            if ($addCon->Add($addCon, $addConExt)) {
            	return array("code" => true, "msg" =>lang("save_data_success"));
            }
        }
        if ($option == "update") {
            $contentid = trim(input("txtContentno"));
            if (empty($contentid)) {
            	return array("code" => false, "msg" =>lang("channel_content_empty"));
            }
            $updCon = $PortalContent->GetContentOneById($contentid);
            if (empty($updCon)) {
                return array("code" => false, "msg" =>lang("empty_record"));
            }
            $updCon->channel_id = $txtChannelid;
            $updCon->user_id = session("uid");
            $updCon->type_id = $sltType;
            $updCon->top_level = $txtTopLevel;
            $updCon->is_recommend = $chkIsRecommend;
            $updCon->sort_date = $txtSortDate;
            $updCon->status = $chkStatus;
            $updConExt = $PortalContentExt->GetContentExtById($contentid);
            if (empty($updConExt)) {
                return array("code" => false, "msg" =>lang("channel_content_ext_not_exists"));
            }
            $updConExt->title = $txtTitle;
            $updConExt->short_title = $txtShortTitle;
            $updConExt->author = $txtAuthor;
            $updConExt->origin_author = $txtOrgin;
            $updConExt->origin_url = $txtOrginUrl;
            $updConExt->description = $txtDescription;
            $updConExt->release_date = $txtReleaseDate;
            $updConExt->title_img = $txtTitleImg;
            $updConExt->title_img_width = empty($txtTitleImgW) ? 0 : $txtTitleImgW;
            $updConExt->title_img_height = empty($txtTitleImgH) ? 0 : $txtTitleImgH;
            $updConExt->content_img = $txtContentImg;
            $updConExt->content_img_width = empty($txtContentImgW) ? 0 : $txtContentImgW;
            $updConExt->content_img_height = empty($txtContentImgH) ? 0 : $txtContentImgH;
            $updConExt->link = $txtLinkUrl;
            $updConExt->txt = $txtContent;
           
            if ($updCon->Add($updCon, $updConExt)) {
               return array("code" => true, "msg" =>lang("save_data_success"));
            }
        }
    }

	//删除文章
    public function delete() {
    	
        $channelid = input("channelid");
        $contentid = input("contentid");

        $PortalChannel=new PortalChannel();
        $PortalContent=new PortalContent();
        
        if (empty($contentid)) {
        	return array("code" => false, "msg" =>lang("channel_content_id_empty"));
        }

        $content = $PortalContent->GetChannelContentById($channelid, $contentid);

        if (empty($content)) {
        	return array("code" => false, "msg" =>lang("channel_content_not_exist"));
        }
        if ($PortalContent->DelById($contentid)) {
        	$status=true;
        	$msg=lang("delete_success");
        } else {
        	$status=false;
        	$msg=lang("delete_error");
        }
        return array("code" => $status, "msg" =>$msg);
    }
    
    //批量删除文章
    public function batchDelete() {
    	 
    	$contentid = input("contentid");
    
    	$PortalChannel=new PortalChannel();
    	$PortalContent=new PortalContent();
    
    	if (empty($contentid)) {
    		return array("code" => false, "msg" =>lang("channel_content_id_empty"));
    	}
    
    	if ($PortalContent->BatchDelByContentId($contentid)) {
    		$status=true;
    		$msg=lang("delete_success");
    	} else {
    		$status=false;
    		$msg=lang("delete_error");
    	}
    	return array("code" => $status, "msg" =>$msg);
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
