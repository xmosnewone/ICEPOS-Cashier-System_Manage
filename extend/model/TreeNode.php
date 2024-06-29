<?php
//sys_manager表
namespace model;
use model\Item_cls;

class TreeNode {

    public $name;
    public $id;
    public $isParent;
    public $pId;
    public $open = false;
    public $children;

    public function GetItemClsForControls() {
    	$Item_cls=new Item_cls();
        $result = $Item_cls->select();
        $res = array();
        $treeRoot = new TreeNode();
        $treeRoot->id = "";
        $treeRoot->title = lang("alltypes");
        $treeRoot->isParent = TRUE;
        $treeRoot->parentId = -1;
        $treeRoot->open = TRUE;
        array_push($res, $treeRoot);
        if (!empty($result)) {
            foreach ($result as $v) {
                $tree = new TreeNode();
                $tree->id = $v->item_clsno;
                $tree->title = $v->item_clsname;
                $tree->parentId = $v->cls_parent;
                array_push($res, $tree);
            }
        }
        return $this->build_tree($res, -1);
    }

    function findChild(&$arr, $id) {
        $childs = array();
        foreach ($arr as $k => $v) {
            if ($v->parentId === $id) {
                $childs[] = $v;
            }
        }
        return $childs;
    }

    function build_tree($rows, $root_id) {
        $childs = $this->findChild($rows, $root_id);
        if (empty($childs)) {
            return null;
        }
        foreach ($childs as $k => $v) {
            $rescurTree = $this->build_tree($rows, $v->id);
            if (null != $rescurTree) {
                $childs[$k]->isParent = true;
                $childs[$k]->last=false;
                $childs[$k]->children = $rescurTree;
            } else {
                $childs[$k]->isParent = false;
                $childs[$k]->children = "";
                $childs[$k]->last=true;
            }
        }
        return $childs;
    }

}
