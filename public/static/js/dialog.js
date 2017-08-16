/**
 * Created by sahara on 16-6-3.
 *
 * 所用到弹出层的效果
 */

var dialog = {
    //错误跳转
    error: function(message){
        layer.open({
            content: message ,
            icon: 2,
            title: '错误提示'
        });
    },

    //成功跳转
    success : function(message,url){
        layer.open({
            content: message ,
            icon : 1 ,
            yes : function(){
                location.href = url ;
            }
        });
    },

    //弹窗确认跳转
    confirm : function(message,url){
        layer.open({
            content: message,
            icon : 3,
            btn : ['是','否'],
            yes : function(){
                location.href = url ;
            }
        });
    },

    //弹窗确认
    toconfirm : function(message){
        layer.open({
            content : message ,
            icon : 3 ,
            btn : ['确定']
        });
    }


}