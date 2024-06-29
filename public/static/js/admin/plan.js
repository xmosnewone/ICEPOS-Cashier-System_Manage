/**
 * 促销方案
 * @author xmos
 * @NoModify =true 已审核不能修改,false则可以编辑
 * @input hidResult==Send BARCODEN... 是买满N元加M元送商品 和 买满多少个商品送赠品
 */
var currentStep=0;
var vipTypeNo='ALL';
var rangeChecked='';
layui.use(['form','jquery','element','laydate','table','select'],function(){
    let form = layui.form;
    let $ = layui.jquery;
    let table=layui.table;
    let element=layui.element;
    let laydate=layui.laydate;
    
	//加载完成显示所有标签页
	$("#tabbox").show();
	
	//时间选择+++++++++++++++++++++++++++
	laydate.render({ 
		  elem: '#planDate'
		  ,range: ['#BeginDate', '#EndDate']
	});
	laydate.render({ 
		  elem: '#planTime'
		  ,range: ['#BeginSecond', '#EndSecond']
		  ,type:'time'
	});
	//+++++++++++++++++++++++++++
	
	//会员等级选择
	form.on('select(ViptypeNo)', function(data){
		  vipTypeNo=data.value;
	});
	
	//关闭方案弹窗
	window.closePlan=function(){
		parent.layer.close(parent.layer.getFrameIndex(window.name));//关闭当前页
	}
	
	//显示分店选择框
	window.showMulBranchs=function(){
		if(NoModify){
			return;
		}
		_layerIndex=openWin("分店仓库选择",mulbranchs_url,"800px","500px");
	}
	
	//分店选择后的回调函数
	window.callBackMulBranch=function(data){
		
		var arr=new Array();
		var arrName=new Array();
		for(var i in data){
			arr.push(data[i]['branch_no']);
			arrName.push(data[i]['branch_name']);
		}
		
		$("#BranchNo").val(arr.join(","));
		$("#hidBranchNos").val(arr.join(","));
		$("#BranchName").val(arrName.join("|"));
		
		if(_layerIndex!=null){
			layer.close(_layerIndex);
		}
		_layerIndex=null;
	}
	
	//上一步按钮
	window.basicPrev=function(){
		
		currentStep-=1;
		if(currentStep==0){
			element.tabChange('plancontent', 'basic');
			$("#saveBtn").hide();
			$("#prevBtn").hide();
			//显示下一步按钮
            $("#nextBtn").show();
		}else if(currentStep==1){
			element.tabChange('plancontent', 'plan');
			$("#saveBtn").hide();
            //显示下一步按钮
            $("#nextBtn").show();
		}

		return;
	}
	
	//基本信息-下一步按钮
	window.basicNext=function(){
          var isok = true;
          isok = window.checkFirstStep();
          if (!isok)
          {
              return false;
          }
          
          if (isok === true && (currentStep<=0))
          {
        	  
              $("#li1").attr("style", "");
              $("#li2").attr("style", "");
              //切换到第二个选项卡
              element.tabChange('plancontent', 'plan');
              currentStep=1;
              //显示下一步按钮
              $("#nextBtn").show();
              //显示上一步按钮
              $("#prevBtn").show();
              
          }else if(isok === true && currentStep==1){
        	  //切换到第三个选项卡
              element.tabChange('plancontent', 'detail');
              currentStep=2;
              //显示下一步按钮
              $("#nextBtn").hide();
              //显示保存按钮
              $("#saveBtn").show();
              //显示上一步按钮
              $("#prevBtn").show();
              
              $("#hidChange").val("0");
              
              //加载明细表格
              window.initDetailTable();
              
              //判断是否赠送商品，显示赠送商品表格
              window.showSend();

          }
	}
	
	//检查第一步
	window.checkFirstStep=function(){
        var weeks = $("input[name='week']");
        var check = 0;
        var start = $("#BeginDate").val();
        var end = $("#EndDate").val();
        var startSecond = $("#BeginSecond").val();
        var endSecond = $("#EndSecond").val();
        $.each(weeks, function(i, item) {
            if ($(this).attr("checked") !== "checked")
            {
                check += 1;
            }
        });
        if ($.trim($("#PlanName").val()).length === 0)
        {
            $("#PlanName").focus();
            layer.msg("方案名称不能为空");
            //切换到第一个选项卡
            element.tabChange('plancontent', 'basic');
            return false;
        }
        else if ($.trim($("#PlanMemo").val()).length === 0)
        {
            $("#PlanMemo").focus();
            layer.msg("方案摘要不能为空");
            //切换到第一个选项卡
            element.tabChange('plancontent', 'basic');
            return false;
        }
        else if (startSecond.valueOf() > endSecond.valueOf())
        {
            layer.msg("促销时段开始时间不能晚于促销时段结束时间");
            //切换到第一个选项卡
            element.tabChange('plancontent', 'basic');
            return false;
        }
        else if (start.valueOf() > end.valueOf())
        {
            layer.msg("促销日期开始时间不能晚于促销日期结束时间");
            //切换到第一个选项卡
            element.tabChange('plancontent', 'basic');
            return false;
        }
        else if (weeks.length === check)
        {
            layer.msg("促销日期开始时间不能晚于促销日期结束时间");
            //切换到第一个选项卡
            element.tabChange('plancontent', 'basic');
            return false;
        }
        else if ($.trim($("#BranchNo").val()).length === 0)
        {
            layer.msg("请选择促销门店");
            //切换到第一个选项卡
            element.tabChange('plancontent', 'basic');
            return false;
        }
        
            return true;
	}
	
	element.on('tab(plancontent)', function(data){
		//得到当前Tab的所在下标
		var TabIndex=data.index;
		currentStep=TabIndex;
		//基础信息
		if(currentStep<=0){
			 //隐藏保存按钮
            $("#saveBtn").hide();
            //隐藏上一步按钮
            $("#prevBtn").hide();
            //显示下一步按钮
            $("#nextBtn").show();
            
		}else if(currentStep==1){
			 //隐藏保存按钮
            $("#saveBtn").hide();
            //隐藏上一步按钮
            $("#prevBtn").show();
            //显示下一步按钮
            $("#nextBtn").show();
		}else{
			//显示下一步按钮
            $("#nextBtn").hide();
            //显示保存按钮
            $("#saveBtn").show();
            //显示上一步按钮
            $("#prevBtn").show();
            
            $("#hidChange").val("0");
		}
		
	});
	//#################促销模式切换选择##################################
	//赠送商品明细
	var send_columns = [[
          {title: "行号", field: "rowIndex", width: 100},
          {title: "货号", field: "BARCODEN", width: 150, style:'background-color: #fff6f0;',edit:'text'},
          {title: "品名", field: "item_name", width: 120},
          {title: "单位", field: "unit_no", width: 100},
          {title: "数量", field: "RN",width: 150, style:'background-color: #fff6f0;',edit:'text'},
          {title: "售价", field: "sale_price", width: 120},
          {title: "进价", field: "price", width: 120}
      ]];
	
	//促销模式选择设置
	window.ChangeModel=function(model){
        $("#hidModel").val(model.toLowerCase());
        switch (model.toLowerCase())
        {
			case "p":
		        $("#hidRange").val("I");
		        break;
		    case "f":
		        $("#hidRange").val("A");
		        break;
		    default:
		        $("#hidRange").val("A");
		        break;
		}
        
        //设置模式第一个子项目为选中
        var ruleObj = $("input[name='" + model.toLowerCase() + "']").eq(0);
    	ruleObj.prop("checked", true);
    	
        //范围选择第一个选项的值去除rdo作为隐藏规则的值
		$("#hidRuleno").val($("input[name='" + model.toLowerCase() + "']").val().toString().replace("rdo", ""));
		
	}
	
	//促销范围变更
	//model 是已选择模式的值  val是促销范围各子项目的值
	window.ChangeRange=function(model, range_val){
		//设置模式第一个子项目为选中
        var ruleObj = $("input[name='" + model.toLowerCase() + "']").eq(0);
        	ruleObj.prop("checked", true);
        $("#hidRuleno").val(ruleObj.val().toString().replace("rdo", ""));
        $("#hidRange").val(range_val);
        form.render('radio');
	}
	
	//查询系统隐藏规则并设置隐藏值
	window.SetHidden=function(){
		$.ajax({
            url: path+"/getrule",
            type: "POST",
            data: {
                rule_no: $("#hidRuleno").val(),
                range_flag: $("#hidRange").val()
            },
            dataType: "json",
            async: false, //同步
            success: function(ruleresult) {
                if (ruleresult)
                {
                    if (ruleresult.data)
                    {
                        $("#hidCondtion").val(ruleresult.data.rule_condition);
                        $("#hidResult").val(ruleresult.data.rule_result);
                    }
                }
            }
        });
	}
	
	//isPrice 是指特价-设置范围选择是否可选
	window.setRangeAble=function(isPrice){
		if(isPrice==true){
			$("#range1").attr("disabled",true);
			$("#range2").attr("disabled",true);
			$("#range3").attr("disabled",true);
			$("#range4").attr("disabled",false);
			$("#range4").prop("checked",true);
		}else{
			$("#range1").attr("disabled",false);
			$("#range2").attr("disabled",false);
			$("#range3").attr("disabled",false);
			$("#range4").attr("disabled",false);
			if(rangeChecked!=''){
				//编辑方案初始化之后，用户重新选择就设置为空
				rangeChecked='';
			}else{
				$("#range1").prop("checked",true);
			}
		}
		form.render('radio');
	}
	
	
	//执行模式选择动作
	//chk 促销模式值
	window.radioModelChange=function(chk){
		
		if(chk=='rdoD'){
			  $("#PlanPrice").hide();
			  $("#PlanSend").hide();
			  $("#PlanDiscount").show();
			  setRangeAble(false);
		}else if(chk=='rdoP'){
			  $("#PlanDiscount").hide();
			  $("#PlanSend").hide();
			  $("#PlanPrice").show();
			  //促销范围单选设定特价只可以选商品
			  setRangeAble(true);
		}else if(chk=='rdoF'){
			  $("#PlanDiscount").hide();
			  $("#PlanPrice").hide();
			  $("#PlanSend").show();
			  setRangeAble(false);
		}
		
		 window.SetHidden();
	}
	
	//模式选择
	form.on('radio(model)', function(data){
		  var chk=data.value;
		  var selM = chk.toString().replace("rdo", "");
		  window.ChangeModel(selM);
		  window.radioModelChange(chk);
	});
	
	//范围选择
	form.on('radio(range)', function(data){
		var selM = $("#hidModel").val();
		var chkVal=data.value;
        var range = chkVal.toString().replace("rdo", "");
        window.ChangeRange(selM, range);
        window.SetHidden();
	});
	
	//特价模式选择
	form.on('radio(p)', function(data){
		var chkVal=data.value;
		$("#hidRuleno").val(chkVal.toString().replace("rdo", ""));
        window.SetHidden();
	});
	
	//折扣模式选择
	form.on('radio(d)', function(data){
		var chkVal=data.value;
		$("#hidRuleno").val(chkVal.toString().replace("rdo", ""));
        window.SetHidden();
	});
	
	//买满送选择
	form.on('radio(f)', function(data){
		var chkVal=data.value;
		$("#hidRuleno").val(chkVal.toString().replace("rdo", ""));
        window.SetHidden();
	});
	
	//设置详细信息表格数据（字段）明细
	window.setColumns=function(){
		master_columns = [];
		master_columns.push({title: "行号", field: "rowIndex", width: 80});
	
		keyField = "";
		var primaryKey = "";
		var mark = $("#hidCondtion").val() + "," + $("#hidResult").val();
		var condition = $("#hidCondtion").val();
		var result = $("#hidResult").val();
		//空列字段表头
		var item = {};
			item['rowIndex']='';
		//AMT金额QTY数量ALL全部BRAND品牌CLS分类ITEM商品R1 *打折= R1特价Reduce直减RN商品组合
		//Send赠送  R1 BARCODEN指定商品 需要显示赠送商品信息
		var title = "";
		var model = $("#hidModel").val().toLowerCase();
		if (mark.indexOf("BRAND1") > -1)
		{
			master_columns.push({title: "品牌编码", field: "BRAND1", width: 150, style:'background-color: #fff6f0;',edit:'text'});
			item['BRAND1']='';
			master_columns.push({title: "品牌名称", field: "code_name", width: 100});
			item['BRAND_Name']='';
			primaryKey = "BRAND1";
		}
		if (mark.indexOf("CLS1") > -1)
		{
			master_columns.push({ title: "类别编码", field: "CLS1", width: 150, style:'background-color: #fff6f0;',edit:'text'});
			item['CLS1']='';
			master_columns.push({ title: "类别名称", field: "item_clsname", width: 100});
			item['item_clsname']='';
			primaryKey = "CLS1";
		}
		if (model === "p" || mark.indexOf("ITEMN") > -1 || mark.indexOf("ITEM1") > -1)
		{
			var item_field = "ITEM1";
			master_columns.push({ title: "货号", field: item_field, width: 150, style:'background-color: #fff6f0;',edit:'text'});
			keyField = item_field;
			item[item_field]='';
			master_columns.push({ title: "商品名称", field: "item_name", width: 150});
			item["item_name"]='';
			master_columns.push({ title: "零售价", field: "sale_price", width: 100});
			item["sale_price"]='';
			master_columns.push({ title: "进价", field: "price", width: 100});
			item["price"]='';
			if (condition.indexOf("TN") > -1)
			{
				master_columns.push({title: "组号", field: "ROWID", width: 150, style:'background-color: #fff6f0;',edit:'text'});
				item["ROWID"]='';
				keyField = "ROWID";
			}
			primaryKey = keyField;
		}
		switch (model)
		{
			case "p":
				title = "特价"; //商品、件数、特价、组合
				break;
			case "f":
				title = "售价"; //SEND的话需要保存赠送信息商品、数量
				//商品 买满金额、减少金额
				break;
			default:
				title = "折扣"; //只有商品和特价两个值需要存储
				break;
		}
		var field1 = "";
		var _title = "";
		if (condition.length > 0 && condition.indexOf(">") > -1)//是否有买满的判断
		{
			if (condition.indexOf("AMT") > -1)
			{
				_title = "金额";
			}
			else if (condition.indexOf("QTY") > -1)
			{
				_title = "数量";
			}
			
			if (condition.indexOf("TN") > -1)//
			{
				field1 = "TN";
				keyField = "TN";
			}
			else
			{
				if (condition.indexOf("ADD") > -1)//
				{
					field1 = "T2";
					keyField = "T2";
				}
				else
				{
					field1 = "T1";
					keyField = "T1";
				}
			}
			master_columns.push({title: "买满" + _title, field: field1, width: 150,style:'background-color: #fff6f0;',edit:'text'});
			item[field1]='';
		}
		if (condition.length > 0 && condition.indexOf("ADD") > -1)
		{
			master_columns.push({title: "增加" + _title, field: "T1", width: 150, style:'background-color: #fff6f0;',edit:'text'});
			item["T1"]='';
			keyField = "T1";
		}
		if (condition.length > 0 && condition.indexOf("LIMIT") > -1)
		{
			master_columns.push({title: "限制数量", field: "T2", width: 150, style:'background-color: #fff6f0;',edit:'text'});
			item["T2"]='';
			keyField = "T2";
		}
		if (result.indexOf("Reduce R1") > -1 || result.indexOf("Send R1") > -1)
		{
			master_columns.push({title: "减少金额", field: "R1", width: 150, style:'background-color: #fff6f0;',edit:'text'});
			item["R1"]='';
			keyField = "R1";
		}
		if (model === "d")
		{
			master_columns.push({title:title, field: "R1", width: 150,style:'background-color: #fff6f0;',edit:'text'});
			item["R1"]='';
			keyField = "R1";
		}
		else if (model === "p")
		{
			var p_field = "R1";
			if (result.indexOf("RN") > -1)
			{
				p_field = "RN";
			}
			master_columns.push({title: title, field: p_field, width: 150, style:'background-color: #fff6f0;',edit:'text'});
			item[p_field]='';
			keyField = p_field;
		}
		//设置空行字段和空置，添加行项目
		emptyGrid = item ;
		//设置明细表格表头
		cols[0]=master_columns;
		
		//console.log("字段:");
		//console.log(item);
		
		//console.log("明细表字段:");
		//console.log(master_columns);
	}
	
	//####################赠送商品表格开始#############################################
	//判断是否显示赠送商品
	window.showSend=function(){
		
		var rule = $("#hidResult").val();
		//显示赠送商品信息
		if (rule.indexOf("Send BARCODEN") > -1)
        {
            $("#sendDiv").show();
            
            var plan_no = $("#hidPlanNo").val();
            
            if (plan_no == "")
            {
            	//显示空值得赠送商品
                window.loadSendItems();
            }
            else
            {
            	//显示促销计划的赠送商品
            	window.loadPlanSendItems();
            }
            
            	$("#hidSendGrid").val("1");
        }
        else
        {
        	 $("#sendDiv").hide();
        	//不显示赠送商品信息
            $("#hidSendGrid").val("0");
        }
	}
	
	
	//从send_columns取字段名，返回值为空的object
	window.getSendField=function(){
		var column=new Object();
		for(var i in send_columns[0]){
			var obj=send_columns[0][i];
			var field=obj['field'];
			column[field]='';
		}
		return column;
	}
	
	//添加赠送商品空信息行
	window.addEmptySendRows=function(){
		var sendField=window.getSendField();
		var rows=table.getData("send-table");
		var grid=new Object();
		//复制对象
		$.extend(grid,sendField);
		grid.rowIndex=rows.length+1;
		rows.push(grid);
		
		reloadSendTable(rows);
	}
	
	//初始化赠送商品表格
	window.initSendTable=function(_url){
		var param={
				elem: '#send-table',
				url: _url,
				page: false,
				cols: send_columns,
				limit:1000,
				skin: 'line',
				toolbar: '',
				defaultToolbar: []
		};
		
		//初始化只有一行空数据的表格
		if(_url==''){
			var rows=[];
			param['data']=rows;
		}
		
		table.render(param);
		
		editListener();
	}
	
	//重新加载赠送商品表格
	window.reloadSendTable=function(rows){
		table.reload('send-table',{
			url: '',
			data : rows
		});
		
		rebuildSendItemNo();
		
		editListener();
	}
	
	//显示空数据的赠送商品
	window.loadSendItems=function(){
		initSendTable("");
	}
	
	//显示促销计划保存的赠送商品
	window.loadPlanSendItems=function(){
		var plan_no = $("#hidPlanNo").val();
		var _url=path+"/getsend?plan_no="+plan_no;
			initSendTable(_url);
	}
	
	//选择多个商品窗口
	window.selectSendGoods=function(param){
		isSingle=false;
		exeSendGood=true;
		_layerIndex=openWin("商品选择",multiple_items_url,"1050px","500px");
	}
	//编辑数据表格选择单个商品窗口
	window.selectSendSingleGoods=function(param){
		isSingle=true;
		exeSendGood=true;
		_layerIndex=openWin("商品选择",items_url,"1050px","500px");
	}
	//商品选择后的回调函数
	window.callBack=function(data){
	
		if(_layerIndex!=null){
			layer.close(_layerIndex);
		}
		
		//单选商品，且赠送商品
		if(isSingle&&exeSendGood){
			var one=data[0];
			one['rowIndex']=rowIndex+1;
			one['BARCODEN']=one['item_no'];
			one['RN']=1;
			window.editRow(rowIndex,one);
			return;	
		}
		
		//单选商品，且明细商品
		if(isSingle&&!exeSendGood){
			var one=data[0];
				one['ITEM1']=one['item_no'];
			var data=window.editDetailRow(one);
				//重新加载明细表格
				window.reloadTable(data);
				return;	
		}
		
		
		var repeat=false;
		for(var i in data){
			var new_item_no=data[i]['item_no'];
			if(all_item_no.indexOf(new_item_no)!=-1){
				repeat=true;
				data.splice(i,1);
			}
		}
				
		if(repeat){
			layer.msg("已存在相同商品");
		}
		
		if(data.length>0){
			//临时添加行项目
			addCombItem(data);
			
			window.addEmptySendRows();
		}
	}
	
	//重新堆栈数组，保存当前所有的商品编号
	function rebuildSendItemNo(){
		
		all_item_no=new Array();
		
		var rows=table.getData("send-table");
		for(var i in rows){
			if(rows[i]['BARCODEN']!=''){
				all_item_no.push(rows[i]['BARCODEN']);
			}
		}
	}
	
	//成功输入商品编码或单选商品返回成功，编辑当前表格数据
	//_rowIndex是当前编辑的行号
	//data是新数据
	window.editRow=function(_rowIndex,data){

		var new_item_no=data['BARCODEN'];
		var rindex=data['rowIndex'];
		if(all_item_no.indexOf(new_item_no)!=-1){
			layer.msg("已存在相同商品");
			var sendField=window.getSendField();
			$.extend(data,sendField);
			data['rowIndex']=rindex;
		}
		
		var rows=table.getData("send-table");
			rows[_rowIndex]=data;
			reloadSendTable(rows);
			return true;
	}
	
	//监听单元格编辑
	function editListener(){
		
		 //监听单元格编辑
	  	table.on('edit(send-table)', function(obj){
	  		//验证数字规则
	  		var regNum =  /^[0-9]*$/g;
	  		//验证商品编码
	  		var regNo =  /^[0-9a-zA-Z]*$/g;
	  		
	  		var value = obj.value //得到修改后的值
			    ,data = obj.data //得到所在行所有键值
			    ,field = obj.field; //得到字段
		    var selector = obj.tr.selector+' td[data-field="'+obj.field+'"] div';   
			var oldtext = $(selector).text();

			 if(field=='BARCODEN'&&!regNo.test(value)){
		    		layer.alert("请输入字母或数字");
		    	    $(obj.tr.selector + ' td[data-field="' + obj.field + '"] input').val(oldtext);
		    		return;
			 }
			 
			 if(field=='RN'){
				 if(!regNum.test(value)){
			    		layer.alert("请输入数字");
			    	    $(obj.tr.selector + ' td[data-field="' + obj.field + '"] input').val(oldtext);
			    		return;
		    	}
			 }    
			 
			 rowIndex=obj.tr.attr("data-index");
			 rowIndex=parseInt(rowIndex);
			//选择商品
	  		if(field=='BARCODEN'&&$.trim(value)!=''){
	  			
	  			loadGood=true;
	  			//判断商品编码，并且弹出商品选择窗口
		    	$.ajax({
					url:good_url,
					data:{"itemno":value},
					dataType: 'json',
					type: 'POST',
					success: function(result) {
						//如果不为空，则填充数据
						if(result.code==1){
							var tempData=result.data;
								tempData['rowIndex']=1;
								tempData['BARCODEN']=tempData['item_no'];
								tempData['RN']=1;

								window.editRow(rowIndex,tempData);
						}else{
								//弹出商品选择窗口
								selectSendSingleGoods("");
						}
						
						loadGood=false;
					}
				})
				
	  		}
	  	});
	  	
	    //数据表格点击事件
	    table.on('row(send-table)', function(obj){
			
	    	if(loadGood){
	    		return;
	    	}
	    	
	    	var bg=$(this).attr("style");
	    	if(bg==undefined||bg=='background-color:#ffffff;'){
	    		$(this).attr({ "style": "background-color:#fafafa;" });
	    	}else{
	    		$(this).attr({ "style": "background-color:#ffffff;" });
	    	}
	    	
	    	rowObj=obj;
			//当前编辑行的序号
			rowIndex=rowObj.tr.attr("data-index");

		});
	}
	
	//添加商品
	function addCombItem(data){
		//获取当前表格的所有数据
		var rows=table.getData("send-table");
		var update_rows=[];
		//删除空数据
		for(var i in rows){
			if(rows[i]['BARCODEN']!=''){
				update_rows.push(rows[i]);
			}
		}
		
		var next=update_rows.length+1;
		for(var i in data){
			var obj=data[i];
			
			var array=new Object();
				array=obj;
				array['rowIndex']=next;
				array['BARCODEN']=obj['item_no'];
				array['item_name']=obj['item_name'];
				array['unit_no']=obj['unit_no'];
				array['RN']=1;
				array['sale_price']=obj['sale_price'];
				array['price']=obj['price'];
			
			update_rows.push(array);
			next++;
		}
		reloadSendTable(update_rows);
	}
	
	//清空当前行
	window.deleteSendItem=function(){
		
		if(rowObj==null){
			layer.msg("请选择要清空的数据行");
			return;
		}
		
		var rows=table.getData("send-table");
		rows.splice(rowIndex,1);
		
		var _index=1;
		for(var i in rows){
			rows[i]['rowIndex']=_index;
			_index++;
		}
		reloadSendTable(rows);
	}
	
	//####################赠送商品表格结束#############################################
	
	//####################明细信息表格开始#############################################
	
	//页面初始化详细信息表格
	window.initDetailTable=function(){
			
			var plan_no = $("#hidPlanNo").val();
		
			window.setColumns();
			
			//判断明细表是否有商品编码字段-没有则隐藏工具按钮
			var showTool=false;
			for(var i in emptyGrid){
				if(i=="ITEM1"){
					showTool=true;
					break;
				}
			}
			
			if(showTool){
				$("#detailTool").show();
			}else{
				$("#detailTool").hide();
			}
			
			if (plan_no == "")
			{
				window.LoadEmptyItems();
			}
			else
			{
				window.LoadGoodItems(plan_no);
			}
	}
	
	//初始化layui table明细商品表格组件
	window.initTable=function(_url){
		//console.log(_url);
		//console.log(cols);
		var param={
				elem: '#goods-table',
				url: _url,
				page: false,
				cols: cols,
				limit:1000,
				skin: 'line',
				toolbar: '',
				defaultToolbar: []
		};
		
		//初始化只有一行空数据的表格
		if(_url==''){
			var grid=new Object();
			var rows=[];
			//复制对象
			$.extend(grid,emptyGrid);
			grid.rowIndex=1;
			rows.push(grid);
			
			param['data']=rows;
		}
		
		table.render(param);
		
		editDetailListener();
	}

	
	//加载空数据表格
	window.LoadEmptyItems=function(){
		window.initTable("");
	}
	
	//加载表格数据的商品或数据详细
	window.LoadGoodItems=function(plan_no) {
		
        var condition = $("#hidCondtion").val();
        var result = $("#hidResult").val();
        var _url=path+"/getdetail?plan_no="+plan_no+"&rule_para="+condition+"&rule_val="+result;
		window.initTable(_url);
    }
	
	//明细表选择商品
	window.selectGoods=function(){
		isSingle=true;
		exeSendGood=false;
		//选择商品后回调函数还是window.callback
		_layerIndex=openWin("商品选择",items_url,"1050px","500px");
	}
	
	//明细表商品选择后执行函数
	window.editDetailRow=function(data){
		var rows=table.getData("goods-table");
			rows[0]=data;
			return rows;
	}
	
	//重新加载明细商品表格
	window.reloadTable=function(rows){
		table.reload('goods-table',{
			url: '',
			data : rows
		});
		
		editDetailListener();
	}
	
	//监听单元格编辑
	function editDetailListener(){
		
		 //监听单元格编辑
	  	table.on('edit(goods-table)', function(obj){
	  		//验证数字规则
	  		var regNum =  /^[0-9]*$/g;
	  		//验证商品编码
	  		var regNo =  /^[0-9a-zA-Z]*$/g;
	  		
	  		var value = obj.value //得到修改后的值
			    ,data = obj.data //得到所在行所有键值
			    ,field = obj.field; //得到字段
		    var selector = obj.tr.selector+' td[data-field="'+obj.field+'"] div';   
			var oldtext = $(selector).text();

			 if((field=='CLS1'||field=='BRAND1')&&!regNo.test(value)){
		    		layer.alert("请输入字母或数字");
		    	    $(obj.tr.selector + ' td[data-field="' + obj.field + '"] input').val(oldtext);
		    		return;
			 }
			 
			//输入商品分类代码
		 	if(field=='CLS1'&&$.trim(value)!=''){
	  			
	  			loadGood=true;
	  			//判断商品编码，并且弹出商品选择窗口
		    	$.ajax({
					url:cls_url,
					data:{"cls_no":value},
					dataType: 'json',
					type: 'POST',
					success: function(result) {
						//如果不为空，则填充数据
						if(result.code==1){
							var tempData=result.data;
								tempData['rowIndex']=1;
								tempData['CLS1']=tempData['item_clsno'];
								tempData['item_clsname']=tempData['item_clsname'];

								var data=window.editDetailRow(tempData);
								//重新加载明细表格
								window.reloadTable(data);
						}else{
								layer.msg(result.msg);
						}
						
						loadGood=false;
					}
				})
	  		}else if(field=='BRAND1'&&$.trim(value)!=''){
	  			//选择商品品牌
	  			loadGood=true;

		    	$.ajax({
					url:brand_url,
					data:{"brand_no":value},
					dataType: 'json',
					type: 'POST',
					success: function(result) {
						//如果不为空，则填充数据
						if(result.code==1){
							var tempData=result.data;
								tempData['rowIndex']=1;
								tempData['BRAND1']=tempData['code_id'];
								tempData['code_name']=tempData['code_name'];

								var data=window.editDetailRow(tempData);
								//重新加载明细表格
								window.reloadTable(data);
						}else{
								layer.msg(result.msg);
						}
						
						loadGood=false;
					}
				})
	  		}
	  	});
	  	
	    //明细数据表格点击事件
	    table.on('row(goods-table)', function(obj){
			
	    	if(loadGood){
	    		return;
	    	}
	    	
	    	var bg=$(this).attr("style");
	    	if(bg==undefined||bg=='background-color:#ffffff;'){
	    		$(this).attr({ "style": "background-color:#fafafa;" });
	    	}else{
	    		$(this).attr({ "style": "background-color:#ffffff;" });
	    	}

		});
	}
	
	//清空当前行
	window.deleteItem=function(){
		
		var grid=new Object();
		var rows=[];
		//复制对象
		$.extend(grid,emptyGrid);
		grid.rowIndex=1;
		rows.push(grid);
		
		window.reloadTable(rows);
	}
    
  //####################明细信息表格结束#############################################
	
	//如果是编辑方案，则加载方案并设置各个input值
	window.editPlan=function(){
        var plan_no = $("#hidPlanNo").val();
        $.ajax({
            url: path+"/get",
            type: "POST",
            data: {
                "plan_no": plan_no
            },
            dataType: "json",
            success: function(result) {

                    if (result.code)
                    {
                        var master = result.data;
                        var branch = result.branch;
                        $("#PlanNo").val(master.plan_no);
                        $("#PlanName").val(master.plan_name);
                        $("#PlanMemo").val(master.plan_memo);
                        $("#OperMan").val(master.oper_man);
                        $("#OperDate").val(master.oper_date);
                        $("#BeginDate").val(master.begin_date);
                        $("#EndDate").val(master.end_date);
                        $("#ConfirmMan").val(master.confirm_man);
                        var seconds = master.date1.split('~');
                        $("#BeginSecond").val(seconds[0]);
                        $("#EndSecond").val(seconds[1]);
                        var week = master.week;
                        $("input[name='week']").each(function() {
                            var id = $(this).attr("id").toString().replace("week", "");
                            var val = week.toString().substring(parseInt(id), parseInt(id) + 1);
                            if (val === "1")
                            {
                                $(this).attr("checked", "checked");
                            }
                            else
                            {
                                $(this).removeAttr("checked");
                            }
                        });
                        
                        $("#stop_man").text(master.stop_man);
                        $("#stop_date").text(master.stop_date);

                        var bran_t1 = "";
                        var bran_n1 = "";
                        $.each(branch, function(index, item) {
                            bran_t1 = item.branch_no + "," + bran_t1;
                            bran_n1 = item.branch_name + "|" + bran_n1;
                        });
                        var branchs;
                        if (bran_t1.length > 0)
                        {
                            $("#BranchName").val(bran_n1.substring(0, bran_n1.length - 1));
                            branchs = bran_t1.substring(0, bran_t1.length - 1);
                            $("#hidBranchNos").val(branchs);
                            $("#BranchNo").val(branchs);
                        }
                        var rule_no = master.rule_no;
                        var range_flag = master.range_flag;
                        var model = rule_no.toString().substring(0, 1).toLowerCase();
                        $("input[name='model'][value='rdo" + model.toUpperCase() + "']").attr("checked", "checked");
                        window.ChangeModel(model);
                        $("#hidModel").val(model);
                        $("input[name='range'][value='rdo" + range_flag + "']").attr("checked", "checked");
                        window.ChangeRange(model, range_flag);
                        rangeChecked=range_flag;
                        $("#hidRange").val(range_flag);
                        $("input[name='" + model + "'][value='rdo" + rule_no + "']").attr("checked", "checked");
                        $("#hidRuleno").val(rule_no);
                        $("#hidCondtion").val("");
                        $("#hidResult").val("");
                       
                        if ($("#hidResult").val().indexOf("Send BARCODEN") > -1)
                        {
                            $("#hidSend").val("1");
                            $("#hidSendGrid").val("1");
                            //加载赠送商品信息
                            window.loadPlanSendItems();
                        }
                        if (master.approve_flag === '1')
                        {
                            
                            isApprove = 1;
                        }
                        
                        window.radioModelChange("rdo"+model.toUpperCase());
                        form.render();
                    }
                    else
                    {
                        layer.msg(result.message);
                    }
                }
        });
	}
	
	//#############加载促销方案详细##########################
	window.loadPlan=function(){
		var planNo=$("#hidPlanNo").val();
		if($.trim(planNo)!=''){
			window.editPlan();
		}
		window.SetHidden();
		return;
	}
	window.loadPlan();
	//保存方案
	window.savePlan=function(){
			
			if(isApprove){
				layer.msg("促销政策已审核不能再修改");
				return;
			}
			//判断买满送赠送商品
			var sendGood=false;
			if($("#hidResult").val().indexOf("Send BARCODEN") > -1){
				sendGood=true;
			}
			var send_items=[];
            $isok = false;
            if ($("#hidResult").val().indexOf("Send BARCODEN") > -1)
            {
                
            	var rows=table.getData("send-table");
    			
            	if(rows.length<=0){
    				layer.msg("请输入赠送商品信息");
    				return;
    			}else
                {
                    $("#hidSendGrid").val("1");
                    send_items=rows;
                    $isok = true;
                }
            }
            else
            {
                $("#hidSendGrid").val("0");
                $isok = true;
            }
            if (!$isok)
            {
                return;
            }

	            var week = "";
	            var weeks = $("input[name='week']");
	            var week0 = "0";
	            var week1 = "0";
	            var week2 = "0";
	            var week3 = "0";
	            var week4 = "0";
	            var week5 = "0";
	            var week6 = "0";
	            for (var i = 0; i < weeks.length; i++)
	            {
	                switch ($(weeks[i]).attr("id").replace("week", ""))
	                {
	                    case "0":
	                        if ($(weeks[i]).prop("checked") === true)
	                        {
	                            week0 = "1";
	                        }
	                        break;
	                    case "1":
	                        if ($(weeks[i]).prop("checked") === true)
	                        {
	                            week1 = "1";
	                        }
	                        break;
	                    case "2":
	                        if ($(weeks[i]).prop("checked") === true)
	                        {
	                            week2 = "1";
	                        }
	                        break;
	                    case "3":
	                        if ($(weeks[i]).prop("checked") === true)
	                        {
	                            week3 = "1";
	                        }
	                        break;
	                    case "4":
	                        if ($(weeks[i]).prop("checked") === true)
	                        {
	                            week4 = "1";
	                        }
	                        break;
	                    case "5":
	                        if ($(weeks[i]).prop("checked") === true)
	                        {
	                            week5 = "1";
	                        }
	                        break;
	                    case "6":
	                        if ($(weeks[i]).prop("checked") === true)
	                        {
	                            week6 = "1";
	                        }
	                        break;
	                }
	            }
	            week = week0 + week1 + week2 + week3 + week4 + week5 + week6;
	            
	            //明细商品信息
	            var goods=table.getData("goods-table");
	            
            	if(vipTypeNo==''){
	            		layer.msg("请选择会员等级");
	    				return;
            	}
            	
	            var para = {
	                'plan[plan_no]': $("#PlanNo").val(),
	                'plan[vip_type]': vipTypeNo,
	                'plan[rule_no]': $("#hidRuleno").val(),
	                'plan[range_flag]': $("#hidRange").val(),
	                'plan[plan_name]': $("#PlanName").val(),
	                'plan[plan_memo]': $("#PlanMemo").val(),
	                'plan[begin_date]': $("#BeginDate").val() + " " + $("#BeginSecond").val(),
	                'plan[end_date]': $("#EndDate").val() + " " + $("#EndSecond").val(),
	                'plan[week]': week,
	                'plan[branch]': $("#hidBranchNos").val(),
	                'detail': goods,//明细信息
	                'plan[condition]': $("#hidCondtion").val(),
	                'plan[result]': $("#hidResult").val(),
	                'detail[send]': send_items,//赠送信息
	                'plan[send]': $("#hidSendGrid").val()
	            };
	            
	            var l=layer.load();
	            
	            $.ajax({
	                url: path+"/save",
	                type: "POST",
	                dataType: "json",
	                data: para,
	                success: function(result) {
	                	if(result.code){
	                        layer.msg(result.msg,{icon:1,time:2000},function(){
	                            parent.layer.close(parent.layer.getFrameIndex(window.name));//关闭当前页
	                            parent.layui.table.reload("plan-table");
	                        });
	                    }else{
	                    	layer.close(l);
	                        layer.msg(result.msg,{icon:2,time:2000});
	                    }
	                },
	                complete: function() {
	                	
	                }
	            });
	        
	}
	
	//######################保存方案###########################
})