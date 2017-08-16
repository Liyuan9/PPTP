//创建项目
$("#projectbutton").click(function(){
    var data = $("#projectForm").serializeArray();
    var postData ={};
    $(data).each(function(i){
        postData[this.name] = this.value;
    });
    //将获取到的数据post给服务器
    var url = '../project/addproject';
   // var jump_url = '../project/index';
    $.ajax({
        url:url,
        data:postData,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                if(data.message){
                    layer.msg(data.message,{icon:1},function(){
                        if(data.url != ''){
                            top.location.href = data.url;
                        }else{
                            location.reload();
                        }
                    })
                }else{
                    if(data.url != ''){
                        top.location.href = data.url;
                    }else{
                        location.reload();
                    }
                }
            }
            else {
                return dialog.error(data.message);
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });

});

/***
 *
 * Form表单提交数据
 * 提交按钮 @formbutton
 * 表单ID @Form
 *
 *****/

 $("#formbutton").click(function(){
	var uploads={};
	var uploadpath=$('input[name=upload]').val();
    var upby=$('input[name=upload]').attr('attr_user');
    var mydate = new Date;
    var upfile = '';
	$(".upFileList").each(function(){
        uploads['path'] = $(this).attr('attr-path');
        uploads['title']=$(this).attr('attr-title');
        uploads['upby'] = $(this).attr('attr-upby');
        uploads['size'] = $(this).attr('attr-size');
        uploads['uptime']= $(this).attr('attr-uptime');
        upfile=upfile + "|" + JSON.stringify(uploads);
    });
    $(".file-preview-success").each(function(){
        uploads['path'] = uploadpath;
		uploads['title']=$(this).find(".file-footer-caption").attr("title");
        uploads['upby'] = upby;
        uploads['size'] = $(this).find(".file-footer-caption").find('samp').text();
        uploads['uptime']= mydate.toLocaleString();
        upfile = upfile +"|"+ JSON.stringify(uploads);
    });
    upfile = upfile.substring(1);
	$('input[name=upload]').val(upfile);
    var data = $("#Form").serializeArray();
    var postData ={};
    $(data).each(function(i){ 
        postData[this.name] = this.value;
    });
    //将获取到的数据post给服务器
    var url = SCOPE.save_url;
    var jump_url = SCOPE.jump_url;

    handelData(url,postData,jump_url)
});


/*****
*条件筛选
*@searchForm表单提交数据
*@searchbutton 搜索按钮
*@search_url  搜索接口
*
*
******/
$("#searchbutton").click(function(){
    var data = $("#searchForm").serializeArray();
    var postData ={};
    $(data).each(function(i){
        postData[this.name] = this.value;
    });
    //将获取到的数据post给服务器
    var url = SCOPE.search_url;
    var jump_url = SCOPE.search_jump_url;
    $.ajax({
        url:url,
        data:postData,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                location.href = jump_url;
            }
            else {
                return dialog.error(data.message);
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });

});
/*********
 * 提交数据到后台
 *
 * *******/
function handelData(url,postData,jump_url){
    $.ajax({
        url:url,
        data:postData,
        type:'post',
        dataType: 'json',
        success:function(data) {
            if(data.status == 1){
                if(data.message){
                    layer.msg(data.message,{icon:1},function(){
                        if(jump_url){
                            location.href = jump_url;
                        }else{
                            location.reload();
                        }
                    })
                }else{
                    if(jump_url){
                        location.href = jump_url;
                    }else{
                        location.reload();
                    }
                }
            }else if(data.status == 3){
                location.href = data.url;
            }else{
                return dialog.error(data.message);
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    });
}

/****
 * 改变状态
 * @id  对象
 * @type  对象改变值
 *
 *****/
function settype(obj,id,type){
    var data = {};
    data['id'] = id;
    data['type'] = type;
    var url = SCOPE.set_url
    $.ajax({
        url:url,
        method:'post',
        data:data,
        dataType:'json',
        success:function(data){
            if(data.status == 1){
                layer.msg(data.message,{icon:1},function(){
                    $(obj).parents('td').find('em').text(data.type);
                })
            }
            else {
                return dialog.error(data.message);
            }
        },
        error:function() {
            return dialog.error('网络异常');
        }
    })
}

/**
 * 改变状态，重新加载页面
 */
 function changestatus(model,id,status){
        layer.open({
            content: '确定要设为'+status+'吗？',
            icon : 3,
            btn : ['是','否'],
            yes : function(){
                $.ajax({
                    url:'../common/changestatus',
                    method:'post',
                    data:{id:id,table:model,status:status},
                    dataType:'json',
                    success:function(data){
                        if(data.status == 1){
                            top.location.reload();
                        }
                        else {
                            return dialog.error(data.message);
                        }
                    },
                    error:function() {
                        return dialog.error('网络异常');
                    }
                })
            }
        });
    }


/**
 * 筛选条件
 * 
 * 
 * **/
function choose(data,pdata){
	var _temple = {
                    forli:'<li></li>',
                    label:'<label></label>',
                    finput:'<input type="text" name="" value="" class="finput"/>',
                    idel:'<i class="fa fa-close del" style="margin-left:10px;color:#617CCD"></i>',
                    item : ' <select name="" class="fselect"></select>',
                    opt: ' <option value=""></option>'
                }; 
	var optionHtml = $('.drop-search-menu-ul').empty();
    $.each(data,function(){
		for(var i=0; i<pdata.length;i++){
			if(this.comment == pdata[i]){
				var liitem = $(_temple.forli);
				if(this.children){
					var childdata = this.children;
					liitem
						.append($(_temple.label)
							.append(this.comment)
						)
					var childs = $(_temple.item);
						childs
							.attr('name',this.name)
						for(var j=0;j<childdata.length;j++){
							childs
								.append($(_temple.opt)
									.attr('value',childdata[j].id)
									.append(childdata[j].name)
								)
						}
						liitem
							.append(childs)
							.append($(_temple.idel))
				}else{
					liitem
						.append($(_temple.label)
							.append(this.comment)
						)
						.append($(_temple.finput)
							.attr('name',this.name)
						)
						.append($(_temple.idel))
				}
				optionHtml.append(liitem);
			}
		}	
	})
	return optionHtml;
}

/**
 * 邮箱格式判断
 * @param str
 */
function checkEmail(str){
    var reg = /^[a-z0-9]([a-z0-9\\.]*[-_]{0,4}?[a-z0-9-_\\.]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+([\.][\w_-]+){1,5}$/i;
    if(reg.test(str)){
        return true;
    }else{
        return false;
    }
}
/**
 * 手机号码格式判断
 * @param tel
 * @returns {boolean}
 */
function checkMobile(tel) {
    var reg = /(^1[3|4|5|7|8][0-9]{9}$)/;
    if (reg.test(tel)) {
        return true;
    }else{
        return false;
    };
}
/*********执行用例************/

function bm_play(planid,needid,caseid,bugid,bugname){
    layer.open({
        type: 2,
        title: '执行用例',
        shadeClose: true,
        shade: 0.3,
        area: ['50%', '70%'],
        content: '../testplan/play_case?planid='+planid+'&needid='+needid+'&caseid='+caseid+'&bugid='+bugid+'&bugname='+bugname
    });
}

/********获取时间差**********/
function getTime(obj,end,start){
     end = $(end).val();
     start = $(start).val();
     end = Date.parse(new Date(end))/1000;
     start = Date.parse(new Date(start))/1000;
     if(start>end){
         return layer.msg('结束时间不能大于开始时间',{icon:2});
     }else{
        var  time = end-start;
        time = parseFloat(time/3600).toFixed(1);
        $(obj).val(time);
     }


}




