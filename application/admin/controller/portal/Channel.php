<?php
namespace app\admin\controller\portal;
use app\admin\controller\portal\Jsondata;
use app\admin\controller\Super;
use model\PortalChannel;
use model\PortalChannelExt;
use model\PortalModel;
use model\PortalContent;
use think\Db;

class Channel extends Super {
	
	//栏目列表
    public function index() {
        return $this->fetch("portal/channel/index");
    }

    //显示新增或者修改栏模板
    public function view() {
    	
    	//当前栏目的ID
        $channelid = trim(input("channelid"));
        $option = trim(input("option"));
        
        //上级栏目ID
        $parent=intval(input("parent"));
        
        //获取多级栏目树
        $Jsondata=new Jsondata();
        $data=$Jsondata->getChannels();
        $tree=$data['data'][0];

        $this->assign("tree",$tree);
        
		$PortalChannel=new PortalChannel();
		
		//获取当前栏目主表信息
		if(!empty($channelid)){
			$channel = $PortalChannel->GetChannelByOnce($channelid);
			if (empty($channel)) {
				return array("code" => false, "msg" => lang("channel_not_exists"));
			}
		}
        
        $PortalChannelExt=new PortalChannelExt();
        $PortalModel=new PortalModel();
        $isOption = array("add", "update");
        if (in_array($option, $isOption)) {
			
        	//可选模型
        	$this->assign("models", $PortalModel->GetAllModel());
        	$this->assign("option", $option);
           
            if ($option == "update") {
                $sql = "SELECT c.channel_id,e.channel_name,c.model_id,m.model_name,c.parent_id,c.priority,c.is_display,e.link,e.title,e.keywords,e.description" .
                        " FROM " . $PortalChannel->tableName() . " AS c " .
                        " LEFT JOIN " . $PortalChannelExt->tableName() . " AS e ON e.channel_id=c.channel_id" .
                        " LEFT JOIN " . $PortalModel->tableName() . " AS m ON m.model_id=c.model_id" .
                        " WHERE c.channel_id=" . $channelid;
                
                $channelList=Db::query($sql);
                $pm=$channelList[0];//当前栏目信息

                $this->assign("one", $pm);
            }
            
            $this->assign("parent",$parent);
            return $this->fetch("portal/channel/addchannel");
        } 
        
        return array("code" => false, "msg" =>lang("channel_tag_error"));
        
    }

	//保存栏目
    public function save() {

        $channelid = trim(input("txtChannelno"));
        $parentid = trim(input("txtParentno"));
        $modelid = trim(input("sltModel"));
        $channelname = trim(input("txtChannelName"));
        $priority = trim(input("txtPriority"));
        $link = trim(input("txtLink"));
        $title = trim(input("txtTitle"));
        $keywords = trim(input("txtKeywords"));
        $description = trim(input("txtDescription"));
        $display = trim(input("display"));
        $option = trim(input("option"));
		
        $PortalChannel=new PortalChannel();
        $PortalChannelExt=new PortalChannelExt();
        $PortalModel=new PortalModel();
        
        if (empty($option)) {
            return array("code" => false, "msg" => lang("channel_tag_empty"));
        }
        if (empty($channelname)) {
            return array("code" => false, "msg" => lang("channel_name_empty"));
        }
        if (intval($priority) < 0) {
            return array("code" => false, "msg" => lang("channel_sort_error"));
        }
        $isOption = array("add", "update");
        if (!in_array($option, $isOption)) {
            return array("code" => false, "msg" => lang("channel_tag_error"));
        }
        if ($option == "add") {
            if (empty($modelid)) {
                return array("code" => false, "msg" => lang("channel_model_empty"));
            }
            $pmodel = $PortalModel->GetModelById($modelid);
            if (empty($pmodel)) {
                return array("code" => false, "msg" => lang("channel_model_not_exists"));
            }
            $channel = new PortalChannel();
            $channel->parent_id = is_numeric($parentid) ? $parentid : 0;
            $channel->priority = $priority;
            $channel->is_display = $display;
            $channel->model_id = $modelid;
            $channelExt = new PortalChannelExt();
            $channelExt->channel_name = $channelname;
            $channelExt->link = $link;
            $channelExt->title = $title;
            $channelExt->keywords = $keywords;
            $channelExt->description = $description;
            if ($channel->Add($channel, $channelExt)) {
                return array("code" => true, "msg" => lang("save_data_success"));
            } else {
                return array("code" => false, "msg" => lang("save_data_error"));
            }
        }
        if ($option == "update") {
            if (empty($channelid)) {
                return array("code" => false, "msg" => lang("channel_code_empty"));
            }
            if($channelid==$parentid){
                return array("code" => false, "msg" => lang("channel_pid_same"));
            }

            $channelModel = $PortalChannel->GetChannelByOnce($channelid);
            if (empty($channelModel)) {
                return array("code" => false, "msg" => str_replace('channelid', $channelid, lang("channel_id_not_exists")));
            }
            $channelExtModel = $PortalChannelExt->GetChannelExtByOnce($channelid);
            if (empty($channelExtModel)) {
                return array("code" => false, "msg" => str_replace('channelid', $channelid, lang("channel_id_ext_not_exists")));
            }
            $channel = $channelModel;
            $channel->parent_id = is_numeric($parentid) ? $parentid : 0;
            $channel->priority = $priority;
            $channel->model_id = $modelid;
            $channel->is_display = $display;
            $channelExt = $channelExtModel;
            $channelExt->channel_name = $channelname;
            $channelExt->link = $link;
            $channelExt->title = $title;
            $channelExt->keywords = $keywords;
            $channelExt->description = $description;
            
            if ($channel->Add($channel, $channelExt)) {
                return array("code" => true, "msg" => lang("save_data_success"));
            } else {
                return array("code" => false, "msg" => lang("save_data_error"));
            }
        }
    }

	//更新栏目排序
    public function updatePriority() {
    	
        $channel_id = input("channel_id");
        $priority=is_numeric(input("priority")) ? trim(input("priority")) : 0;
        if (empty($channel_id)) {
            return array("code" => false, "msg" =>lang("channel_empty"));
        }
        
        $PortalChannel=new PortalChannel();
        $PortalChannelExt=new PortalChannelExt();
        //成功或者失败标记
        $flag = FALSE;
      
        $cmodel = $PortalChannel->GetChannelByOnce($channel_id);
        $extModel = $PortalChannelExt->GetChannelExtByOnce($channel_id);

        $cmodel->priority = $priority;
        if ($cmodel->Add($cmodel, $extModel)) {
        	$flag = TRUE;
        } 
        
        if ($flag) {
            return array("code" => true, "msg" => lang("channel_update_success"));
        } else {
            return array("code" => false, "msg" => str_replace('flagAry', $channel_id, lang("channel_update_error")));
        }
    }
    
    //是否显示栏目
    public function updateDisplay() {
    	 
    	$channel_id = input("channel_id");
    	$display=is_numeric(input("is_display")) ? trim(input("is_display")) : 0;
    	if (empty($channel_id)) {
    		return array("code" => false, "msg" =>lang("channel_empty"));
    	}
    
    	$PortalChannel=new PortalChannel();
    	$PortalChannelExt=new PortalChannelExt();
    	//成功或者失败标记
    	$flag = FALSE;
    
    	$cmodel = $PortalChannel->GetChannelByOnce($channel_id);
    	$extModel = $PortalChannelExt->GetChannelExtByOnce($channel_id);
    
    	$cmodel->is_display = $display;
    	if ($cmodel->Add($cmodel, $extModel)) {
    		$flag = TRUE;
    	}
    
    	if ($flag) {
    		return array("code" => true, "msg" => lang("channel_update_success"));
    	} else {
    		return array("code" => false, "msg" => str_replace('flagAry', $channel_id, lang("channel_update_error")));
    	}
    }

	//删除栏目
    public function delete() {
    	
        $channelid = trim(input("channelid"));

        if (empty($channelid)) {
            return array("code" => false, "msg" => lang("channel_del_empty"));
        }
        $channelModel = new PortalChannel();
        $PortalContent=new PortalContent();

        $channel = $channelModel->GetChannelByOnce($channelid);
        if (empty($channel)) {
            return array("code" => false, "msg" => lang("channel_not_exists"));
        }
        
        $childChannel = $channelModel->GetChildChannel($channelid);

        if (!empty($childChannel)&&count($childChannel)>0) {
            return array("code" => false, "msg" => lang("channel_del_son"));
        }

        $channelContent = $PortalContent->GetChannelContent($channelid);
        if (!empty($channelContent)&&count($channelContent)>0) {
            return array("code" => false, "msg" => lang("channel_del_content"));
        }
        if ($channelModel->Del($channelid)) {
            return array("code" => true, "msg" =>str_replace('channelid', $channelid, lang("channel_del_success")));
        } else {
            return array("code" => false, "msg" => str_replace('channelid', $channelid, lang("channel_del_error")));
        }
    }

}
