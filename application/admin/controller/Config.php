<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use app\common\service\Helper;

use model\SystemConfig;
use model\SystemLog;
use think\Db;
/**
 * 系统设置
 *
 */
class Config extends Super {

    //配置显示页面
    public function index() {

        $system_config = new SystemConfig();
        $config = $system_config->select ();
        foreach ( $config as $key => $value ) {
            $web_config [$value ['key']] = stripslashes ( $value ['value'] );
        }

        $this->assign ( 'web_config', $web_config );
        return $this->fetch ( "config/index" );
    }

    //保存配置
    public function set_config() {

        $config = input ("post.",'','trim');
        $configDb=new SystemConfig();
        foreach($config as $key=>$value){
            $system_config = new SystemConfig();
            $system_config->key=$key;
            $system_config->value=$value;
            $configDb->addKey($system_config);
        }

        $this->addLog($config);
        //文件缓存
        config_cache ();

        return ["code" => true, "msg" => lang("save_success")];
    }

    //添加日志
    private function addLog($config){
        $uid=$this->_G['uid'];
        $loginname=$this->_G['username'];
        $time=$this->_G['time'];
        $log='';
        foreach ( $config as $key => $value ) {
            $log.=$key.":".$value.",";
        }
        $logtxt=$log;
        $systemLog=new SystemLog();
        return $systemLog->save([
            'uid'=>$uid,
            'loginnam'=>$loginname,
            'logtxt'=>$logtxt,
            'add_time'=>$time
        ]);
    }
}
