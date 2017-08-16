/**
 * Created by lihuangyuan on 17-8-10.
 */

function liveImg(data,id,title,subtitle){
    var Y=[];
    var X=[];
    $.each(data.Y,function(i,n){
        Y.push(n);
    });
    $.each(data.X,function(i,n){
        X.push(n);
    });
    if(data.xsign == data.ysign){
        var datas = [];
        $.each(data.data,function(i,n){
            var num = {};
            num['value'] = n.data[i];
            num['name'] = n.name;
            datas[i] = num;
        });
        addPie(datas,id,title,subtitle,Y,data.xsign);
    }else{
        addBar(data.data,id,title,subtitle,X,Y);
    }
}


function addBar(data,id,title,subtitle,X,Y){
    var myChart = echarts.init(document.getElementById(id));
    var option = {
        title:{
            text: title,
            subtext:subtitle,
            x:'center'
        },
        tooltip : {
            show : true,
            trigger: 'item'
        },
        legend: {
            x:'center',
            y:340,
            data:Y
        },
        toolbox: {
            show : true,
            orient: 'vertical',
            x: 'right',
            y: 'center',
            feature : {
                magicType : {show: true, type: ['stack']},
                restore : {show: true},
                saveAsImage : {show: true}
            }
        },
        calculable : true,
        xAxis : [
            {
                type : 'category',
                data : X
            }
        ],
        yAxis : [
            {
                type : 'value',
                splitArea : {show : true}
            }
        ],
        grid: {
            x: 80,
            y: 60,
            x2: 80,
            y2: 120
        },
        series : data
    };
    myChart.setOption(option);
}

function addPie(data,id,title,subtitle,Y,name){
    var myChart = echarts.init(document.getElementById(id));
    var option = {
        title : {
            text: title,
            subtext:subtitle,
            x:'center'

        },
        tooltip : {
            trigger: 'item',
            formatter: "{b} : {c} ({d}%)"
        },
        legend: {
            x:'right',
            data:Y
        },
        calculable : true,
        toolbox: {
            show : true,
            orient: 'vertical',
            x: 'right',
            y: 'center',
            feature : {
                magicType : {
                    show: true,
                    type: ['pie']
                },
                restore : {show: true},
                saveAsImage : {show: true}
            }
        },
        series : [
            {
                name:'',
                type:'pie',
                radius : '55%',
                center: ['50%', '50%'],
                itemStyle : {
                    normal : {
                        label : {
                            formatter : "{b}:{c}"
                        }
                    }
                },
                data:data
            }
        ]

    };
    myChart.setOption(option);
}