/**
 * Created by lihuangyuan on 17-4-21.
 */
;(function($, window, document, undefined) {

    'use strict';

    var pluginName = 'treeCase';

    var Tree = function(element, options) {
        this.$element = $(element);
        this.$result = options.obj.node2;
        this.$resultItem = options.obj.node1;
        this._init(options);
    };

    Tree.defaults = {
        injectStyle: true,
        expandIcon: 'glyphicon glyphicon-plus',
        collapseIcon: 'glyphicon glyphicon-minus',
        color: undefined, // '#000000',
        backColor: undefined, // '#FFFFFF',
        borderColor: undefined, // '#dddddd',
        onhoverColor: '#F5F5F5'
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
            this._render();
            this._subscribeEvents();
        },

        _render: function() {
            var self = this;
            if (!self.initialized) {
                self.$element.addClass(pluginName);
                self.$wrapper = self.$element;
                self.initialized = true;
            }
            self.$element.append(self.$wrapper.empty());

            // Build tree
            self.nodes = [];
            self._buildTree(self.tree, 0);
        },
        _subscribeEvents: function() {
            this.$element.on('click', $.proxy(this._clickHandler, this));
        },
        _clickHandler:function(event){
            var target = $(event.target),
                classList = target.attr('class') ? target.attr('class').split(' ') : [];
            if(classList.indexOf('click-collapse') != -1){
                target.toggleClass('glyphicon-minus');
                target.parent().siblings('ul').slideToggle();
                target.toggleClass('glyphicon-plus');
            }
            if(classList.indexOf('pa-check') != -1){
                if(target.is(":checked")){
                    target.siblings('ul').find('input').prop("checked",true);
                }else{
                    target.siblings('ul').find('input').prop("checked",false);
                }
                this._selectInput();
            }
        },

        _selectInput:function(){
            var text = [];
            var obj = this.$element.find('.child-check:checked');
            for(var i=0;i<obj.length;i++){
                var id = $(obj[i]).val();
                var name = $(obj[i]).attr('name');
                text[i]=[id,name];
            }
            this._buildResult(text);
        },
        _buildResult:function(text){
            var self = this;
            var resultItem = $(document.getElementById('resultItem')).empty();
            for(var i=0; i<text.length;i++){
                resultItem
                    .append($(this._template.item)
                        .removeClass('list-group-item')
                        .addClass('linkid')
                        .attr('style','')
                        .append($(this._template.link)
                            .attr('href','../cases/desc?caseid='+text[i][0])
                            .append(text[i][1])
                        )
                        .attr('attr_id',text[i][0])
                        .append($(this._template.canceli))
                    )
            }
               document.getElementById('result-num').innerHTML = '['+text.length+']';
               this.$result.append(resultItem);
            var obj = this.$element.find('.child-check:checked');
            $('.result-cancle').on('click',function(){
                var inputid = $(this).parent('li').attr('attr_id');
                obj.each(function(){
                    if($(this).val()== inputid){
                        $(this).prop('checked',false);
                    }
                });
                self._selectInput();
            })
        },

        _buildTree:function(nodes){
            var self = this;
            var treeItem = self._buildChild(nodes);
            self.$wrapper.append(treeItem);
        },

        _buildChild:function(nodes){
            var self = this;
            var treeItem = $(self._template.list);
            $.each(nodes,function(){
                var liItem = $(self._template.item);
                if (this.nodes){
                    liItem
                        .append($(self._template.iconWrapper)
                            .append($(self._template.icon)
                                .addClass('click-collapse')
                                .addClass(self.options.collapseIcon))
                        )
                }
                else {
                    liItem
                        .append($(self._template.iconWrapper)
                            .append($(self._template.icon)
                                .addClass('click-collapse')
                                .addClass('glyphicon'))
                        )
                }
                liItem
                    .append($(self._template.checkbtn))
                    .append(this.list_name)
                    .append($(self._template.emicon)
                        .append('['+this.tags+']')
                    );
                if(this.nodes){
                    var childTree = self._buildChild(this.nodes);
                    liItem
                        .append(childTree);
                }
                if(this.cases){
                    var childCase = $(self._template.list);
                    $.each(this.cases,function(){
                        var caseItem = $(self._template.item);
                        caseItem
                            .append($(self._template.checkbtn)
                                .attr('name',this.case_name)
                                .attr('value',this.id)
                                .addClass('child-check')
                            )
                            .append($(self._template.sign)
                                .append('CASE')
                            )
                            .append($(self._template.link)
                                .attr('href','../cases/desc?caseid='+this.id)
                                .addClass('ma-le-5')
                                .append(this.case_name)
                            );
                        childCase
                            .append(caseItem);
                    });
                    liItem.append(childCase);
                }
                treeItem.append(liItem);
            });
            return treeItem;
        },

        _template: {
            list: '<ul class="list-group" style="border: 0;margin: 0;"></ul>',
            item: '<li class="list-group-item" style="padding: 5px 0 5px 20px;border: 0;"></li>',
            link: '<a href="" target="_blank"></a>',
            iconWrapper: '<span class="icon" style="color: #428bca"></span>',
            icon: '<i></i>',
            canceli: '<i class="fa fa-close result-cancle pull-right" style="color: #428bca;line-height: 30px;cursor:pointer;"></i>',
            sign:'<span style="background-color: #67C23A;padding: 0 3px; color:#fff; font-size: 9px; border-radius: 20px;"></span>',
            emicon:'<em style="color: #ff2222; padding-left: 10px;"></em>',
            checkbtn:'<input type="checkbox" class="pa-check" name="" value="" style="margin-right: 5px;"/>'
        }
    };

    var logError = function(message) {
        if(window.console) {
            window.console.error(message);
        }
    };

    $.fn[pluginName] = function(options,args) {
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
