/**
 * Created by lihuangyuan on 17-8-10.
 */

function addTable(data,id){
    var head,body,item;
    var template = {
        table : '<table class="table"></table>',
        thead : '<thead></thead>',
        tbody : '<tbody></tbody>',
        tr : '<tr></tr>',
        th : '<th style="text-align: center"></th>',
        td : '<td></td>'
    };
    var headItem = $(template.tr).append($(template.th).append(data.ysign));
    if(data.ysign == data.xsign){
        headItem.append($(template.th)
            .append('数量')
        );
        head = $(template.thead).append(headItem);
        var bodyItem = $(template.tbody);
        $.each(data.data,function(i,n){
            bodyItem.append($(template.tr)
                .append($(template.td)
                    .append(n.name)
                )
                .append($(template.td)
                    .append(n.data[i])
                )
            );
        });
        body = bodyItem;
    }else{
        var k=0,num=[];//k,num纵向统计总数
        $.each(data.X,function(i,n){
            headItem.append($(template.th)
                .append(n)
            )
            num[k] = 0;
            k++;
        });
        head = $(template.thead).append(headItem);
        var bodyItem = $(template.tbody);
        var opt,count;
        $.each(data.data,function(i,n){
            opt = $(template.tr);
            opt.append($(template.td)
                .append(n.name)
                .attr('style','width:20%;')
            );
            $.each(n.data,function(i,n){
                opt.append($(template.td)
                    .append(n)
                );
                num[i] = num[i]+n;
            });
            bodyItem.append(opt);
        });
        count = $(template.tr).append(
            $(template.td)
                .append('总计')
                .attr('style','color:#FF7F50')
        );
        $.each(num,function(i,n){
            count
                .append($(template.td)
                    .append(n)
                );
        });
        body = bodyItem.append(count);
    }
    item = $(template.table);
    item
        .append(head)
        .append(body);

    $(id).empty().append(item);

}
