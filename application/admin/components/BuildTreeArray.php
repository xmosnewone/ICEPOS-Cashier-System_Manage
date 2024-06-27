<?php
namespace app\admin\components;
/**
 * 由一个带fid的数组生成一个带children的树形数组 
 * 专为EasyUI的Tree的json格式设计 
 * 
 */
class BuildTreeArray {

    private $idKey = 'id'; //主键的键名  
    private $fidKey = 'fid'; //父ID的键名  
    private $root = 0; //最顶层fid  
    private $data = array(); //源数据  
    private $treeArray = array(); //属性数组  

    function __construct($data, $idKey, $fidKey, $root) {
        if ($idKey)
            $this->idKey = $idKey;
        if ($fidKey)
            $this->fidKey = $fidKey;
        if ($root)
            $this->root = $root;
        if ($data) {
            $this->data = $data;
            $this->getChildren($this->root);
        }
    }

    /**
     * 获得一个带children的树形数组 
     * @return multitype: 
     */
    public function getTreeArray() {
        //去掉键名  
        return array_values($this->treeArray);
    }

    /**
     * @param int $root 父id值 
     * @return null or array 
     */
    private function getChildren($root) {
        $children =[];
        foreach ($this->data as &$node) {
            if ($root == $node[$this->fidKey]) {
                $node['children'] = $this->getChildren($node[$this->idKey]);
                $node['open'] = $node['children'] == 0 ? false : true;
                $children[] = $node;
            }
            //只要一级节点  
            if ($this->root == $node[$this->fidKey]) {
                //@2024 商品分类需显示无下级分类的商品分类一级分类而修改
               //if(count($node['children'])>0){
                   $this->treeArray[$node[$this->idKey]] = $node;
              // }
            }
        }
        return $children;
    }

}
