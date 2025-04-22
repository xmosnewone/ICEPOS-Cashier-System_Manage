<?php
namespace app\api\controller;
use think\Db;
use think\facade\Request;
use think\facade\Lang;
use think\Controller;
use model\PosOperator;
use model\PosStatus;
/**
 * 接口超类
 * @author xmos
 */
class Super extends Controller {
	
	// 远程访问的客户端IP地址
	protected $remote_ip;
	// 本api服务器地址
	protected $api_server;
	//标记是否已Auth
	protected $isAuth;

	public function __construct() {
		define ( 'IS_GET', Request::instance ()->isGet () );
		define ( 'IS_POST', Request::instance ()->isPost () );
		define ( 'IS_AJAX', Request::instance ()->isAjax () );
		define ( 'CONTROLLER_NAME', Request::instance ()->controller () );
		define ( 'ACTION_NAME', Request::instance ()->action () );
		parent::__construct ();
	}
	
	public function initialize() {
		Lang::load( APP_PATH.'admin/lang/zh-cn.php');
		$_POST = input ( '' );
		$this->remote_ip = get_client_ip ();
		$this->api_server = config ( "api_server" );
	}
	
	/**
	 * 记录所有POST参数
	 */
	public function getVars($metion){
		$message = "来自IP:" . $this->remote_ip . $metion."，参数：access_token:" . $_POST ["access_token"]
					. ",client_id:" . $_POST ["client_id"] . ",username:" . $_POST ["username"] 
					. ",password:" . $_POST ["password"] . ",key:" . $_POST ["key"];
		if (! empty ( $_POST ["branch_no"] )) {
			$message .= ",门店编号:" . $_POST ["branch_no"];
		}
		if (! empty ( $_POST ["pos_id"] )) {
			$message .= ",POS机编号:" . $_POST ["pos_id"];
		}
		$rid = "";
		if (isset ( $_POST ["rid"] ) && ! empty ( $_POST ["rid"] )) {
			$rid = $_POST ["rid"];
			$message .= ",rid:" . $_POST ["rid"];
		}
		$updatetime = "";
		if (isset ( $_POST ["updatetime"] ) && ! empty ( $_POST ["updatetime"] )) {
			$updatetime = $_POST ["updatetime"];
			$message .= ",updatetime:" . $_POST ["updatetime"];
		}
		return $message;
	}

	/**
	 * 接口访问认证.
	 *
	 * @param $parameters input接收的参数        	
	 * @param string $mark 判断是否要检测营业员正确性,1是不需要验证
	 */
	public function Auth($parameters, $mark = "") {
		$this->isAuth=0;
		if (empty ( $parameters ["access_token"] ) || empty ( $parameters ["client_id"] ) || empty ( $parameters ["key"] )) {
			return - 10;
		} else {
			$username = $parameters ["username"];
			$password = $parameters ["password"];
			$access_token = $parameters ["access_token"];
			$client_id = $parameters ["client_id"];
			$token=config("access_token");
			$key = $parameters ["key"];
			if (($client_id != "POS" && $access_token != $token) || ($client_id != "CNS" && $access_token != $token)) {
				return - 20;
			} else {
				$parameters1 = "access_token=" . $access_token . "&client_id=" . $client_id . "&username=" . $username . "&password=" . $password;
				if (strtolower ( md5 ( md5 ( $parameters1 . $access_token ) ) ) != $key) {
					return - 20;
				} else {
					if ($mark == 1) {
						return 1;
					} else {
						$PosOperator = new PosOperator ();
						$model = $PosOperator->field("oper_id")->where ( "oper_id='$username' and oper_pw='$password'" )->find ();
						if (empty ( $model )) {
							return - 20;
						} else {
							$this->isAuth=1;
							return 1;
						}
					}
				}
			}
		}
	}
	
	// POS终端连接
	public function ApiConnect($arr) {
		try {
			$result = $this->Auth ( $arr, 1 );
			//配置项目需要记录访问日志
			$pos_log=config("pos_log");
			if($pos_log&&$result){
				$this->SyncRecord();
			}
		} catch ( \Exception $ex ) {
			$result = - 1;
		}
		return $result;
	}
	
	// 测试连通
	public function testconn() {
		echo $this->ApiConnect ( $_POST );
		exit ();
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

	/**
	 * POS终端当天同步记录
	 */
	public function SyncRecord() {
		
		$branchNo = input ( "branch_no" );
		$posid = input ( "posid" );
		if(empty($posid)&&empty($branchNo)){
			return;
		}
		
		if(empty($branchNo)){
			$PosStatus=new PosStatus();
			$pos=$PosStatus->getone($posid);
			$branchNo=$_POST['branch_no']=$pos['branch_no'];
		}else if(empty($posid)){
			$PosStatus=new PosStatus();
			$pos=$PosStatus->getone_bybranchno($branchNo);
			$posid=$_POST['posid']=$pos['posid'];
		}
		
		//根据日志文件判断是否已记录
		$this->createDayLog($posid);
		
		//必须要通过账户验证的可访问数据库
		if(!$this->isAuth){
			return;
		}

		$this->addSyncRecord($posid,$branchNo);
	}

	public function addSyncRecord($posid,$branchNo){
        $data = [ ];
        $data ['posid'] = $posid;
        $data ['branch_no'] = $branchNo?$branchNo:'';
        $data['synctime'] = time ();
        M ( "pos_sync" )->insertGetId( $data );
    }
	
	/**
	 * 判断当日有没有创建日志
	 */
	public function createDayLog($posid){
		return pos_log($posid,"SHOP:".$posid."--URL--".ACTION_NAME."--httpQuery:".http_build_query($_POST));
	}
	/**
	 * 统一返回json数据格式
	 */
	public function ajaxReturn($data){
		return json_encode($data);
	}
}
