<?php
namespace app\api\controller;
use think\Db;
use think\facade\Request;
use think\Controller;
use model\PosOperator;
use model\Supcust;
use model\PosStatus;
use model\PortalAd;
use model\PortalAdAttr;
use model\Item_cls;
use model\Item_info;
use model\ItemBarCode;
use model\BaseCodeType;
use model\BdItemCombsplit;
use model\BaseCode;
use model\PosBranchStock;
use model\PaymentInfo;
use model\PosPlanRule;
use model\PosFunction;
use model\PcBranchPrice;
use model\PmSheetMaster;
use model\PmSheetDetail;
use model\SysSheetNo;
use model\PosPayFlow;
use model\PosSaleFlow;
use model\PosFeedback;
use model\PosAccount;
use model\ImSheetMaster;
use model\ImSheetDetail;
use model\PosViplist;
use model\PosPay;
use model\PosBranch;
use model\BaseModel;
/**
 * POS机交互接口
 * @author xmos
 */
class Api extends Super {

	// 获取经销商
	public function getSup() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$Supcust = new Supcust ();
				$res = $Supcust->GetModelsForPos ();
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取供应商信息(Getsup)");
				write_log ( "访问方法:获取供应商商信息(Getsup)错误,错误信息:" . $ex.$message, "API/api/getSup" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 验证POS终端
	public function validPos() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["hostdisk"] ) || empty ( $_POST ["hostmac"] ) || empty ( $_POST ["branch_no"] ) || empty ( $_POST ["posid"] ) || empty ( $_POST ["hostname"] )) {
					$res = - 10;
				} else {
					$PosStatus = new PosStatus ();
					$res = $PosStatus->ValiedatePosForPos ( $_POST ["hostdisk"], $_POST ["hostmac"], $_POST ["branch_no"], $_POST ["posid"], $_POST ["hostname"] );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:POS验证(Validpos)");
				if (! empty ( $_POST ["pos_id"] )) {
					$message .= ",POS机编号:" . $_POST ["pos_id"];
				}
				if (! empty ( $_POST ["hostdisk"] )) {
					$message .= ",硬盘码:" . $_POST ["hostdisk"];
				}
				if (! empty ( $_POST ["hostmac"] )) {
					$message .= ",MAC地址:" . $_POST ["hostmac"];
				}
				if (! empty ( $_POST ["hostname"] )) {
					$message .= ",机器名称:" . $_POST ["hostname"];
				}
				write_log ( "访问方法:POS验证(Validpos)错误,错误信息:" . $ex.$message, "API/api/validPos" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	public function getAdurl() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["branch_no"] )) {
					$res = - 10;
				} else {
					$PortalAd = new PortalAd ();
					$ad = $PortalAd->GetAdForPos ( $_POST ["branch_no"], $_POST ["posid"] );
					
					// 没有专门的广告，则查找通用广告
					//if (empty ( $ad ['ad_id'] )) {
						//$res = $this->api_server . "/static/images/public/activity.jpg";
					//} else {
						$res = url ( 'api/api/ShowAd', array (
								'branch_no' => $_POST ["branch_no"],
								'ad_id' => $ad ['ad_id'] 
						), false, true );
					//}
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取门店广告推送图片地址(Getadurl)");
				write_log ( "访问方法:获取门店广告推送图片地址(Getadurl)错误,错误信息:" . $ex.$message, "API/api/getAdurl" );
				$res = "-2";
			}
		}
		
		echo $res;
		exit();
	}
	
	// 显示公共的广告
	public function showAd() {
		$branch_no = $_GET ['branch_no'];
		//$ad_id = intval ( $_GET ['ad_id'] );
		$PortalAd = new PortalAd ();
		$ad = $PortalAd->GetAdForPos ( $branch_no );
		$code = '';
		if ($ad ['category'] == 'image' || $ad ['category'] == 'text') {
			$PortalAdAttr = new PortalAdAttr ();
			$sql = "select * from " . $PortalAdAttr->tableName () . "  where ad_id='{$ad['ad_id']}' order by pid asc ";
			$model = Db::query ( $sql );
			
			if ($ad ['category'] == 'image') {

				foreach ( $model as $key => $value ) {
					if ($value ['attr_key'] == 'attr_image_url') {//图片路径
						$src = $value ['attr_value'];
                        $code .= '<div class="swiper-slide"><img src="' . $src . '" title=""' .'></div>';
					}
					continue;
				}
			} else {
				$title = $link = $color = $font = $target = "";
				
				foreach ( $model as $key => $value ) {
					if ($value ['attr_key'] == 'attr_text_title') {
						$title = $value ['attr_value'];
					}
					if ($value ['attr_key'] == 'attr_text_link') {
						$link = $value ['attr_value'];
					}
					if ($value ['attr_key'] == 'attr_text_color') {
						$color = $value ['attr_value'];
					}
					if ($value ['attr_key'] == 'attr_text_font') {
						$font = $value ['attr_value'];
					}
					if ($value ['attr_key'] == 'attr_text_target') {
						$target = $value ['attr_value'];
					}
				}
				$code = '<a href="' . $link . '" target="' . $target . '" style="text-decoration:none;color:' . $color . ';font-size:' . $font . 'px"><p>' . $title . '</p></a>';
			}
		} else if ($ad ['category'] == 'code') {
			$code = $ad ['ad_code'];
		}else{
            $img=$this->api_server . "/static/images/public/activity.jpg";
            $code = '<a href="' . '" target="' . '"><img src="' . $img . '" title="' . '" ></a>';

        }

		$this->assign ( "code", $code );
		// 显示广告网页
		return $this->fetch ( "index/adv" );
	}

    // 显示广告图片
    public function adImage() {
        $branch_no = $_GET ['branch_no'];
        $PortalAd = new PortalAd ();
        $ad = $PortalAd->GetAdForPos ( $branch_no );
        $code = '';
        if ($ad ['category'] == 'image') {
            $PortalAdAttr = new PortalAdAttr ();
            $sql = "select * from " . $PortalAdAttr->tableName () . "  where ad_id='{$ad['ad_id']}' ";
            $model = Db::query ( $sql );

            if ($ad ['category'] == 'image') {
                $src = $width = $height = $link = $title = $target = "";

                foreach ( $model as $key => $value ) {
                    if ($value ['attr_key'] == 'attr_image_url') {
                        $src = $value ['attr_value'];
                    }
                }
                $domain=Request::domain();
                $code=$domain.$src;
            }
        }
        return $this->ajaxReturn($code);
    }
	
	// 返回门店信息
	public function getBrninfo() {

		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$PosBranch = new PosBranch ();
				$res = $PosBranch->GetAllBranch ();
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取门店信息(Getbrninfo)");
				write_log ( "访问方法:获取门店信息(Getbrninfo)错误,错误信息:" . $ex.$message, "API/api/getBrninfo" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}

    // 返回单个门店信息
    public function getOneBrninfo() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $branch_no=$_POST['branch_no'];
                $PosBranch = new PosBranch ();
                $res = $PosBranch->getone ($branch_no);
                if($res==false){
                    $res='-5';
                }else{
                    $res['wechat_pay_qrcode']=$res['wechat_pay_qrcode']!=''?$this->api_server.$res['wechat_pay_qrcode']:'';
                    $res['alipay_pay_qrcode']=$res['alipay_pay_qrcode']!=''?$this->api_server.$res['alipay_pay_qrcode']:'';
                    $unsetField=['wechat_appid','wechat_secret','wechat_merchantid','wechat_paykey',
                        'alipay_appid','alipay_public_key','alipay_private_key'];
                    //不返回敏感资料
                    foreach($unsetField as $field){
                        unset($res[$field]);
                    }
                }

            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取单个门店信息(getOneBrninfo)");
                write_log ( "访问方法:获取单个门店信息(getOneBrninfo)错误,错误信息:" . $ex.$message, "API/api/getOneBrninfo" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }

    //上传门店二维码图片
    public function uploadBrnImg() {
        $token=config("access_token");
        $post_token=$_POST['access_token'];
        if ($token !=$post_token) {
            return $this->ajaxReturn(['status'=>'-1']);
        }
        $fileName=$_POST['filename'];
        $result=$this->uploadFile($fileName);
        //返回上传图片成功与否数组
        if(!$result['status']){
            return $this->ajaxReturn(['status'=>'2']);//上传失败
        }
        $pics=$this->api_server.substr($result['path'],1);
        return $this->ajaxReturn(['status'=>200,'url'=>$pics]);//返回图片路径
    }

    /**
     * 文件上传
     * @access string $input 文件名称
     */
    public function uploadFile($input)
    {
        if (!empty($_FILES[$input])) {
            $uploads=config("uploads_path");
            $file = request()->file($input);
            $info = $file->move($uploads);
            $result=[];
            if($info){
                // 成功上传后 获取上传信息
                $result['status']=1;
                $result['extension']=$info->getExtension();
                //完整上传路径
                $result['path']=$uploads."/".str_replace("\\", "/", $info->getSaveName());
                //上传后的名称
                $result['filename']=$info->getFilename();
            }else{
                // 上传失败获取错误信息
                $result['status']=0;
                $result['msg']=$file->getError();
            }
            return $result;
        }
    }

    // 保存门店的信息
    public function saveBrninfo() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $branch_no=$_POST['branch_no'];
                $PosBranch = new PosBranch ();
                $model = $PosBranch->getoneWhere (['branch_no'=>$branch_no]);
                if($model==false){
                    $res='-5';
                    return $this->ajaxReturn($res);
                }
                //保存门店信息
                $model->wechat_pay_qrcode=str_replace($this->api_server,'',$_POST['wechat_pay_qrcode']);
                $model->alipay_pay_qrcode=str_replace($this->api_server,'',$_POST['alipay_pay_qrcode']);
                $ok=$PosBranch->Add($model);
                if($ok){
                    $res='1';
                }else{
                    $res='0';
                }

            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:保存门店信息(saveBrninfo)");
                write_log ( "访问方法:保存门店信息(saveBrninfo)错误,错误信息:" . $ex.$message, "API/api/saveBrninfo" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }

	
	// 返回商品分类
	public function getItemcls() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				
				$Item_cls = new Item_cls ();
				$res = $Item_cls->GetUpdateDataForPos ( $rid, $updatetime );

				//记录一次同步信息
                $this->addSyncRecord($_POST ["posid"],$_POST ["branch_no"]);
				
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取分类信息(Getitemcls)");
				write_log ( "访问方法:获取分类信息(Getitemcls)错误,错误信息:" . $ex.$message, "API/api/getItemcls" );
				$res = "-2";
			}
		}
		return $this->ajaxReturn($res);
	}

    // 返回商品分类
    public function getItemclsByno() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $clsno=trim($_POST['clsno']);
                $Item_cls = new Item_cls ();
                $res = $Item_cls->GetItemClsByClsno ($clsno);

            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取分类信息(getItemclsByno)");
                write_log ( "访问方法:获取分类信息(getItemclsByno)错误,错误信息:" . $ex.$message, "API/api/getItemclsByno" );
                $res = "-2";
            }
        }
        return $this->ajaxReturn($res);
    }
	
	// 返回商品条码
	public function getBarcode() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				$ItemBarCode = new ItemBarCode ();
				$res = $ItemBarCode->GetUpdateDataForPos ( $rid, $updatetime );
				
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取多条码信息(Getbarcode)");
				write_log ( "访问方法:获取多条码信息(Getbarcode)错误,错误信息:" . $ex.$message, "API/api/getBarcode" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}

    // 返回商品信息
    public function getOneItem() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $item_no=$_POST['item_no'];
                $Item_info = new Item_info ();
                $res = $Item_info->GetOne ($item_no);
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取商品信息(getOneItem)");
                write_log ( "访问方法:获取商品信息(getOneItem)错误,错误信息:" . $ex.$message, "API/api/getOneItem" );
                $res = "-2";
            }
        }
        return $this->ajaxReturn($res);
    }

	// 返回商品信息
	public function getIteminfo() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				$Item_info = new Item_info ();
				$res = $Item_info->GetUpdateDataForPos ( $_POST ["branch_no"], $rid, $updatetime );
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取商品信息(Getiteminfo)");
				write_log ( "访问方法:获取商品信息(Getiteminfo)错误,错误信息:" . $ex.$message, "API/api/getIteminfo" );
				$res = "-2";
			}
		}
		return $this->ajaxReturn($res);
	}

	//批量返回所有符合条件的商品数据--用于分批下载商品信息@2024-01-30和getIteminfo区别在于不用查询breakpoint表
    //@params GET/POST start 起始数   limit 返回条数 clsno 商品分类编号
    // @return total 所有符合条件的个数  list 返回商品数组
    public function getItemsData(){
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $Item_info = new Item_info ();
                $start=$_POST ["start"]?intval($_POST ["start"]):0;
                $limit=$_POST ["limit"]?intval($_POST ["limit"]):0;
                $clsno=$_POST ["clsno"]?$_POST ["clsno"]:'';
                $returnNum=$_POST ["getnum"]?1:0;//返回个数
                $condition=[];
                if(!empty($clsno)){
                    $condition[]="item_clsno ='$clsno'";
                }
                $where='';
                if(count($condition)>0){
                    $where=implode(" and ",$condition);
                }
                $res = $Item_info->GetAllModelsForPos ( $start,$limit,$where,$returnNum);
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取商品信息(getItemsData)");
                write_log ( "访问方法:获取商品信息(getItemsData)错误,错误信息:" . $ex.$message, "API/api/getItemsData" );
                $res = "-2";
            }
        }
        return $this->ajaxReturn($res);
    }
	
	// 返回基础代码分类
	public function getBasecode() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				$BaseCodeType = new BaseCodeType ();
				$res = $BaseCodeType->GetUpdateDataForPos ( $rid, $updatetime );
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取基础代码分类信息(Getbasecode)");
				write_log ( "访问方法:获取基础代码分类信息(Getbasecode)错误,错误信息:" . $ex.$message, "API/api/getBasecode" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}

    // 返回基础代码-所有品牌
    public function getBrands() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                //品牌
                $type_no = "PP";
                $BaseCode = new BaseCode ();
                $res = $BaseCode->GetBaseCode ($type_no);
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取所有品牌信息(getBrands)");
                write_log ( "访问方法:获取所有品牌信息(getBrands)错误,错误信息:" . $ex.$message, "API/api/getBrands" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }

    // 返回基础代码-单个品牌
    public function getBrand() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                //品牌
                $type_no = "PP";
                $code_id=!empty($_POST ["code_id"])?trim($_POST ["code_id"]):'';//字段值

                $BaseCode = new BaseCode ();
                $res = $BaseCode->GetByTypeAndCode ($type_no,$code_id);
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取单个品牌信息(getBrand)");
                write_log ( "访问方法:获取单个品牌信息(getBrand)错误,错误信息:" . $ex.$message, "API/api/getBrand" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }
	
	// 返回商品组合
	public function getComb() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				$BdItemCombsplit = new BdItemCombsplit ();
				$res = $BdItemCombsplit->GetUpdateDataForPos ( $rid, $updatetime );
			} catch ( \Exception $ex ) {
				$message=$this->getVars("获取组合商品信息(Getcomb)");
				write_log ( "访问方法:获取组合商品信息(Getcomb)错误,错误信息:" . $ex.$message, "API/api/getComb" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 返回基础信息
	public function getBase() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				$BaseCode = new BaseCode ();
				$res = $BaseCode->GetUpdateDataForPos ( $rid, $updatetime );
			} catch ( \Exception $ex ) {
				$message=$this->getVars("访问方法:获取基础代码信息(Getbase)");
				write_log ( "访问方法:获取基础代码信息(Getbase)错误,错误信息:" . $ex.$message, "API/api/getBase" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 获取操作员
	public function getOper() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				$PosOperator = new PosOperator ();
				$res = $PosOperator->GetUpdateDataForPos ( $_POST ["branch_no"], $rid, $updatetime );
			} catch ( \Exception $ex ) {
				$message=$this->getVars("访问方法:获取操作员(Getoper)");
				write_log ( "访问方法:获取操作员(Getoper)错误,错误信息:" . $ex.$message, "API/api/getOper" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}

	//验证POS终端账户和密码登录
    public function operLogin() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $branch_no = ctrim($_POST ["branch_no"]);//门店编号
                $oper_id=ctrim($_POST ["oper_id"]);//营业员编号
                $password=ctrim($_POST ["passwd"]);//密码
                $PosOperator = new PosOperator ();
                $oper = $PosOperator->verifyOperator ($oper_id, $password);
                if(empty($oper_id)||empty($password)){
                    $res = "3";//员工账户或密码空
                }else{
                    if(!$oper){
                        $res = "4";//账户不存在或密码错误
                    }else{
                        if($oper['oper_status']!=1){
                            $res = "5";//停用
                        }elseif ($oper['branch_no']!=$branch_no&&$oper['branch_no']!='ALL'){
                            $res = "6";//非本门店员工
                        }else{
                            $res=$oper;
                            $res['token']=md5($oper_id.md5($password));
                            //更新最近登录时间
                            $PosOperator->updateLastime($oper['oper_id']);
                        }

                    }
                }
            } catch ( \Exception $ex ) {
                $message=$this->getVars("访问方法:验证操作员登录(operLogin)");
                write_log ( "访问方法:验证操作员登录(operLogin)错误,错误信息:" . $ex.$message, "API/api/operLogin" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }

	
	// 返回门店商品库存
	public function getBrastock() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				
				if (empty ( $_POST ["branch_no"] )) {
					$res = - 10;
				} else {
					$PosBranchStock = new PosBranchStock ();
					$res = $PosBranchStock->GetUpdateDataForPos ( $_POST ["branch_no"], $rid, $updatetime );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("访问方法:获取门店商品库存(Getbrastock)");
				write_log ( "访问方法:获取门店商品库存(Getbrastock)错误,错误信息:" . $ex.$message, "API/api/getBrastock" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 获取门店商品库存
	public function getStock() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["branch_no"] )) {
					$res = - 10;
				} else {
					$PosBranchStock = new PosBranchStock ();
					$res = $PosBranchStock->GetBranchStockForPos ( $_POST ["branch_no"], $_POST ["item_clsno"], $_POST ["item_brand"], $_POST ["item_supcust"], $_POST ["item_no"], "" );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取门店商品库存(Getstock)");
				write_log ( "访问方法:获取门店商品库存(Getstock)错误,错误信息:" . $ex.$message, "API/api/getStock" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 获取支付方式
	public function getPayinfo() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$PaymentInfo = new PaymentInfo ();
				$res = $PaymentInfo->GetModelsForPos ();
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取支付方式(Getpayinfo)");
				write_log ( "访问方法:获取支付方式(Getpayinfo)错误,错误信息:" . $ex.$message, "API/api/getPayinfo" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 返回快捷键
	public function getKey() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["branch_no"] )) {
					$res = - 10;
				} else {
					$PosFunction = new PosFunction ();
					$res = $PosFunction->GetModelsForPos ( $_POST ["branch_no"] );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取快捷键(Getkey)");
				write_log ( "访问方法:获取快捷键(Getkey)错误,错误信息:" . $ex.$message, "API/api/getKey" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// POS机信息
	public function getPosstatus() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["branch_no"] )) {
					$res = - 10;
				} else {
					$PosStatus = new PosStatus ();
					$res = $PosStatus->GetModelsForPos ( $_POST ["branch_no"] );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取POS机登记信息(Getposstatus)");
				write_log ( "访问方法:获取POS机登记信息(Getposstatus)错误,错误信息:" . $ex.$message, "API/api/getPosstatus" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 获取分店商品价格
	public function getBraprc() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$rid = "";
				if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
					$rid = $_POST ["rid"];
				}
				$updatetime = "";
				if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
					$updatetime = $_POST ["updatetime"];
				}
				if (! empty ( $_POST ["branch_no"] )) {
					$PcBranchPrice = new PcBranchPrice ();
					$res = $PcBranchPrice->GetUpdateDataForPos ( $_POST ["branch_no"], $rid, $updatetime );
				} else {
					$res = - 10;
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取门店商品价格信息(Getbraprc)");
				write_log ( "访问方法:获取门店商品价格信息(Getbraprc)错误,错误信息:" . $ex.$message, "API/api/getBraprc" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}

    // 返回活动促销
    public function getpRule() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $PosPlanRule = new PosPlanRule ();
                $res = $PosPlanRule->GetModelsForPos ();
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取促销活动规则(Getprule)");
                write_log ( "访问方法:获取促销活动规则(Getprule)错误,错误信息:" . $ex.$message, "API/api/getpRule" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }

    // 返回活动促销
    public function getpRuleWhere() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $rule_no=ctrim($_POST['rule_no']);
                $range_flag=ctrim($_POST['range_flag']);
                $PosPlanRule = new PosPlanRule ();
                $res = $PosPlanRule->GetRuleByRuleNoAndRangeFlag ($rule_no,$range_flag);
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取促销活动规则(getpRuleWhere)");
                write_log ( "访问方法:获取促销活动规则(getpRuleWhere)错误,错误信息:" . $ex.$message, "API/api/getpRuleWhere" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }

	// 门店促销规则信息
	public function getPmaster() {

        $res=[];
		return $this->ajaxReturn($res);
	}
	
	// 门店促销详细信息
	public function getPdetail() {
        $res=[];
		return $this->ajaxReturn($res);
	}

	//获取当前门店某项促销方案详细
    public function getPlanMaster(){
        $res=[];
        return $this->ajaxReturn($res);
    }

    //获取当前门店促销详细
    public function getPlanDetail(){
        $res=[];
        return $this->ajaxReturn($res);
    }
	
	// 获取单据信息
	public function getSheet() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["branch_no"] ) || empty ( $_POST ["trans_no"] )) {
					$res = - 10;
				} else {
					$PmSheetMaster = new PmSheetMaster ();
					$res = $PmSheetMaster->GetModelsForPos ( $_POST ["branch_no"], $_POST ["sheet_no"], $_POST ["trans_no"], $_POST ["start"], $_POST ["end"], $_POST ["approve_flag"] );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取单据信息(Getsheet)");
				write_log ( "访问方法:获取单据信息(Getsheet)错误,错误信息:" . $ex.$message, "API/api/getSheet" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 单据信息详细
	public function getsDetail() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["sheet_no"] ) || empty ( $_POST ["trans_no"] )) {
					$res = - 10;
				} else {
					$PmSheetDetail = new PmSheetDetail ();
					$res = $PmSheetDetail->GetModelsForPos ( $_POST ["sheet_no"], $_POST ["trans_no"] );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:获取单据信息(Getsheet)");
				write_log ( "访问方法:获取单据信息(Getsheet)错误,错误信息:" . $ex.$message, "API/api/getsDetail" );
				$res = "-2";
			}
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 删除要货单
	public function delPsheet() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["sheet_no"] )) {
					$res = - 10;
				} else {
					$PmSheetMaster = new PmSheetMaster ();
					$res = $PmSheetMaster->Del ( $_POST ["sheet_no"] );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:删除要货单据(Delpsheet)");
				if (! empty ( $_POST ["sheet_no"] )) {
					$message .= ",sheet_no:" . $_POST ["sheet_no"];
				}
				
				write_log ( "访问方法:删除要货单据(Getsheet)错误,错误信息:" . $ex.$message, "API/api/delPsheet" );
				$res = "-2";
			}
		}
		
		echo $res;
		exit();
	}
	
	// 新增要货单信息
	public function addPsheet() {
		$res = $this->ApiConnect ( $_POST );
		try {
			if ($res == 1) {
				$result = $this->Auth ( $_POST, 0 );
				if ($result == 1) {
					if (empty ( $_POST ["master"] ) || empty ( $_POST ["detail"] ) || empty ( $_POST ["add"] )) {
						$res = - 10;
					} else {
						$masters = json_decode ( $_POST ["master"], true );
						$details1 = json_decode ( $_POST ["detail"], true );
						$addflag = $_POST ["add"];
						if (empty ( $masters ) || empty ( $details1 ) || empty ( $addflag )) {
							$res = - 10;
						} else {
							if ($addflag == "add") {
								$model = new PmSheetMaster ();
								$model->d_branch_no = $masters [0] ['d_branch_no'];
								$model->db_no="+";
								$SysSheetNo = new SysSheetNo ();
								$model->sheet_no = $SysSheetNo->CreateSheetNo ( "YH", $model->d_branch_no );
							} else {
								$PmSheetMaster = new PmSheetMaster ();
								$model = $PmSheetMaster->where ( "sheet_no='{$_POST["sheet_no"]}'" )->find ();
							}
							foreach ( $masters [0] as $key => $value ) {
								if($key=='sheet_no'||$key=='db_no'){
									continue;
								}
								$model->$key = $value;
							}
							$model->valid_date = date ( DATE_FORMAT, strtotime ( '+7 day', strtotime ( date ( DATE_FORMAT ) ) ) );
							$details = array ();
							$Item_info = new Item_info ();
							foreach ( $details1 as $k => $v ) {
								$detail1 = new PmSheetDetail ();
								foreach ( $v as $key => $value ) {
									$detail1->$key = $value;
								}
								$detail1->item_no = $v ["item_no"];
								$temp_model = $Item_info->where ( "item_no='{$v["item_no"]}'" )->find ();
								if (! empty ( $temp_model->purchase_spec )) {
									$large_qty = doubleval ( $v ["real_qty"] / $temp_model->purchase_spec );
									$detail1->large_qty = formatMoneyDisplay ( $large_qty );
								}
								$detail1->real_qty = $v ["real_qty"];
								$detail1->other1 = $v ["other1"];
								$detail1->sheet_no = $model->sheet_no;
								array_push ( $details, $detail1 );
							}
							$PmSheetMaster = new PmSheetMaster ();
							$result1 = $PmSheetMaster->SaveSheet ( $model, $details, $addflag );
							if ($result1 == 1) {
								$res = $model->sheet_no;
							} else {
								$res = $result1;
							}
						}
					}
				} else {
					$res = $result;
				}
			}
		} catch ( \Exception $ex ) {
			
			$res = - 2;
			$message=$this->getVars("方法:新增单据信息(Addpsheet)");
			if (! empty ( $_POST ["master"] )) {
				$message .= ",master:" . $_POST ["branch_no"];
			}
			if (! empty ( $_POST ["detail"] )) {
				$message .= ",detail:" . $_POST ["branch_no"];
			}
			
			write_log ( "方法:新增单据信息(Addpsheet),异常:" . $ex.$message, "API/api/addPsheet" );
		}
		
		echo $res;
		exit();
	}
	
	// 新增流水
	public function addFlow() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			
			try {
				
				if (empty ( $_POST ["pay"] )) {
					$res = - 10;
				} else {
					$pays1 = json_decode ( $_POST ["pay"], true );
					if (! empty ( $_POST ["sale"] )) {
						$sales1 = json_decode ( $_POST ["sale"], true );
					}

					if (empty ( $pays1 )) {
						$res = - 10;
					} else {
						$pays = array ();
						$sales = array ();
                        $payRelation=array();
						$PosPayFlow = new PosPayFlow ();
						foreach ( $pays1 as $k => $v ) {
							$payflow = new PosPayFlow ();
							$flow_id = $v ["flow_id"]; // 商品序号
							$flow_no = $v ["flow_no"]; // 唯一流水号
							$record = $PosPayFlow->where ( "flow_id='$flow_id' and flow_no='$flow_no'" )->find ();
							if (! empty ( $record )) {
								$res = 0;
								break;
							}

                            $coinType="";
                            $pflow_id="";
                            foreach ( $v as $key => $value ) {
                                $key=trim($key);
                                $value=trim($value);

                                if($key=='pflow_id'&&$value!=''){//微信或支付宝等临时支付号
                                    $pflow_id=$value;
                                }else{
                                    $payflow->$key = $value;
                                }
                                //支付方式 是 Wechat / ZFB 则记录
                                if($key=='coin_type'&&($value=='Wechat'||$value=='ZFB')){
                                    $coinType=$value;
                                }
                            }

                            //记录微信或支付宝对应的支付流水号
                            if($coinType!=""&&$pflow_id!=""){
                                $payRelation[$flow_no][$coinType]['coin_type']=$coinType;//支付方式名称Wechat/ZFB
                                $payRelation[$flow_no][$coinType]['pflow_id']=$pflow_id;// $flow_no唯一流水号对应一个临时流水号$value
                            }
							
							$payflow->com_flag = '1';
							if (empty ( $v ["pos_flag"] )) {
								$payflow->pos_flag = "0";
							} else {
								$payflow->pos_flag = $v ["pos_flag"];
							}
							
							array_push ( $pays, $payflow );
						} // end of foreach
						
						$Item_info = new Item_info ();
						$PosSaleFlow = new PosSaleFlow ();
						if (! empty ( $sales1 )) {
							foreach ( $sales1 as $v ) {
								$saleflow = new PosSaleFlow ();
								
								$saleflow->flow_id = $v ["flow_id"];
								$saleflow->flow_no = $v ["flow_no"];
								$saleflow->item_no = $v ["item_no"];
								$item = $Item_info->where ( "item_no='{$v['item_no']}'" )->find ();
								$sale_record = $PosSaleFlow->where ( "flow_id='{$v["flow_id"]}' and flow_no='{$v["flow_no"]}'" )->find ();
								if (! empty ( $sale_record )) {
									$res = 0;
									break;
								}
								$saleflow->unit_price = $v ["sale_price"];
								$saleflow->sale_price = $v ["unit_price"];
								$saleflow->sale_qnty = $v ["sale_qnty"];
								$saleflow->sale_money = $v ["sale_money"];
								$saleflow->in_price = $v ["price"];//进货价
								$saleflow->sell_way = $v ["sale_way"];
								$saleflow->discount_rate = $v ["discount_rate"];
								$saleflow->oper_id = $v ["oper_id"];
								$saleflow->oper_date = $v ["oper_date"];
								$saleflow->item_clsno = $v ["item_clsno"];
								$saleflow->item_subno = $v ["item_subno"];
								$saleflow->item_brand = $v ["item_brand"];
								$saleflow->item_name = $item->item_name;
								
								$saleflow->item_status = $v ["item_status"];
								$saleflow->item_subname = $item->item_subname;
								
								$saleflow->reasonid = $v ["reasonid"];
								$saleflow->branch_no = $v ["branch_no"];
								$saleflow->pos_id = $v ["pos_id"];
								$saleflow->plan_no = $v ["plan_no"];
								$saleflow->com_flag = '1';
								
								array_push ( $sales, $saleflow );
							}
						}
						if ($res == 1) {
							if (empty ( $pays )) {
								$res = - 10;
							} else {
								$PosPayFlow = new PosPayFlow ();
								$res = $PosPayFlow->AddModelsForPos ( $pays, $sales,$payRelation);
							}
						}
					}
				}
			} catch ( \Exception $ex ) {
				$res = - 2;
				$message=$this->getVars("方法:新增流水(Addflow)");
				if (! empty ( $_POST ["pay"] )) {
					$message .= ",收银流水:" . $_POST ["pay"];
				}
				if (! empty ( $_POST ["sale"] )) {
					$message .= ",销售流水:" . $_POST ["sale"];
				}
				write_log ( "方法:新增流水(Addflow),异常:" . $ex.$message, "API/api/addFlow" );
			}
		}
		
		echo $res;
		exit();
	}

	//获取最新的销售流水号
    public function getLastSaleFlow() {

        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $branch_no = $_POST['branch_no'];
                $PosSaleFlow = new PosSaleFlow ();
                $res=$PosSaleFlow->GetLastFlow($branch_no);
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取门店最新销售流水(getLastSaleFlow)");
                write_log ( "访问方法:获取门店最新销售流水(getLastSaleFlow)错误,错误信息:" . $ex.$message, "API/api/getLastSaleFlow" );
                $res = "-2";
            }
        }

        return $this->ajaxReturn($res);
    }

	// 更新门店库存
	public function updateStock() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			
			try {
				if (empty ( $_POST ["branch_no"] ) || empty ( $_POST ["item_no"] ) || empty ( $_POST ["qty"] ) || empty ( $_POST ["db_no"] )) {
					$res = - 10;
				} else {
					if (! is_numeric ( $_POST ["qty"] )) {
						$res = - 10;
					} else {
						$PosBranchStock = new PosBranchStock ();
						if ($PosBranchStock->UpdateStock ( $_POST ["branch_no"], $_POST ["item_no"], $_POST ["qty"], $_POST ["db_no"] )) {
							$res = 1;
						} else {
							$res = 0;
						}
					}
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:更新门店库存(Updatestock)");
				if (! empty ( $_POST ["pay"] )) {
					$message .= ",收银流水:" . $_POST ["pay"];
				}
				if (! empty ( $_POST ["sale"] )) {
					$message .= ",销售流水:" . $_POST ["sale"];
				}
					
				write_log ( "方法:更新门店库存(Updatestock),异常:" . $ex . $message, "API/api/updateStock" );
				$res = - 2;
			}
		}
		
		echo $res;
		exit();
	}

	//检测旧密码是否正确
    public function checkOperOldPwd(){
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            if (empty ( $_POST ["operId"] ) || empty ( $_POST ["pwd"] )) {
                $res= -10;
            } else {
                $model = new PosOperator ();
                $result = $model->verifyOperator ( trim ( $_POST ["operId"] ), trim ( $_POST ["pwd"] ) );
                if($result){
                    $res= 1;
                }else{
                    $res= -2;
                }
            }
        }
        echo $res;
        exit();
    }
	
	// 更新操作员密码
	public function updateOperPwd() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				if (empty ( $_POST ["operId"] ) || empty ( $_POST ["pwd"] )) {
                    $res= -10;
				} else {
					$model = new PosOperator ();
                    $res = $model->UpdatePwd ( trim ( $_POST ["operId"] ), md5(trim ( $_POST ["pwd"] )) );
				}
			} catch ( \Exception $ex ) {
				$message=$this->getVars("方法:更新密码(UpdateOperPwd)");
				write_log ( "方法:更新密码(UpdateOperPwd),异常:" . $ex.$message, "API/api/updateOperPwd" );
				$res = - 2;
			}
		}
		
		echo $res;
		exit();
	}
	
	// 添加留言反馈
	public function addFeedback() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				$model = new PosFeedback ();
				$model->branch_no = $_POST ["branch_no"];
				$model->posid = $_POST ["posid"];
				$model->oper_id = $_POST ["oper_id"];
				$model->content = $_POST ["content"];
				$res = $model->AddModelForPos ( $model );
			} catch ( \Exception $ex ) {
				$res = - 2;
				$message=$this->getVars("方法:添加反馈(Addfeedback)");
				write_log ( "方法:添加反馈(Addfeedback),异常:" . $ex.$message, "API/api/addFeedback" );
			}
		}
		
		echo $res;
		exit();
	}
	
	// 收银员对账
	public function addPosac() {
		$res = $this->ApiConnect ( $_POST );
		if ($res == 1) {
			try {
				
				if (empty ( $_POST ["ac"] )) {
					$res = "-10";
				} else {
					$acs = json_decode ( $_POST ["ac"], true );
					$models = array ();
					if (empty ( $acs )) {
						$res = "-10";
					} else {
						foreach ( $acs as $model ) {
							$posac = new PosAccount ();
							$posac->branch_no = $model ["branch_no"];
							$posac->pos_id = $model ["pos_id"];
							$posac->oper_id = $model ["oper_id"];
							$posac->oper_date = $model ["oper_date"];
							$posac->start_time = $model ["start_time"];
							$posac->end_time = $model ["end_time"];
							$posac->sale_amt = $model ["sale_amt"];
							$posac->hand_amt = $model ["hand_amt"];
							array_push ( $models, $posac );
						}
						$PosAccount = new PosAccount ();
						$res = $PosAccount->AddModelsForPos ( $models );
					}
				}
			} catch ( \Exception $ex ) {
				$res = - 2;
				$message=$this->getVars("方法:收银员对账记录(Addposac)");
				write_log ( "方法:收银员对账记录(Addposac),异常:" . $ex.$message, "API/api/addPosac" );
			}
		}
		
		echo $res;
		exit();
	}
	
	// 调出单列表
	public function getMosheet() {
		$res = $this->ApiConnect ( $_POST );
		try {
			if ($res == 1) {
				$ImSheetMaster = new ImSheetMaster ();
				$res = $ImSheetMaster->GetNoneDearMoForPos ( $_POST ["start"], $_POST ["end"], $_POST ["sheet_no"], $_POST ["branch_no"] );
			}
		} catch ( \Exception $ex ) {
			$message=$this->getVars("访问方法:获取调出单列表(Getmosheet)");
			if (! empty ( $_POST ["start"] )) {
				$message .= ",开始时间:" . $_POST ["start"];
			}
			if (! empty ( $_POST ["end"] )) {
				$message .= ",结束时间:" . $_POST ["end"];
			}
			if (! empty ( $_POST ["sheet_no"] )) {
				$message .= ",单据编号:" . $_POST ["sheet_no"];
			}
			if (! empty ( $_POST ["branch_no"] )) {
				$message .= ",门店编码:" . $_POST ["branch_no"];
			}
			write_log ( "获取调出单列表失败" . $ex.$message, "API/api/getMosheet" );
			$result = - 2;
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 调出单详细信息
	public function getModetail() {
		$res = $this->ApiConnect ( $_POST );
		try {
			if ($res == 1) {
				
				if (empty ( $_POST ["sheet_no"] )) {
					$res = - 10;
				} else {
					$ImSheetDetail = new ImSheetDetail ();
					$res = $ImSheetDetail->GetDearDetailForPos ( $_POST ["sheet_no"] );
				}
			}
		} catch ( \Exception $ex ) {
			$message=$this->getVars("访问方法:获取调出单详细信息(Getmodetail)");
			if (! empty ( $_POST ["sheet_no"] )) {
				$message .= ",单据编号:" . $_POST ["sheet_no"];
			}
			write_log ( "获取调出单详细信息失败" . $ex.$message, "API/api/getModetail" );
			$res = - 2;
		}
		
		return $this->ajaxReturn($res);
	}
	
	// 确认收货
	public function addMisheet() {
		$result = $this->ApiConnect ( $_POST );
		try {
			
			if ($result == 1) {
				$result = $this->Auth ( $_POST, 0 );
				if ($result == 1) {
					$master = $_POST ["master"];
					$detail = $_POST ["detail"];
					if (empty ( $master ) || empty ( $detail )) {
						$result = - 10;
					} else {
						$master1 = json_decode ( $master, true );
						$detail1 = json_decode ( $detail, true );
						$SysSheetNo = new SysSheetNo ();
						foreach ( $master1 as $v ) {
							$master11 = new ImSheetMaster ();
							foreach ( $v as $key => $value ) {
								$master11->$key = $value;
							}
							$master11->db_no = '+';
							$master11->trans_no = 'MI';
							$master11->sheet_no = $SysSheetNo->CreateSheetNo ( $master11->trans_no, $master11->d_branch_no );
						}
						$details = array ();
						foreach ( $detail1 as $v ) {
							$_detail11 = new ImSheetDetail ();
							foreach ( $v as $key => $value ) {
								$_detail11->$key = $value;
							}
							array_push ( $details, $_detail11 );
						}
						if (empty ( $details ) || empty ( $master11 )) {
							$result = - 10;
						} else {
							$ImSheetMaster = new ImSheetMaster ();
							$result = $ImSheetMaster->AddMi ( $master11, $details, 0 );
							if ($result == 1) {
								$model = $ImSheetMaster->GetMISheetForPos ( $master11->voucher_no );
								if (! empty ( $model )) {
									$result = $ImSheetMaster->ApproveMi ( $model->sheet_no, $master11->oper_id );
								} else {
									$result = - 5;
								}
							}
						}
					}
				}
			}
		} catch ( \Exception $ex ) {
			$message=$this->getVars("访问方法:确认收货(Addmisheet)");
			if (! empty ( $_POST ["master"] )) {
				$message .= ",主单据信息:" . $_POST ["master"];
			}
			if (! empty ( $_POST ["detail"] )) {
				$message .= ",主单据详细信息:" . $_POST ["detail"];
			}
			write_log ( "确认收货信息失败" . $ex.$message, "API/api/addMisheet" );
			$result = - 2;
		}
		
		echo $result;
		exit();
	}
	
	// 要货申请单，返回有货的商品
	public function getComstock() {
		$result = $this->ApiConnect ( $_POST );
		try {
			if ($result == 1) {
				$result = $this->Auth ( $_POST, 0 );
				if ($result == 1) {
					if (empty ( $_POST ["branch_no"] ) || empty ( $_POST ["d_branch_no"] )) {
						$result = - 10;
					} else {
						$Item_info = new Item_info ();
						$result = $Item_info->GetCompareStock ( $_POST ["branch_no"], $_POST ["d_branch_no"] );
					}
				}
			}
		} catch ( \Exception $ex ) {
			
			$message=$this->getVars("访问方法:获取要货门店对比信息(Getcomstock)");
			if (! empty ( $_POST ["branch_no"] )) {
				$message .= ",要货门店:" . $_POST ["branch_no"];
			}
			if (! empty ( $_POST ["d_branch_no"] )) {
				$message .= ",发货门店:" . $_POST ["d_branch_no"];
			}
				
			write_log ( "获取要货门店对比信息" . $ex.$message, "API/api/getComstock" );
			$result = - 2;
		}
		
		
		return $this->ajaxReturn($result);
	}

	// 会员消费记录
    public function addvip() {
        $result = $this->ApiConnect ( $_POST );
        if ($result == 1) {
            try {
                if (empty ( $_POST ["vip"] )) {
                    $result = "-10";
                } else {
                    $models = json_decode ( $_POST ["vip"], true );
                    if (empty ( $models )) {
                        $result = "-10";
                    } else {
                        $model=$models[0];
                        $posvip = new PosViplist ();
                        $posvip->flow_no = $model ["flow_no"];
                        $posvip->card_no = $model ["card_no"];
                        $posvip->score = $model ["score"];
                        $posvip->sale_amt = $model ["sale_amt"];
                        $posvip->card_score = $model ["card_score"];
                        $posvip->card_amount = $model ["card_amount"];
                        $posvip->oper_date = $model ["oper_date"];
                        $posvip->voucher_no = $model ["voucher_no"];
                        $PosViplist = new PosViplist ();
                        $result = $PosViplist->AddModelsForPos ( $posvip );
                    }
                }
            } catch ( \Exception $ex ) {
                $message=$this->getVars("访问方法:会员消费记录(Addvip)");
                if (! empty ( $_POST ["branch_no"] )) {
                    $message .= ",要货门店:" . $_POST ["branch_no"];
                }
                if (! empty ( $_POST ["d_branch_no"] )) {
                    $message .= ",发货门店:" . $_POST ["d_branch_no"];
                }
                write_log ( "会员消费记录(Addvip)，异常:" . $ex.$message, "API/api/addvip" );
                $result = - 2;
            }
        }

        echo $result;
        exit();
    }
	
	// 是否在优惠活动中领取商品
	public function getIsFirstSale() {
		$result = 0;
		try {
			if (empty ( $_POST ["plan_no"] ) || empty ( $_POST ["item_no"] ) || $_POST ["mem_no"]) {
				$result = - 10;
			} else {
				$result = $this->ApiConnect ( $_POST );
				if ($result == 1) {
					$result = $this->Auth ( $_POST, 0 );
					if ($result == 1) {
						$PosSaleFlow = new PosSaleFlow ();
						$result = $PosSaleFlow->GetIsFirstSaleForPos ( $_POST ["vip_no"], $_POST ["item_no"], $_POST ["plan_no"] );
					}
				}
			}
		} catch ( \Exception $ex ) {
			$message=$this->getVars("访问方法:是否在优惠活动中领取商品(getIsFirstSale)");
			if (! empty ( $_POST ["plan_no"] )) {
				$message .= ",活动编号:" . $_POST ["plan_no"];
			}
			if (! empty ( $_POST ["item_no"] )) {
				$message .= ",商品编号:" . $_POST ["item_no"];
			}
			if (! empty ( $_POST ["vip_no"] )) {
				$message .= ",会员账号:" . $_POST ["vip_no"];
			}
			write_log ( "是否在优惠活动中领取商品" . $ex.$message, "API/api/getIsFirstSale" );
			$result = - 2;
		}
		echo $result;
		exit();
	}
	
	// 更新支付流水
	public function updatePayflow() {
		$result = 0;
		try {
			$result = $this->ApiConnect ( $_POST );
			if ($result == 1 && ($this->Auth ( $_POST, 0 ) == 1)) {
				if (empty ( $_POST ["flow_no"] ) || empty ( $_POST ["flow_id"] ) || empty ( $_POST ["pay_way"] ) || empty ( $_POST ["pay_name"] ) || empty ( $_POST ["pay_amount"] )) {
					$result = - 10;
				} else {
					$PosPayFlow = new PosPayFlow ();
					$result = $PosPayFlow->UpdatePaywayForPos ( $_POST ["flow_no"], $_POST ["flow_id"], $_POST ["pay_way"], $_POST ["pay_name"], $_POST ["pay_amount"] );
				}
			}
		} catch ( \Exception $ex ) {
			$message=$this->getVars("访问方法:更新支付流水(Updatepayflow)");
			$message .= ",exception:" . $ex;
			write_log ( $message, "API/api/updatePayflow" );
			$result = - 2;
		}
		return $result;
	}

    // 支付宝支付刷卡面对面支付(付款码支付)
    public function aliPayFtf() {
        $result = $this->ApiConnect ( $_POST );
        if ($result != 1) {
            return strval ( $result );
        }
        $result = $this->Auth ( $_POST, 0 );
        if ($result != 1) {
            return strval ( $result );
        }

        $flowno = input ( "flow_no" ); // 订单号
        $payAmount = input ( "pay_amount" ); // 订单金额
        $authCode = input ( "auth_code" ); // 18位数字授权码
        $branchno = input ( "branch_no" ); // 商店号
        $payAmount = floatval ( $payAmount ); // 订单总金额。单位为元

        $path = "../extend/alipay/f2fpay/";
        require_once $path . 'model/builder/AlipayTradePayContentBuilder.php';
        require_once $path . 'service/AlipayTradeService.php';

        // 外向订单号
        $outTradeNo = $flowno;
        // 查询门店名称
        $PosBranchInfo = new PosBranch ();
        $shop = $PosBranchInfo->getone ( $branchno );
        if (! $shop) {
            $branchName = $branchno;
        } else {
            $branchName = $shop ['branch_name'];
        }

        if (!$shop||empty($shop['alipay_appid'])||empty($shop['alipay_public_key'])||empty($shop['alipay_private_key'])) {
            $message = $branchno."查询支付宝支付状态缺少配置";
            write_log ( $message, "API/api/aliPayFtf" );
            return "-3";
        }

        $shop_config=[];
        $shop_config['app_id'] = $shop ['alipay_appid']; //应用ID
        $shop_config['alipay_public_key'] = $shop ['alipay_public_key']; // 支付宝公钥
        $shop_config['merchant_private_key']= $shop ['alipay_private_key']; // 商户私钥

        //更改门店支付配置
        $ali_config=isset($config)?array_merge($config,$shop_config):[];

        // (必填) 订单标题，粗略描述用户的支付目的”
        $subject = $branchName . "消费订单支付";

        // (必填) 订单总金额
        // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
        $totalAmount = $payAmount;//单位是(元)

        // (必填) 付款条码
        $authCode = $authCode; // 28开头18位数字

        // (可选,根据需要使用) 订单可打折金额
        // 如果该值未传入,但传入了【订单总金额】,【不可打折金额】 则该值默认为【订单总金额】- 【不可打折金额】
        // String discountableAmount = "1.00"; //

        // (可选) 订单不可打折金额
        // 如果该值未传入,但传入了【订单总金额】,【打折金额】,则该值默认为【订单总金额】-【打折金额】
        $undiscountableAmount = "0.00";

        // 卖家支付宝账号ID，用于支持一个签约账号下支持打款到不同的收款账号，(打款到sellerId对应的支付宝账号)
        // 如果该字段为空，则默认为与支付宝签约的商户的PID，也就是appid对应的PID
        $sellerId = "";

        // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
        $body = "购买商品共{$totalAmount}元";

        // 商户操作员编号，添加此参数可以为商户操作员做销售统计
        $operatorId = "";

        // (可选) 商户门店编号，通过门店号和商家后台可以配置精准到门店的折扣信息，详询支付宝技术支持
        $storeId = "";

        // 支付宝的店铺编号
        $alipayStoreId = "";

        // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
        $providerId = ""; // 系统商pid,作为系统商返佣数据提取的依据
        $extendParams = new \ExtendParams ();
        $extendParams->setSysServiceProviderId ( $providerId );
        $extendParamsArr = $extendParams->getExtendParams ();

        // 支付超时，线下扫码交易定义为5分钟
        $timeExpress = "5m";

        // 商品明细列表，需填写购买商品详细信息，
        $goodsDetailList = array ();

        // 第三方应用授权令牌,商户授权系统商开发模式下使用
        $appAuthToken = ""; // 根据真实值填写

        // 创建请求builder，设置请求参数
        $barPayRequestBuilder = new \AlipayTradePayContentBuilder ();
        $barPayRequestBuilder->setOutTradeNo ( $outTradeNo );
        $barPayRequestBuilder->setTotalAmount ( $totalAmount );
        $barPayRequestBuilder->setAuthCode ( $authCode );
        $barPayRequestBuilder->setTimeExpress ( $timeExpress );
        $barPayRequestBuilder->setSubject ( $subject );
        $barPayRequestBuilder->setBody ( $body );
        $barPayRequestBuilder->setUndiscountableAmount ( $undiscountableAmount );
        $barPayRequestBuilder->setExtendParams ( $extendParamsArr );
        $barPayRequestBuilder->setGoodsDetailList ( $goodsDetailList );
        $barPayRequestBuilder->setStoreId ( $storeId );
        $barPayRequestBuilder->setOperatorId ( $operatorId );
        $barPayRequestBuilder->setAlipayStoreId ( $alipayStoreId );

        $barPayRequestBuilder->setAppAuthToken ( $appAuthToken );

        // 调用barPay方法获取当面付应答
        $barPay = new \AlipayTradeService ( $ali_config );
        $barPayResult = $barPay->barPay ( $barPayRequestBuilder );

        $code="-1";
        switch ($barPayResult->getTradeStatus ()) {
            case "SUCCESS" :
                // "支付宝支付成功:";
                //添加支付信息
                $PosPay = new PosPay ();
                $PosPay->AddZfbPosPay ( $outTradeNo, $totalAmount );
                $response=$barPayResult->getResponse();
                // 添加收银支付流水信息
                //支付宝交易号 trade_no
                $trade_no=isset($response->trade_no)?$response->trade_no:'';
                $PosPay->UpdatewxPosPay ( $outTradeNo, $trade_no); // 更新支付信息
                return "1";

            case "FAILED" :
                // "支付宝支付失败!!!";
                $code="-2";
            case "UNKNOWN" :
                // "系统异常，订单状态未知!!!";
                $code="-3";
            default :
                // "不支持的交易状态，交易返回异常!!!";
                $code="-4";
        }

        $message = "访问方法:支付宝扫码支付(aliPayFtf)失败 :" .$code ;
        write_log ( $message, "API/api/aliPayFtf" );
        return $code;
    }

    // 返回支付宝支付状态
    public function getAliPayStatus() {
        $result = $this->ApiConnect ( $_POST );
        if ($result != 1) {
            return strval ( $result );
        }
        $result = $this->Auth ( $_POST, 0 );
        if ($result != 1) {
            return strval ( $result );
        }

        $flow_no = input ( "flowno" ); // POS系统端的订单号
        $branchno = input ( "branch_no" ); // 商店号
        if (empty ( $flow_no )) {
            return "-10";
        }

        $path = "../extend/alipay/f2fpay/";
        require_once $path.'service/AlipayTradeService.php';

        try {

            $posDB = new PosBranch ();
            $shop = $posDB->getone ( $branchno );
            if (!$shop||empty($shop['alipay_appid'])||empty($shop['alipay_public_key'])||empty($shop['alipay_private_key'])) {
                $message = $branchno."查询支付宝支付状态缺少配置";
                write_log ( $message, "API/api/getAliPayStatus" );
                return "-3";
            }

            $shop_config=[];
            $shop_config['app_id'] = $shop ['alipay_appid']; //应用ID
            $shop_config['alipay_public_key'] = $shop ['alipay_public_key']; // 支付宝公钥
            $shop_config['merchant_private_key']= $shop ['alipay_private_key']; // 商户私钥

            //更改门店支付配置
            $ali_config=isset($config)?array_merge($config,$shop_config):[];

            ////获取商户订单号
            $out_trade_no = trim($flow_no);

            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $appAuthToken = "";//根据真实值填写

            //构造查询业务请求参数对象
            $queryContentBuilder = new \AlipayTradeQueryContentBuilder();
            $queryContentBuilder->setOutTradeNo($out_trade_no);

            $queryContentBuilder->setAppAuthToken($appAuthToken);


            //初始化类对象，调用queryTradeResult方法获取查询应答
            $queryResponse = new \AlipayTradeService($ali_config);
            $queryResult = $queryResponse->queryTradeResult($queryContentBuilder);

            //根据查询返回结果状态进行业务处理
            $result="-4";
            //print_r($queryResult->getResponse())
            switch ($queryResult->getTradeStatus()){
                case "SUCCESS":
                    //"支付宝查询交易成功:";
                    $response=$queryResult->getResponse();
                    $transaction_id=$response->trade_no;//支付宝交易号
                    $PosPay = new PosPay ();
                    $PosPay->UpdateZfbPosPay ( $flow_no, $transaction_id); // 更新支付信息
                    $result="1";
                case "FAILED":
                    $msg="支付宝查询交易失败或者交易已关闭!!!";
                    break;
                case "UNKNOWN":
                    $msg="系统异常，订单状态未知!!!";
                    break;
                default:
                    $msg="不支持的查询状态，交易返回异常!!!";
                    break;
            }

            if($result!="1"){
                write_log ($msg, "API/api/getAliPayStatus" );
            }
            return $result;
        } catch ( \Exception $e ) {
            //检测异常
            $message = "访问方法:支付宝支付-订单查询(getAliPayStatus)异常，" . json_encode ( $e );
            write_log ( $message, "API/api/getAliPayStatus" );
            return "-5";
        }
    }

    // 微信商户刷卡支付接口
    public function wechatPay() {

        $result = $this->ApiConnect ( $_POST );
        if ($result != 1) {
            return strval ( $result );
        }
        $result = $this->Auth ( $_POST, 0 );
        if ($result != 1) {
            return strval ( $result );
        }

        $flowno = input ( "flow_no" ); // 订单号
        $payAmount = input ( "pay_amount" ); // 订单金额
        $authCode = input ( "auth_code" ); // 18位数字授权码
        $branchno = input ( "branch_no" ); // 商店号
        $payAmount = floatval ( $payAmount ) * 100;//以分为单位

        if (empty ( $flowno ) || ! is_numeric ( $payAmount ) || empty ( $authCode )) {
            return "-10";
        }

        $PosBranchInfo = new PosBranch ();
        $shop = $PosBranchInfo->getone ( $branchno );
        if (! $shop) {
            $branchName = $branchno;
        } else {
            $branchName = $shop ['branch_name'];
        }

        $subject = $branchName . "消费订单支付";

        $lib_path = "../extend/wxpay/lib/";
        require_once ($lib_path . "WxPay.Api.php");
        require_once ($lib_path . "WxPay.Config.Interface.php");
        require_once ($lib_path . "WxPay.MicroPay.php");

        // 查询当前这个POS或门店的微信支付设置
        if (!$shop	||empty ($shop ['wechat_appid'])
            || empty ($shop ['wechat_merchantid'])
            || empty ($shop ['wechat_secret'])
            ||empty($shop ['wechat_paykey'])
        )
        {
            $message = "访问方法:微信刷卡支付(Wechatpay)异常，缺少商户配置";
            write_log ( $message, "API/api/wechatPay" );
            return "-4";
        }

        $appid = $shop ['wechat_appid']; // 微信公众号appid
        $merchantid = $shop ['wechat_merchantid']; // 微信商家商户号
        $appsecret = $shop ['wechat_secret']; // 微信公众号开发者密钥
        $paykey = $shop ['wechat_paykey']; // 微信商家支付密钥

        try {

            $input = new \WxPayMicroPay ();
            $input->SetAuth_code ( $authCode );
            $input->SetBody ( $subject );
            $input->SetTotal_fee ( $payAmount );

            $input->SetOut_trade_no ( $flowno );

            $microPay = new \MicroPay ( $appid, $merchantid, $appsecret, $paykey );
            $wxpay_result = $microPay->pay ( $input );

            if (strtoupper ( $wxpay_result ['return_code'] ) == 'SUCCESS' && strtoupper ( $wxpay_result ['result_code'] ) == 'SUCCESS' && strtoupper ( $wxpay_result ['return_msg'] ) == 'OK') {
                //添加收银支付流水信息
                $PosPay = new PosPay ();
                $PosPay->AddWxPosPay ( $flowno, round($payAmount/100,2) );
                $PosPay->UpdatewxPosPay ( $flowno, $wxpay_result ['transaction_id'] ); // 更新支付信息
                $result = "1";
            } else {
                $result = "-4"; // 支付失败
                $message = "访问方法:微信刷卡支付(Wechatpay)失败 ，" . json_encode ( $wxpay_result );
                write_log ( $message, "API/api/wechatPay" );
            }

            return $result;

        } catch ( \Exception $e ) {
            $result = "-2";
            $message = "访问方法:微信刷卡支付(Wechatpay)失败，" . json_encode ( $e );
            write_log ( $message, "API/api/wechatPay" );
            return "-2";
        }

    }

    // 返回微信刷卡支付状态
    public function getWxPayStatus() {

        $result = $this->ApiConnect ( $_POST );
        if ($result != 1) {
            return strval ( $result );
        }
        $result = $this->Auth ( $_POST, 0 );
        if ($result != 1) {
            return strval ( $result );
        }

        $flow_no = input ( "flowno" ); // POS系统端的订单号
        $branchno = input ( "branch_no" ); // 商店号

        if (empty ( $flow_no )) {
            return "-10";
        }

        $lib_path = "../extend/wxpay/lib/";
        require_once ($lib_path . "WxPay.Api.php");
        require_once ($lib_path . "WxPay.Config.Interface.php");
        require_once ($lib_path . "WxPay.MicroPay.php");

        try {

            $posDB = new PosBranch ();
            $shop = $posDB->getone ( $branchno );

            if (!$shop	||trim ($shop ['wechat_appid']) == ''
                || trim ($shop ['wechat_merchantid']) == ''
                || trim ($shop ['wechat_secret']) == ''
                ||trim($shop ['wechat_paykey']== '')
            )
            {
                $message = "访问方法:支付宝查询(Wechatpay)异常，缺少商户配置";
                write_log ( $message, "API/api/wechatPay" );
                return "-4";
            }

            $appid=$shop ['wechat_appid'];
            $merchantid=$shop ['wechat_merchantid'];
            $appsecret=$shop ['wechat_secret'];
            $paykey=$shop ['wechat_paykey'];

            $input = new \WxPayOrderQuery ();
            $input->SetOut_trade_no ( $flow_no );
            $config = new \WxPayConfig ( $appid, $merchantid, $appsecret, $paykey );

            $queryResult = \WxPayApi::orderQuery ( $config, $input );
            if (strtoupper ( $queryResult ['return_code'] ) == 'SUCCESS' && strtoupper ( $queryResult ['result_code'] ) == 'SUCCESS' && strtoupper ( $queryResult ['trade_state'] ) == 'SUCCESS') {
                $PosPay = new PosPay ();
                $PosPay->UpdatewxPosPay ( $flow_no, $queryResult ['transaction_id'] ); // 更新支付信息
                $result = "1";
            } else {
                $result = "-4"; // 未支付
            }

            return $result;

        } catch ( \Exception $e ) {
            // 检测异常
            $message = "访问方法:微信刷卡支付-订单查询(GetwxPayStatus)异常，" . json_encode ( $e );
            write_log ( $message, "API/api/getWxPayStatus" );
            return "- 5";
        }
    }
}
