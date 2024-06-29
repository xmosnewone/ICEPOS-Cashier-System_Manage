/**
 * admdin 模块公共JS文件
 */
function openWin(_title,url,width,height){
				var layerIndex=layer.open({
	                type: 2,
	                title: _title,
	                shade: 0.1,
	                area: [width,height],
	                content: url
	            });
				return layerIndex;
}
//弹出html内容对话框
function openWinHtml(_title,_content,width,height,_success,_yes){
	var layerIndex=layer.open({
        type: 1,
        title: _title,
        shade: 0.1,
        area: [width,height],
        content: _content,
        success:function(){
        	_success();
        },
        btn:["确定"],
        yes:function(index, layero){
        	_yes();
        }
    });
	return layerIndex;
}

//获取父窗口的Tab内容高度
function getParentWinHeight(jQuery){
	return jQuery(".layui-body #content .layui-tab-content",window.parent.document).height();
}
//获取父窗口的Tab内容宽度
function getParentWinWidth(jQuery){
	return jQuery(".layui-body #content .layui-tab-content",window.parent.document).width();
}
//重建树
function rebuildTree(dtree,treeId,_url,treeHeight){
	var DTree = dtree.reload(treeId,{
		elem: "#"+treeId,
		height:treeHeight,
		initLevel: "2", //默认展开层级为1
		method: 'get',
		url: _url
	});
}
//正数数字判断
function isNumber(val) {
    var regPos = /^\d+(\.\d+)?$/; //非负浮点数
    var regNeg = /^(-(([0-9]+\.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*\.[0-9]+)|([0-9]*[1-9][0-9]*)))$/; //负浮点数
    if (regPos.test(val) || regNeg.test(val)) {
        return true;
    } else {
        return false;
    }
}
//清除隐藏表单元素值
window.resetForm=function(){
	layui.jquery("input:hidden").val("");
}