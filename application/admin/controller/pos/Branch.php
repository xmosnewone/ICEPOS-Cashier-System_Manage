<?php
namespace app\admin\controller\pos;
use app\admin\controller\Super;
use model\PosBranch;
use model\PosStatus;
use model\BaseModel;

/**
 *	门店仓库
 */
class Branch extends Super
{
    private $trade_type=[
        1=>'仓库',
        2=>'加盟店',
        3=>'总部',
    ];
    public function branchList(){
      $page=input('page');
      $limit=input('limit');
      if($page){
      	$where=[];
      	$branch_no=input("branch_no");
      	$branch_name=input("branch_name");
      	$branch_man=input("branch_man");
      	$branch_tel=input("branch_tel");
      	if(!empty($branch_no)){
      		$where[]=['branch_no','like',"%$branch_no%"];
      	}
      	if(!empty($branch_name)){
      		$where[]=['branch_name','like',"%$branch_name%"];
      	}
      	if(!empty($branch_man)){
      		$where[]=['branch_man','like',"%$branch_man%"];
      	}
      	if(!empty($branch_tel)){
      		$where[]=['branch_tel','like',"%$branch_tel%"];
      	}
        $model = new PosBranch();
        $total=$model->where($where)->count();
        $start=($page-1)*$limit;
        $list = $model->where($where)->limit($start,$limit)->order("init_date desc")->select()->toArray();
        foreach($list as $k=>$value){
            $list[$k]['trade_type']=$this->trade_type[$value['trade_type']];
        }
        return listJson(0,'',$total,$list);
      }
      else{
        return $this->fetch("pos/branchlist");
      }
    }
	
    //添加门店仓库
    public function branchAdd(){
        if(IS_POST){
          $model=new PosBranch();
          $content=array();
          $content['branch_no']=input('branch_no');
          if($content['branch_no']==''){
            $return['code']=false;
            $return['errMsg']='门店编码不能为空';
            return $return;
          }

          $num=$model->where("branch_no='{$content['branch_no']}'")->count();
          if($num>0){
            $return['code']=false;
            $return['msg']=lang("StoreCodeExists");
            return $return;
          }
          $content['branch_name']=input('branch_name');
          if($content['branch_name']==''){
            $return['code']=false;
            $return['msg']=lang("store_name_empty");
            return $return;
          }
          $content['trade_type']=input('trade_type');
          $content['price_type']=input('price_type');
          $content['branch_man']=input('branch_man');
          $content['branch_tel']=input('branch_tel');
          $content['branch_fax']=input('branch_fax');
          $content['branch_email']=input('branch_email');
          $content['branch_mj']=input('branch_mj');
          $content['address']=input('address');
          $content['lon']=input('lon');
          $content['lat']=input('lat');
          $content['other1']=input('other1');
          $content['authorcode']=input('authorcode');
          $content['wechat']=input('wechat');
          $content['upername']=input('upername');
          $content['wechat_appid']=input('wechat_appid');
          $content['wechat_secret']=input('wechat_secret');
          $content['wechat_merchantid']=input('wechat_merchantid');
          $content['wechat_paykey']=input('wechat_paykey');
          $content ['wechat_pay_qrcode'] = input ( 'wechat_pay_qrcode' );
          $content['alipay_appid']=input('alipay_appid');
          $content['alipay_public_key']=input('alipay_public_key');
          $content['alipay_private_key']=input('alipay_private_key');
          $content ['alipay_pay_qrcode'] = input ( 'alipay_pay_qrcode' );
          $content['logo']=input('logo');
          $content ['use_credit'] = input ( 'use_credit' )?input ( 'use_credit' ):'0';
          $content ['credit_money'] = input ( 'credit_money' )?intval(input ( 'credit_money' )):'0';
          $content ['use_wechatpay'] = input ( 'use_wechatpay' )?input ( 'use_wechatpay' ):'0';
          $content ['use_alipay'] = input ( 'use_alipay' )?input ( 'use_alipay' ):'0';

          if($model->save($content)){
          	//添加funtion-pos端功能键
          	$model->addFunction($content['branch_no']);
            $return['code']=true;
            $return['msg']=lang("update_success");
          }
          else{
            $return['code']=false;
            $return['msg']=lang("update_error");
          }
          return $return;
        }
        else{
                $this->assign("image_size",config("image_size"));
         return $this->fetch('pos/branchadd');
        }
    }

    //编辑门店仓库
    public function branchEdit(){

        $branch_no=input('branch_no');
        $id=input('id');
        $model=new PosBranch();
        if (! empty ( $id )) {
        	$result = $model->getoneWhere ( [
        			'id' => $id
        	] );
        }
        if(IS_POST){
        	
			$content = array ();
			$content ['branch_no'] = $branch_no;
			$content ['branch_name'] = input ( 'branch_name' );
            $content ['trade_type'] = input ( 'trade_type' );
			$content ['price_type'] = input ( 'price_type' );
			$content ['branch_man'] = input ( 'branch_man' );
			$content ['branch_tel'] = input ( 'branch_tel' );
			$content ['branch_fax'] = input ( 'branch_fax' );
			$content ['branch_email'] = input ( 'branch_email' );
			$content ['branch_mj'] = input ( 'branch_mj' );
			$content ['address'] = input ( 'address' );
			$content ['other1'] = input ( 'other1' );
			$content ['lon'] = input ( 'lon' );
			$content ['lat'] = input ( 'lat' );
			$content ['authorcode'] = input ( 'authorcode' );
			$content ['wechat'] = input ( 'wechat' );
			$content ['upername'] = input ( 'upername' );
			$content ['wechat_appid'] = input ( 'wechat_appid' );
			$content ['wechat_secret'] = input ( 'wechat_secret' );
			$content ['wechat_merchantid'] = input ( 'wechat_merchantid' );
			$content ['wechat_paykey'] = input ( 'wechat_paykey' );
            $content ['wechat_pay_qrcode'] = input ( 'wechat_pay_qrcode' );
            $content ['wechat_apiclient_cert'] = input ( 'wechat_apiclient_cert' );
            $content ['wechat_apiclient_key'] = input ( 'wechat_apiclient_key' );
			$content ['alipay_appid'] = input ( 'alipay_appid' );
			$content ['alipay_public_key'] = input ( 'alipay_public_key' );
			$content ['alipay_private_key'] = input ( 'alipay_private_key' );
            $content ['alipay_pay_qrcode'] = input ( 'alipay_pay_qrcode' );
            $content['logo']=input('logo');
            $content ['use_credit'] = input ( 'use_credit' )?input ( 'use_credit' ):'0';
            $content ['credit_money'] = input ( 'credit_money' )?intval(input ( 'credit_money' )):'0';
            $content ['use_wechatpay'] = input ( 'use_wechatpay' )?input ( 'use_wechatpay' ):'0';
            $content ['use_alipay'] = input ( 'use_alipay' )?input ( 'use_alipay' ):'0';
			// 转换经纬度
			/*if ($content ['lon'] != '' && $content ['lat'] != '') {
				$lnglat = $content ['lon'] . "," . $content ['lat'];
				$url = "http://api.map.baidu.com/geoconv/v1/?coords=" . $lnglat . "&from=1&to=5&ak=" . BAIDU_AKEY;
				$translate_latlng = file_get_contents ( $url );
				$res = json_decode ( $translate_latlng, true );
				if (strval ( $res ['status'] ) == "0") { // 0 表示成功
					$content ['baidu_location'] = $res ['result'] [0] ['x'] . "," . $res ['result'] [0] ['y'];
				}
			}*/
			
			$posModel=new PosBranch();
			if ($posModel->save ($content,['id'=>$id])) {
                //删除该门店快捷键
                $posModel->delFunction($content['branch_no']);
                //添加funtion-pos端功能键
                $posModel->addFunction($content['branch_no']);

				$return ['code'] = true;
				$return['msg']=lang("update_success");
			} else {
				$return ['code'] = false;
				$return['msg']=lang("update_error");
			}
			return $return;
        }
        else{
            $this->assign("image_size",config("image_size"));
            $this->assign("one",$result);
            return $this->fetch("pos/branchadd");
        }
    }
    
    //删除门店仓库
    public function deleteBranch(){
    	
    	$branch_no=input('branch_no');
    	$model=new PosBranch();
    	$result= $model->getone($branch_no);
    	if($result){
    		//查找对应的POS机，做提示
    		$statusModel=new PosStatus();
    		$posStatus=$statusModel->getone_bybranchno($result->branch_no);
    		if($posStatus){
    			$return['code']=false;
    			$return['msg']='该门店已绑定POS机，请先解绑';
    			return $return;
    		}else{
    			$model->deleteOne($branch_no);
    			$model->delFunction($branch_no);
                if($result['wechat_pay_qrcode']!=''){
                    @unlink(".".$result['wechat_pay_qrcode']);
                }
                if($result['alipay_pay_qrcode']!=''){
                    @unlink(".".$result['alipay_pay_qrcode']);
                }
                if($result['logo']!=''){
                    @unlink($result['logo']);
                }
    			$return['code']=true;
    			$return['msg']=lang("update_success");
    			return $return;
    		}
    		
    	}else{
    			$return['code']=false;
    			$return['msg']=lang("no_article");
    			return $return;
    	}
    }
    
    //批量删除门店仓库
    public function batchDeleteBranch(){
    	 
    	$branch_no=input('branch_no');
    	$model=new PosBranch();
    	
    	$branchs=explode(",",$branch_no);
    	//查找对应的POS机，做提示
    	$statusModel=new PosStatus();
    	$posStatus=$statusModel->getAllBywhere("branch_no in (".simplode($branchs).")");
    	if($posStatus){
    			$return['code']=false;
    			$return['msg']='门店已绑定POS机，请先解绑';
    			return $return;
    	}else{
    			
    			foreach($branchs as $no){
                    $result= $model->getone($no);
                    if($result){
                        if($result['wechat_pay_qrcode']!=''){
                            @unlink(".".$result['wechat_pay_qrcode']);
                        }
                        if($result['alipay_pay_qrcode']!=''){
                            @unlink(".".$result['alipay_pay_qrcode']);
                        }
                        if($result['logo']!=''){
                            @unlink($result['logo']);
                        }
                    }
    				$model->deleteOne($no);
    				$model->delFunction($no);

    			}
    			$return['code']=true;
    			$return['msg']=lang("update_success");
    			return $return;
    	}
    
    }

    //继承uploadFile
    public function upload()
    {
        $result=$this->uploadImage("file",[]);
        if($result['status']==1){
            $result['path']=substr($result['path'], 1);
        }
        return $result;
    }

    //上传证书文件
    public function uploadCert()
    {
        $input="file";//上传文件的名称
        $branch_no=$_POST['branch_no'];//门店编号
        if(empty($_FILES[$input])){
            return ['status'=>0,'msg'=>'file empty'];
        }
        if(empty($branch_no)){
            return ['status'=>0,'msg'=>'branch no empty'];
        }

        $branch_no=strtolower($branch_no);
        $folder="../cert/".$branch_no."/";
        if(!file_exists($folder)){
            //创建0777权限的文件夹
            mk_dir($folder);
        }

        //判断PHP环境的操作系统
        $os="UNIX";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $os="WIN";
        }

        $file = request()->file($input);
        $info = $file->move($folder);
        $result=[];
        if($info){
            // 成功上传后 获取上传信息
            $result['status']=1;
            $result['extension']=$info->getExtension();
            //完整上传路径  必须是绝对路径
            if($os=="WIN"){
                $result['path']=ROOT_PATH."cert"."\\".$branch_no."\\". $info->getSaveName();
            }else{
                $result['path']=ROOT_PATH."cert"."/".$branch_no."/".str_replace("\\", "/", $info->getSaveName());
            }
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

