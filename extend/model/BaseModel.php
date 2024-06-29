<?php
//基础模型表
namespace model;
use think\Model;
use think\Db;
use validate\ModelValidate;

class BaseModel extends Model
{
	//表前缀
	public $prefix;
	//表名(含前缀)
	protected $table;
	//表名(不含前缀)
	protected $name;
	//错误代码
	protected $error = 0;
	//规则
	protected $rule = [];
	//返回信息
	protected $msg = [];
	//验证器
	protected $Validate;

	//覆盖初始化
	public function __construct($data = [])
	{
		parent::__construct($data);
		$db_config=config("database.");
		$this->prefix=$db_config['prefix'];
		if(empty($this->table)){
			$this->table=$this->prefix.$this->name;
		}
	}
	
    //返回数据表名(含前缀)
    public function tableName()
    {
    	if(!empty($this->table)){
    		return $this->table;
    	}else if(!empty($this->name)){
    		return $this->prefix.$this->name;
    	}else{
    		return false;
    	}
    }
    
    /**
     * 获取空模型
     */
    public function getEModel($tables)
    {
    	$rs = Db::query('show columns FROM `' . config('database.prefix') . $tables . "`");
    	$obj = [];
    	if ($rs) {
    		foreach ($rs as $key => $v) {
    			$obj[ $v['Field'] ] = $v['Default'];
    			if ($v['Key'] == 'PRI')
    				$obj[ $v['Field'] ] = 0;
    		}
    	}
    	return $obj;
    }
    
    public function save($data = [], $where = [], $sequence = null)
    {
    	$data = $this->htmlClear($data);
    	$retval = parent::save($data, $where, $sequence);
    	if (!empty($where)) {
    		//表示更新数据
    		if ($retval == 0) {
    			if ($retval !== false) {
    				$retval = 1;
    			}
    		}
    	}
    	return $retval;
    }
    
    public function ihtmlspecialchars($string)
    {
    	if (is_array($string)) {
    		foreach ($string as $key => $val) {
    			$string[ $key ] = $this->ihtmlspecialchars($val);
    		}
    	} else {
    		$string = preg_replace('/&amp;((#(d{3,5}|x[a-fa-f0-9]{4})|[a-za-z][a-z0-9]{2,5});)/', '&\1',
    				str_replace(array( '&', '"', '<', '>' ), array( '&amp;', '&quot;', '&lt;', '&gt;' ), $string));
    	}
    	return $string;
    }
    
    protected function htmlClear($data)
    {
    	$rule = $this->rule;
    	$info = empty($rule) ? $this->Validate : $rule;
    	foreach ($data as $k => $v) {
    		if (!empty($info)) {
    			if (is_array($info)) {
    				$is_Specialchars = $this->is_Specialchars($info, $k);
    				// 数据对象赋值
    				if ($is_Specialchars) {
    					$data[ $k ] = $this->ihtmlspecialchars($v);
    				} else {
    					$data[ $k ] = $v;
    				}
    			}
    		}
    	}
    	return $data;
    }
    
    /**
     * 判断当前k 是否在数组的k值中
     * @param array $rule
     * @param string $k
     */
    protected function is_Specialchars($rule, $k)
    {
    	$is_have = true;
    	foreach ($rule as $key => $value) {
    		if ($key == $k) {
    			if (strcasecmp($value, "no_html_parse") != 0) {
    				$is_have = true;
    			} else {
    				$is_have = false;
    			}
    		}
    	}
    	return $is_have;
    }
    
    /**
     * 列表查询
     *
     * @param int $page_index
     * @param number $page_size
     * @param string $order
     * @param string $where
     * @param string $field
     */
    public function pageQuery($page_index, $page_size, $condition, $order, $field)
    {
    	$order = trim($order);
    	$count = $this->where($condition)->count();
    	if ($page_size == 0) {
    		$list = $this->field($field)
    		->where($condition)
    		->order($order)
    		->select()
    		->toArray();
    		$page_count = 1;
    	} else {
    		$start_row = $page_size * ($page_index - 1);
    		$list = $this->field($field)
    		->where($condition)
    		->order($order)
    		->limit($start_row . "," . $page_size)
    		->select()
    		->toArray();
    		if ($count % $page_size == 0) {
    			$page_count = $count / $page_size;
    		} else {
    			$page_count = (int) ($count / $page_size) + 1;
    		}
    	}
    	return array(
    			'data' => $list,
    			'total_count' => $count,
    			'page_count' => $page_count
    	);
    }
    
    /**
     * 获取一定条件下的列表
     */
    public function getQuery($condition = [], $field = '*', $order = '', $group = '')
    {
    	$order = trim($order);
    	if (empty($group)) {
    		$list = $this->field($field)->where($condition)->order($order)->select();
    	} else {
    		$list = $this->field($field)->where($condition)->group($group)->order($order)->select();
    	}
    
    	return $list;
    }
    
    /**
     * 获取关联查询列表
     *
     * @param object $viewObj 对应view对象
     * @param int $page_index
     * @param int $page_size
     * @param array $condition
     * @param string $order
     * @return array
     */
    public function viewPageQuery($viewObj, $page_index, $page_size, $condition=[], $order)
    {
    	if ($page_size == 0) {
    		$list = $viewObj->where($condition)
    		->order($order)
    		->limit(0, 1000000)
    		->select();
    	} else {
    		$start_row = $page_size * ($page_index - 1);
    		$list = $viewObj->where($condition)
    		->order($order)
    		->limit($start_row . "," . $page_size)
    		->select();
    	}
    	return $list;
    }
    
    /**
     * 获取关联查询数量
     * @param object $viewObj
     * @param array $condition
     * @param string $where_sql
     */
    public function rowsCount($viewObj, $condition, $where_sql)
    {
    	$count = $viewObj->where($condition)->where($where_sql)->limit(0, 1000000)->count();
    	return $count;
    }
    
    /**
     * 设置关联查询返回数据格式
     *
     * @param array $list
     *            查询数据列表
     * @param int $count
     *            查询数据数量
     * @param int $page_size
     *            每页显示条数
     * @return array
     */
    public function setReturnList($list, $count, $page_size)
    {
    	if ($page_size == 0) {
    		$page_count = 1;
    	} else {
    		if ($count % $page_size == 0) {
    			$page_count = $count / $page_size;
    		} else {
    			$page_count = (int) ($count / $page_size) + 1;
    		}
    	}
    	return array(
    			'data' => $list,
    			'total_count' => $count,
    			'page_count' => $page_count
    	);
    }
    
    /**
     * 获取单条记录的基本信息
     */
    public function getInfo($condition = '', $field = '*')
    {
    	$info = Db::table($this->table)->where($condition)
    	->field($field)
    	->find();
    	return $info;
    }
    
    /**
     * 查询数据的记录数量
     */
    public function getCount($condition)
    {
    	$count = Db::table($this->table)->where($condition)->count();
    	return $count;
    }
    
    /**
     * 查询条件数量
     * @param array $condition
     * @param string $field
     */
    public function getSum($condition, $field)
    {
    	$sum = Db::table($this->table)->where($condition)->sum($field);
    	if (empty($sum)) {
    		return 0;
    	} else
    		return $sum;
    }
    
    /**
     * 查询数据最大值
     * @param array $condition
     * @param string $field
     * @return number
     */
    public function getMax($condition, $field)
    {
    	$max = Db::table($this->table)->where($condition)->max($field);
    	if (empty($max)) {
    		return 0;
    	} else
    		return $max;
    }
    
    /**
     * 查询数据最小值
     * @param array $condition
     * @param string $field
     * @return number
     */
    public function getMin($condition, $field)
    {
    	$min = Db::table($this->table)->where($condition)->min($field);
    	if (empty($min)) {
    		return 0;
    	} else
    		return $min;
    }
    
    /**
     * 查询数据均值
     * @param array $condition
     * @param int
     */
    public function getAvg($condition, $field)
    {
    	$avg = Db::table($this->table)->where($condition)->avg($field);
    	if (empty($avg)) {
    		return 0;
    	} else
    		return $avg;
    }
    
    /**
     * 查询第一条数据
     * @param array $condition
     */
    public function getFirstData($condition, $order)
    {
    	$data = Db::table($this->table)->where($condition)->order($order)
    	->limit(1)->select();
    	if (!empty($data)) {
    		return $data[0];
    	} else
    		return '';
    }
    
    /**
     * 修改表单个字段值
     * @param int $pk_id
     * @param string $field_name
     * @param string $field_value
     */
    public function ModifyTableField($pk_name, $pk_id, $field_name, $field_value)
    {
    	$data = array(
    			$field_name => $field_value
    	);
    	$res = $this->save($data, [ $pk_name => $pk_id ]);
    	return $res;
    }
    
    /**
     * 获取某个字段值(数组)
     * @param array $condition
     * @param string $name
     * @param string $order
     */
    public function getColumn($condition, $name, $order = '')
    {
    	$data = Db::table($this->table)->where($condition)->order($order)->column($name);
    	return $data;
    }
    
    /**
     * 获取某个字段值（值）
     * @param array $condition
     * @param string $name
     * @param string $order
     */
    public function getValue($condition, $name, $order = '')
    {
    	$data = Db::table($this->table)->where($condition)->order($order)->value($name);
    	return $data;
    }
    
    /**
     * 数据库开启事务
     */
    public function startTrans()
    {
    	Db::startTrans();
    }
    
    /**
     * 数据库事务提交
     */
    public function commit()
    {
    	Db::commit();
    }
    
    /**
     * 数据库事务回滚
     */
    public function rollback()
    {
    	Db::rollback();
    }
   
   /**
    * 验证器
    */
    public function validate($data,$rule=[],$message=[])
    {
    	
    	$vdata['rule']=$rule;
    	$vdata['message']=$message;
    	$validate=new ModelValidate($vdata);
    	
    	$result = $validate->check($data);
    	
    	if(!$result){
    		$error= $validate->getError();
    		return ['error'=>$error,'status'=>false];
    	}
    	
    	return ['error'=>'','status'=>true];
    
    }
}