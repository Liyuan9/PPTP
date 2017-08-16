/**
 * Created by lihuangyuan on 17-7-5.
 */
;(function($, window, document, undefined) {
    /*global jQuery, console*/
    'use strict';
    var pluginName = 'threeLink';
    var Link = function(element, options) {
        this.$element = $(element);
        this.PLAN = $(element).find('#linkPlan');
        this.NEED = $(element).find('#linkNeed');
        this.CASES = $(element).find('#linkCase');
        this.VER = $(element).parents('form').find('#linkVer');//获取关联版本
        this.plan = [];
        this.need = [];
        this.cases = [];
        this.ver = [];
        this.$wrapper = [];
        this.$need = [];
        this.$case = [];
        this.$ver = [];
        this._init(options);
    };

    Link.prototype = {
        _init: function(options) {
            if (options.data) {
                if (typeof options.data === 'string') {
                    options.data = $.parseJSON(options.data);
                }
                this.plan = $.extend(true, [], options.data);
                delete options.data;
            }
            this._setPlanOption(this.plan);
            this._bind();
            this._render();

        },
        _render: function() {
            var self = this;
            self.$element.find('div').eq(0).append(self.$wrapper);
            self.$element.find('div').eq(1).append(self.$need);
            self.$element.find('div').eq(2).append(self.$case);
            self.$element.parents('form').find('#LinkVers').append(self.$ver);
        },
        _bind: function (){
            this.$element.off('click');
            this.$element.on('click', $.proxy(this._clickHandler, this));
        },
        _clickHandler:function(event){
           var self = this;
           var target = $(event.target);
           var name = target.attr('name');
           if(name == 'planID'){
               target.on('change',function(){
                   var planid = target.val();
                   $.each(self.plan,function(i){
                       if(this.id == planid){
                           self.need = this.need ;
                           self.ver = this.vers;
                       }
                   });
                   self._setNeedOption(self.need);
                   self._setCaseOption();
                   self._setVerOption(self.ver);
                   self._render();
               });
           }
		   if(name == 'iterationID'){
			   target.on('change',function(){
                   var planid = target.val();
                   $.each(self.plan,function(i){
                       if(this.id == planid){
                           self.ver = this.vers ;
                       }
                   });
                   self._setVerOption(self.ver);
                   self._render();
               });
		   }
           if(name == 'needID'){
               target.on('change',function(){
                   var needid = target.val();
                   $.each(self.need,function(i){
                       if(this.id == needid){
                           self.case = this.case ;
                       }
                   });
                   self._setCaseOption(self.case);
                   self._render();
               });
           }
        },
        _setPlanOption:function(node){
            var self = this;
            var optItem = self.PLAN;
            $.each(node,function(i){
                optItem
                    .append($(self._template.opt)
                        .attr('value',this.id)
                        .append(this.planName)
                    );
            });
            return self.$wrapper = optItem;
        },
        _setNeedOption:function(node){
            var self = this;
            var optItem = self.NEED.empty();
            optItem.append($(self._template.opt)
                .append('--选择需求--')
            );
            $.each(node,function(i){
                optItem
                    .append($(self._template.opt)
                        .attr('value',this.id)
                        .append(this.needName)
                    );
            });
            return self.$need = optItem;
        },

        _setCaseOption:function(node){
            var self = this;
            var optItem = self.CASES.empty();
            optItem.append($(self._template.opt)
                .append('--选择用例--')
            );
            $.each(node,function(i){
                optItem
                    .append($(self._template.opt)
                        .attr('value',this.id)
                        .append(this.casesName)
                    );
            });
            return self.$case = optItem;
        },
        _setVerOption:function(node){
            var self = this;
            var optItem = self.VER.empty();
            optItem.append($(self._template.opt)
                .attr('value','0')
                .append('--选择版本--')
            );
            $.each(node,function(i){
                optItem
                    .append($(self._template.opt)
                        .attr('value',this.id)
                        .append(this.title)
                    );
            });
            return self.$ver = optItem;
        },
        _template: {
            selectplan: '<select class="form-control select" name="planID"></select>',
            selectneed: '<select class="form-control select" name="needID"></select>',
            selectcase: '<select class="form-control select" name="caseID"></select>',
            opt :'<option value=""></option>'
        }
    };

    $.fn[pluginName] = function(options) {
       var self = $.data(this, 'plugin_' + pluginName);
       if (!self) {
           return  $.data(this, 'plugin_' + pluginName, new Link(this, $.extend(true, {}, options)));
       }
       else {
           return  self._init(options);
       }
    };
})(jQuery, window, document);

