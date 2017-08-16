/*********打开创建项目窗口***********/
function openProject(){
    layer.open({
        type: 2,
        area: ['600px', '589px'],
        fix: false, //不固定
        title: '创建项目',
        content: '../project/createproject.html'
    });
}

/*********追加提示框*********/
function tip(obj,msg){
    layer.tips(msg,obj,{
        tips: [1, '#3595CC'],
        time: 4000
    })
}

/********点击笔，快速修改*********/
function edit_pencil(obj){
    $(obj).parent().hide();
    $(obj).parent().next('.edit_input').show();
}

function hideObj(obj){
    $(obj).parent().hide();
    $(obj).parent().prev('.edit_pencil').show();
}

function editObj(obj,table){
    var data = {};
    data[obj.name] = obj.value;
    data['id'] = $(obj).attr('attr_id');
    data['table'] = table;
    if(obj.name == 'email'){
        if(!checkEmail(obj.value)){
            return layer.msg('邮箱格式错误',{icon:2});
        }
    }
    $.ajax({
        url:"../common/penedit",
        data:data,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                if(data.url){
                    layer.msg(data.msg,{icon:1},function(){
                        top.location.href = data.url;
                    })
                }else{
                    layer.msg(data.msg,function(){
                        $(obj).val(obj.value);
                        if(data.list_name){
                            $(obj).parent().prev().find('.edit').text(data.list_name);
                        }else{
                            $(obj).parent().prev().find('.edit').text(obj.value);
                        }
                        hideObj(obj);
                    })
                }
            }
            else {
                layer.msg(data.msg,{icon:2},function(){
                    hideObj(obj);
                })

            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });
}
/********点击笔，快速修改  结束 *********/

/*******************测试计划快速添加用例******************/
function addoption(obj,sign){
    var pid = $(obj).attr('attr_pid');
    var url = $(obj).attr('url');
    var optionHtml = [];
    switch (sign){
        case 'cases':
            optionHtml.push('<tr class="test-case">'+
                +'<td></td>'
                +'<td><input type="text" name="needID" value="'+pid+'" hidden /><input type="text" name="list" value="-1" hidden /></td>'
                +'<td></td>'
                +'<td><input type="text" class="form-control" name="casesName" value="" placeholder="测试用例名称" /></td>'
                +'<td><select name="grade" class="form-control select" style="width: 100%;"><option value="1">1</option>'
                +'<option value="2">2</option><option value="3">3</option><option value="4">4</option></select></td>'
                +'<td><select name="status" class="form-control select" style="width: 100%;"><option value="正常">正常</option>'
                +'<option value="待更新">待更新</option><option value="已废弃">已废弃</option></select></td>'
                +'<td style="text-align: right">用例类型</td>'
                +'<td><select name="type" class="form-control select"><option value="--">--</option>'
                +'<option value="功能测试">功能测试</option><option value="安全测试">安全测试</option>'
                +'<option value="性能测试">性能测试</option><option value="其他">其他</option></select></td>'
                +'<td><a href="javascript:;" onclick="saveappend(this,\''+url+'\')" class="btn btn-warning btn-sm">确定</a>'
                +'<a class="btn btn-default can ma-le-20 btn-sm">取消</a></td>' +
                +'</tr>');
            break;
        default :
            break;
    }
    $(obj).parents('tr.need').after(optionHtml);
    $('.can').click(function(){
        $(this).parent().parent().remove();
    });
}

/****************快速添加子需求,测试用例***************/
function appendtable(obj){
    var id = $(obj).attr('attr_id');
    var url = $(obj).attr('url');
    var pid = $(obj).attr('attr_pid');
    var optionHtml = [];
    switch (id){
        case 'substory':
            optionHtml.push('<tr>' +
                '<td style="text-align: right; vertical-align: middle">需求名称</td>'+
                '<td colspan="2"><input type="text" name="pidNeed" value="'+pid+'" hidden/><input type="text" name="needName" value="" class="form-control"/></td>' +
                '<td><a href="javascript:;" onclick="saveappend(this,\''+url+'\')" class="btn btn-warning btn-sm">确定</a>' +
                '<a class="btn btn-default can ma-le-20 btn-sm">取消</a></td>' +
                '</tr>');
            break;
        case 'linkcases':
            optionHtml.push('<tr>' +
                '<td colspan="2"><input type="text" name="needID" value="'+pid+'" hidden/><input type="text" name="list" value="-1" hidden/>'+
                '<input type="text" name="casesName" value="" class="form-control" placeholder="用例名称"/></td>' +
                '<td><select name="type" class="form-control select"><option value="--">--</option>'+
                '<option value="功能测试">功能测试</option><option value="安全测试">安全测试</option>'+
                '<option value="性能测试">性能测试</option><option value="其他">其他</option></select></td>'+
                '<td><input type="text" class="form-control" style="width: 80px" disabled name="status" value="正常"></td>'+
                '<td><select name="grade" class="form-control select" style="width: 100%;"><option value="1">1</option>'+'' +
                '<option value="2">2</option><option value="3">3</option><option value="4">4</option></select></td>'+
                '<td><a href="javascript:;" onclick="saveappend(this,\''+url+'\')" class="btn btn-warning btn-sm">确定</a>' +
                '<a class="btn btn-default can ma-le-20 btn-sm">取消</a></td>' +
                '</tr>');
            break;
		case 'modular':
            optionHtml.push('<tr>'+
                '<td></td><td><input type="text" class="form-control" name="modularName" placeholder="模块名称"></td>'+
                '<td><textarea name="depict" class="form-control"></textarea></td>'+
                '<td colspan="3"><a href="javascript:;" onclick="saveappend(this,\''+url+'\')" class="btn btn-warning btn-sm">确定</a>' +
                '<a class="btn btn-default can ma-le-20 btn-sm">取消</a></td>' +
                '</tr>');
            break;
		case 'need':
            optionHtml.push('<tr>' +
                '<td style="text-align: right; vertical-align: middle">需求名称</td>'+
                '<td><input type="text" name="iterationID" value="'+pid+'" hidden/><input type="text" name="needName" value="" class="form-control"/></td>' +
                '<td><select name="level" class="form-control select"><option value="--">--</option>'+
                '<option value="极高">极高</option><option value="高">高</option>'+
                '<option value="中">中</option><option value="低">低</option></select></td>'+
                '<td><select name="type" class="form-control select"><option value="--">--</option>'+
                '<option value="业务">业务</option><option value="技术">技术</option></select></td>'+
                '<td><input type="text" class="form-control" name="value" value="" onchange="checkval(this)" onfocus="tip(this,\'请为需求业务价值评分,满分10分\')" /></td>'+
                '<td class="dropdown"><input type="text" class="dropdown-toggle form-control" data-toggle="dropdown" name="dealname" id="dealname" onfocus="getName(this)" value=""/>'+
                '<ul class="dropdown-menu" style="left: 10px; top:40px;"></ul></td>'+
                '<td><a href="javascript:;" onclick="saveappend(this,\''+url+'\')" class="btn btn-warning btn-sm">确定</a>' +
                '<a class="btn btn-default can ma-le-20 btn-sm">取消</a></td>' +
                '</tr>');
            break;
        default:
            break;
    }
    var id = '#'+id;
    $(id).find('tbody').prepend(optionHtml);
    $('.can').click(function(){
        $(this).parent().parent().remove();
    });
}

function saveappend(obj,url){
    var opt = $(obj).parent().parent();
    var data = $(opt).find('input');
    var data1 = $(opt).find('select');
	var data2 = $(opt).find('textarea');
    var postData ={};
    $(data).each(function(i){
        postData[this.name] = this.value;
    });
    $(data1).each(function(i){
        postData[this.name] = this.value;
    });
	$(data2).each(function(i){
        postData[this.name] = this.value;
    });
    $.ajax({
        url:url,
        data:postData,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                if(data.msg){
                    layer.msg(data.msg,{icon:1},function(){
                        location.reload();
                    })
                }else{
                    layer.msg('新增成功',{icon:1},function(){
                        location.reload();
                    })
                }
            }
            else {
                if(data.msg){
                    layer.msg(data.msg,{icon:2});
                }else{
                    layer.msg('新增失败',{icon:2});
                }
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });
}
/**************快速添加子需求结束*****************/
function testtip(obj){
	var msg = '有以下1条执行结果，冒烟测试不通过</br>1、提测模块中，不可测试的功能点占总功能点的40%以上；</br>2、崩溃或bug导致70%以上的功能无法测试（即有70%的测试用例阻塞测试。另：弱阻塞部分有独立模块，与其他模块无影响时，该模块可以测试）；'+
					'</br>3、崩溃频繁导致无法进行测试；</br>4、半个小时随机测试，发现bug数量超过20个；</br>5、兼容机型、浏览器等有一半以上不可用，需要与开发负责人确认修改范围是否与兼容有关，再评估是否可测试；</br>6、个别bug影响60%以上的功能逻辑。';
		layer.tips(msg,obj,{
			tips: [1, '#F0AD4E'],
			area: 'auto',
			maxWidth:'500',
			time: 7200
			})			
}
/************需求状态监测**********/
function checkstatus(obj){
    var status= $(obj).val();
    var creatName =  $('#typeHandel').find('input[name=creatName]').val();
    var dealname = $('#typeHandel').find('input[name=olddeal]').val();
		switch(status){
			case '已拒绝':
				$('#typeHandel').find('input[name=dealname]').val(creatName);
				$('#typeHandel').find('input[name=dealname]').attr('disabled',true);
				tip(obj,'请在评论处填写拒绝理由');
				break;
			case '冒烟测试失败':
				$('#typeHandel').find('input[name=dealname]').val(dealname);
				$('#typeHandel').find('input[name=dealname]').attr('disabled',false);
				testtip(obj);
				break;
			default :
				$('#typeHandel').find('input[name=dealname]').val(dealname);
				$('#typeHandel').find('input[name=dealname]').attr('disabled',false);
				break;
		}
		
	
}
/************缺陷状态监测**********/
function checkBugstatus(obj,sta,step){
    var status= $(obj).val();
    var creatName =  $('#typeHandel').find('input[name=creatName]').val();
    var dealname = $('#typeHandel').find('input[name=olddeal]').val();
	var optionHtml = [];
		switch(status){
			case '拒绝解决':
				$('#typeHandel').find('input[name=dealby]').val(creatName);
				optionHtml.push(
                '<div class="col-sm-12 form-group dealmethod"><label class="col-md-1 control-label font-primary" style="text-align: right">解决方法</label>'+
                '<div class="col-sm-6"><select name="dealmethod" class="form-control select" style="background-color:#fff;">' +
                '<option value="">--</option><option value="修改需求">修改需求</option><option value="设计如此">设计如此</option>' +
				'<option value="不予解决">不予解决</option><option value="删除需求">删除需求</option>' +
				'<option value="重复BUG">重复BUG</option><option value="无法实现">无法实现</option>' +
				'<option value="外部环境">外部环境</option><option value="BUG转需求">BUG转需求</option>' +
                '</select></div>' +
                '</div>');
				var opt = $('#typeHandel').find('.dealmethod');
				if(opt.length == 0){
					$('#typeHandel').find('div').eq(1).after(optionHtml);
				}else{
					$('#typeHandel').find('.dealmethod').show();
				}
				break;
			case '已解决':
				$('#typeHandel').find('input[name=dealby]').val(creatName);
				break;
			case '拒绝原因评审中':
				if($('#typeHandel').find('.tips').length>0){
					$('#typeHandel').find('.tips').show();
				}else{
					if(step == '删除需求' || step == '不予解决' && sta=='拒绝解决'){
						$('#typeHandel').find('div').eq(3).append('<span class="tips">此bug的解决方法是<em class="font-red">'+step+'</em>，请流转给项目负责人审批</span>');
					}else if(step == '修改需求' || step == '设计如此' && sta=='拒绝解决'){
						$('#typeHandel').find('div').eq(3).append('<span class="tips">此bug的解决方法是<em class="font-red">'+step+'</em>，请流转给产品负责人审批</span>');
					}else if(step == '外部环境' && sta=='拒绝解决'){
						$('#typeHandel').find('div').eq(3).append('<span class="tips">此bug的解决方法是<em class="font-red">'+step+'</em>，请流转给运维负责人处理环境</span>');
					}else if(step == 'BUG转需求' && sta=='拒绝解决'){
						$('#typeHandel').find('div').eq(3).append('<span class="tips">此bug的解决方法是<em class="font-red">'+step+'</em>，请先流转给项目负责人审批</span>');
					}
				}
				$('#typeHandel').find('.dealmethod').show();
				break;
			default :
				$('#typeHandel').find('input[name=dealby]').val(dealname);
				$('#typeHandel').find('.dealmethod').hide();
				break;	
		}
}
/*******需求、缺陷状态流转**********/
function typeHandel(step){
    var data = $('#typeHandel').serializeArray();
    var postData = {};
    $(data).each(function(i){
        postData[this.name] = this.value;
    });
	if(step == 'need'){
		url = '../need/changeStatus';
	}else if(step == 'bug'){
		url = '../bugtrace/changeStatus';
	}
    $.ajax({
        url:url,
        data:postData,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status==1){
                location.reload();
            }else{
				layer.msg(data.msg,{icon:2});
			}
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });
}
/*******************需求关联测试用例**********/
function link_case(obj){
    var needID = $(obj).attr('attr_id');
    layer.open({
        type: 2,
        title: '关联用例',
        shadeClose: true,
        shade: 0.3,
        area: ['935px', '70%'],
        content: '../common/linkcases?needID='+needID
    });
}

/*******************测试计划关联需求**********/
function link_need(obj){
    var planID = $(obj).attr('attr_id');
    layer.open({
        type: 2,
        title: '选择需求',
        shadeClose: true,
        shade: 0.3,
        area: ['935px', '70%'],
        content: '../need/linkneed?planid='+planID
    });
}

/****
 * @link 关联对象
 * @linkby  被关联对象
 * @id    被关联id
 * @linkid 关联对象id
 * ****/
function surelink(link,linkby,id){
    var linkid = [];
    var data = {};
    var obj = $('#result').find('.linkid');
    var i=0;
    obj.each(function(){
        linkid[i]=$(this).attr('attr_id');
        i++;
    })
    data['link'] = link;
    data['linkby'] = linkby;
    data['id'] = id;
    data['linkid'] = linkid;

    $.ajax({
        url:'../common/sureLink',
        data:data,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                layer.msg(data.msg,{icon:1},function(){
                    top.location.reload();
                });
            }
            else {
                layer.msg(data.msg,{icon:2});
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });


}
/*****************打开一个评论窗口**********/
function openframe(id,table){
    layer.open({
        type: 2,
        area: ['900px', '70%'],
        fix: false, //不固定
        title: '添加评论',
        content: '../common/comment.html?id='+id+'&ta='+table
    });
}

/*****************查看评论窗口**********/
function lookframe(id,table){
    layer.open({
        type: 2,
        area: ['900px', '500px'],
        fix: false, //不固定
        title: '评论面板',
        content: '../common/lookcomment.html?id='+id+'&ta='+table
    });
}


/*******************删除********************/

function del(table,id){
    var msg = '';
    if(table == 'project_group'){
        msg = '您确定要将此成员从项目中移除吗？';
    }else{
        msg = '您确定要删除吗？一旦删除将不可找回，请慎重！';
    }
    layer.open({
        content: msg,
        icon : 3,
        btn : ['是','否'],
        yes : function(){
            $.ajax({
                url:'../common/del',
                data:{table:table,id:id},
                type:'post',
                dataType: 'json',
                success:function(data) {
                    if(data.status == 1){
                        layer.msg(data.msg,{icon:1},function(){
                            if(data.url == ''){
                                location.reload();
                            }else{
                                top.location.href=data.url;
                            }
                        })
                    }
                    else {
                        layer.msg(data.msg,{icon:2});
                    }
                },
                error:function() {
                    return dialog.error('网络异常');
                }
            });
        }
    });
}


/*******************tab选择**************/
function tabchoose(obj){
    $('.bg-gray').removeClass('active');
    $(obj).parent().addClass('active');
    var id = $(obj).attr('id');
    id = '#'+id+'-div';
    $('.tab-pane').removeClass('active');
    $(id).addClass('active');
}

/**************选择多个测试人员或抄送人员*************/
function selectuser(obj,id){
    var text = '';
    $(obj).toggleClass('blue');
    $(id).next().find('.blue').each(function(){
        text = text+'|'+$(this).text();
    });
    text = text.substring(1);
    $(id).val(text);
    if(text!=''&&id=='#sendto'){
        $('.explain').show();
    }else{
        $('.explain').hide();
        $('#sendtext').val('');
    }
}

/**************选择1个测试人员或抄送人员*************/
function selectOneuser(obj,id){
    var text = '';
    $('.blue').removeClass('blue');
    $(obj).addClass('blue');
    text = $(obj).text();
    $(id).val(text);
}

/**************创建子目录*************/
function addCatalog(obj,table){

    var pa_id = $(obj).attr('attr_id');
    var inputHtml = [];
    inputHtml.push('<li style="padding: 0 5px"><input type="text" name="list_name" id="catalog_add" placeholder="请输入要创建目录的名称" class="form-control" style="margin: 5px 0; border: 1px solid #008800"/></li>');
    $(obj).parents('.list-group-item').after(inputHtml);
    $('#catalog_add').on('change',function(){
        addCata(this,pa_id,table);
    });

}

//上传服务器
function addCata(obj,pid,table){
    var data ={};
    data['list_pid'] = pid;
    data['table'] = table;
    data[$(obj).attr('name')] = $(obj).val();
    $.ajax({
        url:'../common/addCatalog',
        data:data,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                layer.msg(data.msg,{icon:1},function(){
                    location.reload();
                    $(obj).parent('li').remove();
                })
            }
            else {
                layer.msg(data.msg,{icon:2});
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });
}

/*********************打开一个设置显示字段的窗口************************/
function setThead(table){
    layer.open({
        type: 2,
        area: ['750px', '500px'],
        fix: false, //不固定
        title: '设置显示显示',
        content: '../common/setthead?ta='+table
    });
}

/*************************视图选择************************/
function handel_view(obj,table){
    var data = {};
    data['table'] = table;
    data['view'] = $(obj).val();
    $.ajax({
        url:'../common/chooseview',
        data:data,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                location.href = data.url;
            }
            else {
                layer.msg(data.msg,{icon:2});
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });

}


/************************取消关联********************/
function cancelLink(obj,table,sign){
    var data = {};
    data['table'] = table;
    data['id'] = $(obj).attr('attr_id');
    data['sign'] = sign;
    data['cancelID'] = $(obj).attr('attr_cancelID');
    var msg = $(obj).attr('attr_msg');
    if(!msg){
        msg = '你确定取消关联吗？'
    }
    layer.open({
        content: msg,
        icon : 3,
        btn : ['确定','取消'],
        yes : function(){
            $.ajax({
                url:'../common/cancelLink',
                data:data,
                type:'post',
                dataType: 'json',
                success:function(data) {
                    if(data.status == 1){
                        layer.msg(data.msg,{icon:1},function(){
                            location.reload();
                        });
                    }
                    else {
                        layer.msg(data.msg,{icon:2});
                    }
                },
                error:function() {
                    return dialog.error('网络异常');
                }
            });
        }
    })

}

/***********************移出计划**********************/
function removeplan(obj){
    var data = {};
    data['id'] = $(obj).attr('attr_id');
    data['needID'] = $(obj).attr('attr_need');
    data['casesID'] = $(obj).attr('attr_removeid');
    var msg = $(obj).attr('attr_msg');
    if(!msg){
        msg = '你确定取消关联吗？'
    }
    layer.open({
        content: msg,
        icon : 3,
        btn : ['确定','取消'],
        yes : function(){
            $.ajax({
                url:'../common/removeplan',
                data:data,
                type:'post',
                dataType: 'json',
                success:function(data) {
                    if(data.status == 1){
                        layer.msg(data.msg,{icon:1},function(){
                            location.reload();
                        });
                    }
                    else {
                        layer.msg(data.msg,{icon:2});
                    }
                },
                error:function() {
                    return dialog.error('网络异常');
                }
            });
        }
    })
}

/************打开一个人员窗口**************/

function open_user(step,id){
    var title = '';
    if(step == 'join'){
        title = '从已有成员中选择';
    }else{
        title = '新建成员';
    }
    layer.open({
        type: 2,
        area: ['780px', '60%'],
        fix: false, //不固定
        title: title,
        content: '../set/getuser.html?step='+step+'&id='+id
    });
}

/**************批量提测**********/
function planTest(obj){
    var needID = '';
    var num = $(obj).find('.checklist:checked').length;
    if(num<1){
        return dialog.error('请先勾选要提测的需求');
    }
    $(obj).find('.checklist:checked').each(function(i){
        if(needID == ''){
            needID = $(this).val();
        }else{
            needID = needID+','+$(this).val();
        }
    });
    StandardPost('../testplan/create',needID);
}

function StandardPost (url,data)
{
    var form = $("<form method='post'></form>");
    form.attr({"action":url});
    var input = $('<input type="text" name="needID" value="'+data+'" hidden />');
    form.append(input);
    form.submit();
}

/**********获取项目成员************/
function getNames(obj,data){
    //var optItem =$("<ul class='dropdown-menu' style='left: 15px;'></ul>") ;
    var optItem = [];
    $.each(data,function(key,value){
        optItem.push('<li><a href="javascript:;" onclick="selectOneuser(this,\'#dealname\')">'+value+'</a></li>')
    });
    $(obj).next('ul').empty().append(optItem);
}

/***********提交测试************/
function apply(id,test){
    layer.open({
        content: '请先选择测试类型',
        icon : 3,
        area:['300px','220px'],
        btn : ['功能测试','性能测试','接口测试','兼容测试'],
        yes : function(){
            location.href = '../set/applytest?title=功能测试&id='+id+'&test='+test ;
        },
        btn2:function(){
            location.href = '../set/applytest?title=性能测试&id='+id+'&test='+test ;
        },
        btn3:function(){
            location.href = '../set/applytest?title=接口测试&id='+id+'&test='+test ;
        },
        btn4:function(){
            location.href = '../set/applytest?title=兼容性测试&id='+id+'&test='+test ;
        }

    });
}


/**********************需求的批量复制******************/
	function batchCopy(obj,project,needid){
		var opti = '<option value="">--选择项目--</option>';
		var num=0;
		var id = '';
		if(needid){
			num = 1;
			id = needid;
		}else{
			num = $(obj).find('.checklist:checked').length;
			$(obj).find('.checklist:checked').each(function(i){
				id == ''?id = $(this).val():id = id+','+$(this).val();
			});
		}
        for(var i=0;i<project.length;i++){
            opti=opti+'<option value="'+project[i].id+'">'+project[i].projectName+'</option>';
        }
		var sta = '<option value="新需求">新需求</option>'+
				  '<option value="规划中">规划中</option>'+
				  '<option value="实现中">实现中</option>'+
				  '<option value="测试排期中">测试排期中</option>'+
				  '<option value="已拒绝">已拒绝</option>'+
				  '<option value="已实现">已实现</option>'+
				  '<option value="测试中">测试中</option>'+
				  '<option value="冒烟测试失败">冒烟测试失败</option>'+
				  '<option value="测试通过">测试通过</option>';	
            layer.open({
                title:'复制需求至',
                area:['540px','420px'],
                content:'<form id="copyitem"><div class="row" >' +
                        '<p class="font-red" style="padding: 0 15px;">已选择'+num+'个需求</p>' +
                        '<div class="form-group" style="height: 40px; margin: 15px 0"><label class="col-sm-3 control-label">复制需求至项目</label><div class="col-sm-9"><select name="projectID" class="col-sm-8 form-control select">'+opti+'</select></div></div>'+
						'<div class="form-group" style="height: 40px; margin: 15px 0"><label class="col-sm-3 control-label">更新后需求状态</label><div class="col-sm-9"><input type="radio" name="status" value="old" />保留原状态<input type="radio" style="margin-left:20px;" name="status" value="new"/>保留新状态<select name="status_value" style="width:130px;float:right; padding:3px 6px; height:30px;" class=" form-control select" >'+sta+'</select></div></div>'+
                        '<div class="form-group" style="height: 40px; margin: 15px 0"><label class="col-sm-3 control-label">创建人</label><div class="col-sm-9"><input type="radio" name="creatName" value="old"/>保留原创建人<input type="radio" style="margin-left: 20px;" name="creatName" value="new"/>保留为我</div></div>'+
                        '<div class="form-group" style="height: 40px; margin: 15px 0;display:none;"><label class="col-sm-3 control-label"></label><div class="col-sm-9"><input type="checkbox" name="followchange" value="yes"/>原需求变化时，复制需求的(标题，描述，附件)是否跟随变化</div></div>'+
						'</div></form>',
                btn : ['确定','取消'],
                yes : function(){
                    var data = $("#copyitem").serializeArray();
                    var postData ={};
                    $(data).each(function(i){
                        postData[this.name] = this.value;
                    });
                    postData['id'] = id;
					console.log(postData);
                    $.ajax({
                        url:'../need/batchCopy',
                        data:postData,
                        type:'post',
                        dataType: 'json',
                        success:function(data) {
                            if(data.status == 1){
                                layer.msg(data.msg,{icon:1},function(){
                                    location.reload();
                                })
                            }
                            else {
                                layer.msg(data.msg,{icon:5});
                            }
                        },
                        error:function() {
                            return dialog.error('网络异常');
                        }
                    });

                }
            })
	
	}
	
	
	/**********************需求的批量编辑******************/
	function batchEdit(obj,column){
		var num = $(obj).find('.checklist:checked').length;
        var id = '';
		var opti = '<option value=""></option>';
		$(obj).find('.checklist:checked').each(function(i){
			id == ''?id = $(this).val():id = id+','+$(this).val();
		});
		$.each(column,function(i){
			opti = opti + '<option value="'+this.name+'">'+this.comment+'</option>';
		});
		var info = JSON.stringify(column);	
            layer.open({
                title:'批量编辑需求属性',
                area:['540px','320px'],
                content:'<form id="copyitem"><div class="row" >' +
                        '<p class="font-red" style="padding: 0 15px;">已选择'+num+'个需求</p>' +
                        '<div class="form-group" style="height: 40px; margin: 15px 0"><label class="col-sm-3 control-label">选择属性值</label><div class="col-sm-9"><select name="column" onchange="click_Change(this,\'#after_value\')" class="col-sm-8 form-control select">'+opti+'</select></div></div>'+
						'<div class="form-group" style="height: 40px; margin: 15px 0" ><label class="col-sm-3 control-label">更新后的值</label><div class="col-sm-9" id="after_value"><input value="" class="col-sm-8 form-control" /></div></div>'+
						'</div></form>'+
						'<script>'+
						'function click_Change(obj,id){clickChange(obj,id,'+info+');}'+
						'</script>',
                btn : ['确定','取消'],
                yes : function(){
                    var data = $("#copyitem").serializeArray();
                    var postData ={};
                    $(data).each(function(i){
                        postData[this.name] = this.value;
                    });
                    postData['id'] = id;
                    $.ajax({
                        url:'../need/batchEdit',
                        data:postData,
                        type:'post',
                        dataType: 'json',
                        success:function(data) {
                            if(data.status == 1){
                                layer.msg(data.msg,{icon:1},function(){
                                    location.reload();
                                })
                            }
                            else {
                                layer.msg(data.msg,{icon:5});
                            }
                        },
                        error:function() {
                            return dialog.error('网络异常');
                        }
                    });

                }
            })
	}
	
	function clickChange(obj,id,data){
		var opt = '';
		var column = $(obj).val();
		var node = '';
		switch(column){
			case 'creatName':
			case 'needby':
			case 'dealname':
			case 'status':
			case 'level':
			case 'type':
			case 'iterationID':
				$.each(data,function(i){
					if(this.name == column){
						node = this.children;
					}
				});
				opt = '<select name="'+column+'" class="col-sm-8 form-control select"></select>';
				var item = '';
				$.each(node,function(i){
					item = item+'<option value="'+this.id+'">'+this.name+'</option>';
				});
				$(id).empty().append($(opt).append($(item)));
				break;
			case 'startTime':
			case 'endTime':
				opt = '<input class="form-control layer-date" name="'+column+'"  placeholder="YYYY-MM-DD" onclick="laydate({istime:false, format: \'YYYY-MM-DD\'})"/>';
				$(id).empty().append($(opt));
				break;
			default:
				opt = '<input type="text" name="'+column+'" value="" class="form-control col-sm-8"/>' ;
				$(id).empty().append($(opt));
				break;
		}
	}
	
	/******************批量修改分类************/
	function batchCata(obj,column){
		var num = $(obj).find('.checklist:checked').length;
        var id = '';
		var opti = '<option value=""></option>';
		$(obj).find('.checklist:checked').each(function(i){
			id == ''?id = $(this).val():id = id+','+$(this).val();
		});
		var info = JSON.stringify(column);	
            layer.open({
                title:'批量变更需求分类',
                area:['440px','260px'],
                content:'<form id="copyitem"><div class="row" >' +
                        '<p class="font-red" style="padding: 0 15px;">已选择'+num+'个需求</p>' +
                        '<div class="form-group" style="height: 40px; margin: 15px 0"><label class="col-sm-3 control-label">需求分类</label><div class="col-sm-9"><select name="needKind" class="col-sm-8 form-control select" id="tree"><option value="-1">|—未规划</option></select></div></div>'+
						'</div></form>'+
						'<script src="../static/plugins/treeview/selecttree.js"></script>'+
						'<script>'+
						'$("#tree").treeview({color:"#428bca",enableLinks:!0,showBorder:!1,data:'+info+'});'+
						'</script>',
                btn : ['确定','取消'],
                yes : function(){
                    var data = $("#copyitem").serializeArray();
                    var postData ={};
                    $(data).each(function(i){
                        postData[this.name] = this.value;
                    });
                    postData['id'] = id;
                    $.ajax({
                        url:'../need/batchEdit',
                        data:postData,
                        type:'post',
                        dataType: 'json',
                        success:function(data) {
                            if(data.status == 1){
                                layer.msg(data.msg,{icon:1},function(){
                                    location.reload();
                                })
                            }
                            else {
                                layer.msg(data.msg,{icon:5});
                            }
                        },
                        error:function() {
                            return dialog.error('网络异常');
                        }
                    });

                }
            })
	}

	/*************上传头像*************/

	function setImg(id){
		layer.open({
			type:2,
			area: ['360px', '300px'],
			fix: false, //不固定
			title: '上传头像',
			content: '../home/setHead?id='+id
		});
	}