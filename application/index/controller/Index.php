<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
##@测试前台
class Index extends Controller
{
    public function index()
    {

        $list = Db::query('show table status');
        foreach($list as $value){
                $name=$value['Name'];
                $comment=$value['Comment'];
                $fields=Db::query('SHOW FULL COLUMNS FROM `' . $name . '`');
                echo $name.":".$comment."<br>";
                foreach($fields as $fval){
                  echo  "Field Name:".$fval['Field'];
                  echo "<br>Field Comment:".$fval['Comment'];
                  echo  "<br>Field Type:".$fval['Type'];
                    echo  "<br>----------------<br>";
                }
                echo '##############################################################';
        }
   	}
}
