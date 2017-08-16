/**
 * Created by lihuangyuan on 17-5-23.
 */

;(function($, window, document, undefined) {

    /*global jQuery, console*/

    'use strict';

    var pluginName = 'tableview';

    var Tree = function(element, options) {
        this.tbody = $(this._template.tbody);
        this.$element = $(element);
        this._element = element;
        this.tree = [];
        this.nodes = [];
        this.btn_text = options.btn_text;
        this.title_list = options.title_list;
        this._init(options);
    };

    Tree.defaults = {
        injectStyle: true,
        levels:10
    };

    Tree.prototype = {
        _init: function(options) {
            if (options.data) {
                if (typeof options.data === 'string') {
                    options.data = $.parseJSON(options.data);
                }
                this.tree = $.extend(true, [], options.data);
                delete options.data;
            }
            this.options = $.extend({}, Tree.defaults, options);
            this._setInitialLevels(this.tree, 2);

            this._subscribeEvents();
            this._render();
        },

        _subscribeEvents: function() {
            this.$element.on('click', $.proxy(this._clickHandler, this));
        },

        _clickHandler: function(event){
            var target = $(event.target),
                classList = target.attr('class') ? target.attr('class').split(' ') : [];
            if ((classList.indexOf('fa-minus-circle') != -1) ||
                (classList.indexOf('fa-plus-circle') != -1)) {
                var  node = this._findNode(target);
                this._toggleNodes(node);
                this.tbody.empty();
                this._render();
            }else if(classList.indexOf('checkall') != -1){
                if(target.is(':checked')){
                    this.$element.find('.checklist').prop('checked',true);
                }else{
                    this.$element.find('.checklist').prop('checked',false);
                }
            }else if(classList.indexOf('checklist') != -1){
                if(!target.is(':checked')){
                    this.$element.find('.checkall').prop('checked',false);
                }
            }

        },

        _findNode: function(target) {
            var nodeId = target.closest('tr.pa').attr('data-nodeid'),
                node = this.nodes[nodeId];
            if (!node) {
                console.log('Error: node does not exist');
            }
            return node;
        },
        _toggleNodes: function(node) {
            if (!node.nodes && !node._nodes) {
                return;
            }
            if (node.nodes) {
                node._nodes = node.nodes;
                delete node.nodes;
            }
            else {
                node.nodes = node._nodes;
                delete node._nodes;
            }
        },

        _setInitialLevels: function(nodes, level) {
            if (!nodes){ return; }
            level += 1;
            var self = this;
            $.each(nodes, function addNodes(id, node) {
                if (level >= self.options.levels) {
                    self._toggleNodes(node);
                }
                var nodes = node.nodes ? node.nodes : node._nodes ? node._nodes : undefined;
                if (nodes) {
                    return self._setInitialLevels(nodes, level);
                }
            });
        },

        _render: function() {
            var self = this;
            if (!self.initialized) {
                self.$wrapper = $(self._element);
                self.initialized = true;
            }
            self.$element.append(self.$wrapper.empty());
            self.nodes = [];
            self._buildTree(self.tree, 0);
        },

        _buildTree: function(nodes, level) {
            var self = this;
            var title_list = self.title_list;
            var theadItem = $(self._template.thead);
            var trItem = $(self._template.tr).append($(self._template.th));
            var tbname = {};
            $.each(title_list,function build_head(key,value){
                if(key == 'checkbox'){
                    trItem.append($(self._template.th)
                        .append($(self._template.icheck)
                            .addClass('checkall')
                            .removeClass('checklist')
                        )
                    );
                }else{
                    trItem
                        .append($(self._template.th)
                            .append(value)
                        );
                }
                tbname[key] = key;

            });
            theadItem.append(trItem); //获取表头
            self._buildTbody(nodes,level,tbname);
            self.$wrapper
                .append(theadItem)
                .append(self.tbody);
        },

        _buildTbody: function(nodes,level,tbname){
            if (!nodes) {return;}
            level += 1;
            var self = this;

            $.each(nodes, function addNodes(id,node){
                node.nodeId = self.nodes.length;
                self.nodes.push(node);
                var trItem = $(self._template.tr)
                    .attr('data-nodeid', node.nodeId);
                var spItem = $(self._template.sptem);
                for (var i = 0; i < (level - 1); i++){
                    spItem.append($(self._template.em));
                }
                trItem
                    .append($(self._template.td)
                        .append($(self._template.atem)
                            .append($(self._template.btn))
                            .attr('href','javascript:;')
                            .attr('data_toggle','dropdown')
                            .addClass('dropdown-toggle cog')
                        )
                        .append($(self._template.ul)
                            .append($(self._template.li)
                                .append($(self._template.atem)
                                    .attr('href',self.btn_text.creatlink+node.id)
                                    .append(self.btn_text.add)
                                )
                            )
                            .append($(self._template.li)
                                .append($(self._template.atem)
                                    .attr('href',self.btn_text.editlink+node.id)
                                    .append(self.btn_text.edit)
                                )
                            )
                            .append($(self._template.li)
                                .append($(self._template.atem)
                                    .attr('href','javascript:;')
                                    .attr('onclick','batch_Copy("#table","copy",'+node.id+')')
                                    .append(self.btn_text.copy)
                                )
                            )
							.append($(self._template.li)
                                .append($(self._template.atem)
                                    .attr('href',self.btn_text.applylink+node.id)
                                    .append(self.btn_text.apply)
                                )
                            )
                            .append($(self._template.li)
                                .append($(self._template.atem)
                                    .attr('href','javascript:;')
                                    .attr('onclick','del("'+self.btn_text.table+'",'+node.id+')')
                                    .append(self.btn_text.del)
                                )
                            )

                        )
                        .addClass('dropdown operation')
                    )
                    .addClass('pa');
                    $.each(tbname,function buildtd(key,name){
                        var tdItem = $(self._template.td);
                        switch(name){
                            case 'needName':
                                tdItem
                                    .append($(self._template.div)
                                        .append($(self._template.spanicon)
                                            .append('STORY')
                                        )
                                        .append($(self._template.atem)
                                            .append(node[key])
                                            .attr('href',self.btn_text.desclink+node.id)
                                            .addClass('edit')
                                        )
                                        .append($(self._template.editpen))
                                        .addClass('edit_pencil')
                                        .css({'min-width':'300px'})
                                    )
                                    .append($(self._template.div)
                                        .append($(self._template.input)
                                            .attr('name',name)
                                            .attr('value',node[key])
                                            .attr('attr_id',node.id)
                                            .attr('onchange','editObj(this,"'+self.btn_text.table+'")')
                                        )
                                        .addClass('edit_input')
                                    )
                                    .addClass('tb-title font-primary')
                                if(node.upload){
                                    tdItem.find('.edit')
                                        .append($(self._template.file))
                                }
                                if (node._nodes) {
                                    tdItem.find('div').eq(0)
                                        .prepend($(self._template.item)
                                            .addClass('fa-plus-circle')
                                        )
                                }
                                else if (node.nodes) {
                                    tdItem.find('div').eq(0)
                                        .prepend($(self._template.item)
                                            .addClass('fa-minus-circle')
                                        )
                                }
                                tdItem.find('div').eq(0)
                                    .prepend(spItem);
                                break;
                            case 'level':
                                tdItem
                                    .append($(self._template.div)
                                        .append($(self._template.em)
                                            .removeClass('ma-le-20')
                                            .addClass('edit')
                                            .append(node[key])
                                        )
                                        .css({'width':'100px'})
                                        .append($(self._template.editpen))
                                        .addClass('edit_pencil')
                                    )
                                    .append($(self._template.div)
                                        .append($(self._template.select)
                                            .attr('name',name)
                                            .attr('attr_id',node.id)
                                            .attr('onchange','editObj(this,"'+self.btn_text.table+'")')
                                        )
                                        .css({'width':'100px'})
                                        .addClass('edit_input')
                                    )
                                    .addClass('tb-title font-primary')
                                if(node.val_level){
                                    var opts = node.val_level;
                                    $.each(opts, function buildopt(id,op){
                                        tdItem.find('select')
                                            .append($(self._template.opt)
                                                .attr('value',op.id)
                                                .append(op.name)
                                            )

                                    })
                                }
                                break;
                            case 'type':
                                tdItem
                                    .append($(self._template.div)
                                        .append($(self._template.em)
                                            .removeClass('ma-le-20')
                                            .addClass('edit')
                                            .append(node[key])
                                        )
                                        .css({'width':'100px'})
                                        .append($(self._template.editpen))
                                        .addClass('edit_pencil')
                                    )
                                    .append($(self._template.div)
                                        .append($(self._template.select)
                                            .attr('name',name)
                                            .attr('attr_id',node.id)
                                            .attr('onchange','editObj(this,"'+self.btn_text.table+'")')
                                        )
                                        .css({'width':'100px'})
                                        .addClass('edit_input')
                                    )
                                    .addClass('tb-title font-primary')
                                if(node.val_type){
                                    var opts = node.val_type;
                                    $.each(opts, function buildopt(id,op){
                                        tdItem.find('select')
                                            .append($(self._template.opt)
                                                .attr('value',op.id)
                                                .append(op.name)
                                            )
                                    })
                                }
                                break;
							case 'status':
                                if(node[key] == '冒烟测试失败'){
                                    tdItem
                                        .append($(self._template.atem)
                                            .addClass('font-red')
                                            .attr('herf','javascript:void(0)')
                                            .attr('onclick','testtip(this)')
                                            .append(node[key])
                                        )
                                }else if(node[key] == '测试通过'){
                                    tdItem
                                        .append($(self._template.atem)
                                            .addClass('font-green')
                                            .attr('herf','javascript:void(0)')
                                            .append(node[key])
                                        )
                                }else if(node[key] == '测试排期中'){
                                    tdItem
                                        .append($(self._template.atem)
                                            .addClass('font-dblue')
                                            .attr('herf','javascript:void(0)')
                                            .append(node[key])
                                        )
								}else if(node[key] == '测试中'){
									tdItem
                                        .append($(self._template.atem)
                                            .addClass('font-ye')
                                            .attr('herf','javascript:void(0)')
                                            .append(node[key])
                                        )
                                }else{
                                    tdItem
                                        .append($(self._template.atem)
                                            .attr('herf','javascript:void(0)')
                                            .append(node[key])
                                        )
                                }
                                break;
                            case 'checkbox':
                                tdItem
                                    .append($(self._template.icheck)
                                        .attr('value',node['id'])
                                    );
                                break;
                            case 'caozuo':
                                tdItem
                                    .append($(self._template.atem)
                                        .addClass('btn')
                                        .addClass('btn-xs')
                                        .addClass('btn-warning')
                                        .attr('href','javascript:void(0)')
                                        .attr('onclick','re_move('+node['id']+',\'need\')')
                                        .append('移出计划')
                                    );
                                break;
                            default:
                                tdItem
                                    .append(node[key]);
                        }
                        trItem.append(tdItem);
                    })
                self.tbody.append(trItem);
                if (node.nodes){
                    return self._buildTbody(node.nodes, level,tbname);
                }
            });
        },

        _template: {
            thead: '<thead></thead>',
            tbody: '<tbody></tbody>',
            tr: '<tr></tr>',
            td: '<td></td>',
            th: '<th></th>',
            ul: '<ul class="dropdown-menu"></ul>',
            li: '<li></li>',
            atem: '<a></a>',
            item: '<i class="fa ma-ri-10 "></i>',
            btn: '<i class="fa fa-angle-double-down"></i>',
            icheck:'<input type="checkbox" class="checklist" value="" />',
            sptem: '<span></span>',
            em: '<em class="ma-le-30"></em>',
            div: '<div style="width: 100%;"></div>',
            file: '<i class="fa fa-paperclip font-ye ma-le-10"></i>',
            input: '<input type="text" name="" value="" attr_id="" onmouseout="hideObj(this)" onchange="" style="width: 100%;" >',
            spanicon: '<span class="sign green ma-ri-10"></span>',
            editpen: '<a href="javascript:;" onclick="edit_pencil(this)" style="display: none; margin-left: 20px;" class="e-pen" ><i class="fa fa-pencil"></i></a>',
            select: '<select name="" attr_id="" onblur="hideObj(this)" onchange="" style="width: 100%" class="select"></select>',
            opt: '<option value=""></option>'
        }
    };

    $.fn[pluginName] = function(options, args) {
        return this.each(function() {
            var self = $.data(this, 'plugin_' + pluginName);
            if (typeof options === 'string') {
                if (!self) {
                    logError('Not initialized, can not call method : ' + options);
                }
                else if (!$.isFunction(self[options]) || options.charAt(0) === '_') {
                    logError('No such method : ' + options);
                }
                else {
                    if (typeof args === 'string') {
                        args = [args];
                    }
                    self[options].apply(self, args);
                }
            }
            else {
                if (!self) {
                    $.data(this, 'plugin_' + pluginName, new Tree(this, $.extend(true, {}, options)));
                }
                else {
                    self._init(options);
                }
            }
        });
    };
})(jQuery, window, document);


