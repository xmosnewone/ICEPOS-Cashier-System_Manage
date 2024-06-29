<?php
/**
 * Redis读写hash值的类
 */
namespace app\common\logic;
use app\common\data\RedisService;
class Cache{
    
    //REDIS服务器
    private $_REDIS;

    public function __construct(){
    	$this->redis_instance();
    }
    
    //实例化redis
    private function redis_instance(){
    	$this->_REDIS=new RedisService(C("database.REDIS_HOST"),C("database.REDIS_PORT"),C("database.REDIS_DBNAME"),C("database.REDIS_PASSWORD"),0,1);
    }
    
    //判断是否已连接redis
    public function ping(){
    	$return = null;
    	
    	$return = $this->_REDIS->ping();
    	
    	return  $return;
    }
    
    //判断某个Hash表是否存有数据
    //$name 是hash表的名称
    public function hash_exist($name){
    	$length=$this->_REDIS->hashLen($name);
    	if($length==null||!$length){
    		return false;
    	}else{
    		return $length;
    	}
    }
	
   	/**
	 * 获取hash表的数据
	 * @param $hash string 哈希表名
	 * @param $key mixed 表中要存储的key名 默认为null 返回所有key>value
	 * @param $type int 要获取的数据类型 0:返回所有key 1:返回所有value 2:返回所有key->value
	 */
    public function redis_get($hash,$key=array(),$type=0){
    	return $this->_REDIS->hashGet($hash,$key,$type);
    }
    
	/**
	 * 将key->value写入hash表中
	 * @param $hash string 哈希表名
	 * @param $data array 要写入的数据 array('key'=>'value')
	 */
    public function redis_set($hash,$data){
    	return $this->_REDIS->hashSet($hash,$data);
    }
    
	/**
	 * 删除hash表中的key
	 * @param $hash string 哈希表名
	 * @param $key mixed 表中存储的key名
	 */
    public function redis_delete($hash,$key){
    	return $this->_REDIS->hashDel($hash,$key);
    }
    
    /**
     * 查询hash表中某个key是否存在
     * @param $hash string 哈希表名
     * @param $key mixed 表中存储的key名
     */
    public function hashExists($hash,$key)
    {
    	return $this->_REDIS->hashExists($hash,$key);
    
    }
    
}