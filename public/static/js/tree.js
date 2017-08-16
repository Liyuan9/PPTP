/**
 * Created by lihuangyuan on 17-4-5.
 */
;(function($, window, document, undefined) {

    /*global jQuery, console*/

    'use strict';

    var pluginName = 'treeview';

    var Tree = function(element, options) {
        this.$element = $(element);
        this._element = element;
        this._elementId = this._element.id;
        this._styleId = this._elementId + '-style';

        this.tree = [];
        this.nodes = [];
        this.selectedNode = {};
        this.btn_text = [];
        this._init(options);
    };


    Tree.defaults = {

        injectStyle: true,

        levels:10,

        expandIcon: 'glyphicon glyphicon-plus',
        collapseIcon: 'glyphicon glyphicon-minus',
        nodeIcon: 'glyphicon glyphicon-cog',

        color: undefined, // '#000000',
        backColor: '#F9FAFC', // '#FFFFFF',
        borderColor: undefined, // '#dddddd',
        onhoverColor: undefined,
        //selectedColor: '#FFFFFF',
        //selectedBackColor: '#428bca',

        enableLinks: false,
        highlightSelected: true,
        showBorder: true,
        showTags: false,

        // Event handler for when a node is selected
        onNodeSelected: undefined
    };

    Tree.prototype = {

        remove: function() {

            this._destroy();
            $.removeData(this, 'plugin_' + pluginName);
            $('#' + this._styleId).remove();
        },

        _destroy: function() {

            if (this.initialized) {
                this.$wrapper.remove();
                this.$wrapper = null;

                // Switch off events
                //this._unsubscribeEvents();
            }

            // Reset initialized flag
            this.initialized = false;
        },

        _init: function(options) {

            if (options.data) {
                if (typeof options.data === 'string') {
                    options.data = $.parseJSON(options.data);
                }
                this.tree = $.extend(true, [], options.data);
                delete options.data;
            }
            this.btn_text = $.extend(true, [], options.btn_text);
            this.options = $.extend({}, Tree.defaults, options);
            this._setInitialLevels(this.tree, 2);

            this._destroy();
            this._subscribeEvents();
            this._render();
        },

        _unsubscribeEvents: function() {
            this.$element.off('click');
        },

        _subscribeEvents: function() {

            this._unsubscribeEvents();

            this.$element.on('click', $.proxy(this._clickHandler, this));

            if (typeof (this.options.onNodeSelected) === 'function') {
                this.$element.on('nodeSelected', this.options.onNodeSelected);
            }
            this.$element.on('mouseover', $.proxy(this._onMouseMove, this));
        },

        _onMouseMove:function(event){
            this.$element.find('em').hide();
            $(event.target).find('em').show();
            $(event.target).siblings('em').show();
            $(event.target).parents('em').show();
            $(event.target).parent('li').parent('ul').siblings('em').show();

        },
       _onMouseOut:function(event){
            var target = $(event.currentTarget),
                classList = target.attr('class') ? target.attr('class').split(' ') : [];
           if (classList.indexOf('btn-down') != -1){
               target.hide();
           }
        },

        _clickHandler: function(event){
            if (!this.options.enableLinks) { event.preventDefault(); }

            var target = $(event.target),
                classList = target.attr('class') ? target.attr('class').split(' ') : [],
                node = this._findNode(target);
            if ((classList.indexOf('click-expand') != -1) ||
                (classList.indexOf('click-collapse') != -1)) {
                // Expand or collapse node by toggling child node visibility
                this._toggleNodes(node);
                this._render();
            }else if((classList.indexOf('glyphicon-cog') != -1)){
                this.$element.find('.btn-down').hide();
                target.parent().siblings('.btn-down').show();
                var tar = target.parent().siblings('.btn-down');
                tar.on('mouseleave', $.proxy(this._onMouseOut, this));

            }else if (node) {
                this._setSelectedNode(node);
            }
        },

        // Looks up the DOM for the closest parent list item to retrieve the
        // data attribute nodeid, which is used to lookup the node in the flattened structure.
        _findNode: function(target) {

            var nodeId = target.closest('li.list-group-item').attr('data-nodeid'),
                node = this.nodes[nodeId];
            if (!node) {
                console.log('Error: node does not exist');
            }
            return node;
        },


        // Actually triggers the nodeSelected event
        _triggerNodeSelectedEvent: function(node) {
            this.$element.trigger('nodeSelected', [$.extend(true, {}, node)]);
        },
        // Handles selecting and unselecting of nodes,
        // as well as determining whether or not to trigger the nodeSelected event
        _setSelectedNode: function(node) {

            if (!node) { return; }

            if (node === this.selectedNode) {
                this.selectedNode = {};
            }
            else {
                this._triggerNodeSelectedEvent(this.selectedNode = node);
            }

            this._render();
        },

        // On initialization recurses the entire tree structure
        // setting expanded / collapsed states based on initial levels
        _setInitialLevels: function(nodes, level) {

            if (!nodes) { return; }
            level += 1;
            var self = this;
            $.each(nodes, function addNodes(id, node) {
                if (level >= self.options.levels) {
                    self._toggleNodes(node);
                }
                // Need to traverse both nodes and _nodes to ensure
                // all levels collapsed beyond levels
                var nodes = node.nodes ? node.nodes : node._nodes ? node._nodes : undefined;
                if (nodes) {
                    return self._setInitialLevels(nodes, level);
                }
            });
        },


        // Toggle renaming nodes -> _nodes, _nodes -> nodes
        // to simulate expanding or collapsing a node.
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

        _render: function() {

            var self = this;

            if (!self.initialized) {

                // Setup first time only components
                self.$element.addClass(pluginName);
                self.$wrapper = $(self._template.list);
                self._injectStyle();

                self.initialized = true;
            }
            self.$element.append(self.$wrapper.empty());

            // Build tree
            self.nodes = [];
            self._buildTree(self.tree, 0);

        },

        // Starting from the root node, and recursing down the
        // structure we build the tree one node at a time

        _buildTree: function(nodes, level) {
            if (!nodes) { return; }
            level += 1;
            var self = this;
            $.each(nodes, function addNodes(id, node) {
                node.nodeId = self.nodes.length;
                self.nodes.push(node);

                var treeItem = $(self._template.item)
                    .addClass('node-' + self._elementId)
                    .addClass((node === self.selectedNode) ? 'node-selected' : '')
                    .attr('data-nodeid', node.nodeId)
                    .attr('style', self._buildStyleOverride(node));
                // Add indent/spacer to mimic tree structure
                for (var i = 0; i < (level - 1); i++){
                    treeItem.append(self._template.indent);
                }
                // Add expand, collapse or empty spacer icons
                // to facilitate tree structure navigation
                if (node._nodes) {
                    treeItem
                        .append($(self._template.iconWrapper)
                            .append($(self._template.icon)
                                .addClass('click-expand')
                                .addClass(self.options.expandIcon))
                        );
                }
                else if (node.nodes) {
                    treeItem
                        .append($(self._template.iconWrapper)
                            .append($(self._template.icon)
                                .addClass('click-collapse')
                                .addClass(self.options.collapseIcon))
                        );
                }
                else {
                    treeItem
                        .append($(self._template.iconWrapper)
                            .append($(self._template.icon)
                                .addClass('glyphicon'))
                        );
                }
                treeItem
                    .append($(self._template.link)
                        .attr('href', node.url)
                        .append(node.text)
                    );
                // Add node icon
                treeItem
                    .append($(self._template.emWrapper)
                        .append($(self._template.icon)
                            .addClass(node.icon ? node.icon : self.options.nodeIcon))
                    );
                //Add node tags
                if(node.tags != 0){
                    treeItem
                        .append($(self._template.badge)
                            .append(node.tags)
                        );
                }

                if(node.nodes || node._nodes){
                    treeItem
                        .append($(self._template.btnlist)
                            .append($(self._template.btnitem)
                                .append($(self._template.btnlink)
                                    .attr('attr_id',node.id)
                                    .attr('href',self.btn_text.otherlink+node.id + '&id='+node.projectID)
                                    .attr('attr_title',node.text)
                                    .append(self.btn_text.addother)
                                )
                            )
                            .append($(self._template.btnitem)
                                .append($(self._template.btnlinkfun)
                                    .attr('attr_id',node.id)
                                    .attr('attr_sign','add')
                                    .attr('attr_title',node.text)
                                    .append(self.btn_text.add)
                                )
                            )
                            .append($(self._template.btnitem)
                                .append($(self._template.btnlinkfun)
                                    .attr('attr_id',node.id)
                                    .attr('attr_sign','edit')
                                    .attr('attr_title',node.text)
                                    .attr('attr_desc',node.depict)
                                    .append(self.btn_text.edit)
                                )
                            )
                        )
                    }else{
                        treeItem
                            .append($(self._template.btnlist)
                                .append($(self._template.btnitem)
                                    .append($(self._template.btnlink)
                                        .attr('attr_id',node.id)
                                        .attr('href',self.btn_text.otherlink+node.id + '&id='+node.projectID)
                                        .attr('attr_title',node.text)
                                        .append(self.btn_text.addother)
                                    )
                                )
                                .append($(self._template.btnitem)
                                    .append($(self._template.btnlinkfun)
                                        .attr('attr_id',node.id)
                                        .attr('attr_sign','add')
                                        .attr('attr_title',node.text)
                                        .append(self.btn_text.add)
                                    )
                                )
                                .append($(self._template.btnitem)
                                    .append($(self._template.btnlinkfun)
                                        .attr('attr_id',node.id)
                                        .attr('attr_sign','edit')
                                        .attr('attr_title',node.text)
                                        .attr('attr_desc',node.depict)
                                        .append(self.btn_text.edit)
                                    )
                                )
                                .append($(self._template.btnitem)
                                    .append($(self._template.btnlinkfun)
                                        .attr('attr_id',node.id)
                                        .attr('attr_sign','del')
                                        .attr('attr_title',node.text)
                                        .append(self.btn_text.del)
                                    )
                                )
                            )
                    }
                // Add item to the tree
                self.$wrapper.append(treeItem);

                // Recursively add child ndoes
                if (node.nodes) {
                    return self._buildTree(node.nodes, level);
                }
            });
        },

        // Define any node level style override for
        // 1. selectedNode
        // 2. node|data assigned color overrides
        _buildStyleOverride: function(node) {

            var style = 'padding-left:30px;';
            if (this.options.highlightSelected && (node === this.selectedNode)) {
                style += 'color:' + this.options.selectedColor + ';';
            }
            else if (node.color) {
                style += 'color:' + node.color + ';';
            }

            if (this.options.highlightSelected && (node === this.selectedNode)) {
                style += 'background-color:' + this.options.selectedBackColor + ';';
            }
            else if (node.backColor) {
                style += 'background-color:' + node.backColor + ';';
            }

            return style;
        },

        // Add inline style into head
        _injectStyle: function() {

            if (this.options.injectStyle && !document.getElementById(this._styleId)) {
                $('<style type="text/css" id="' + this._styleId + '"> ' + this._buildStyle() + ' </style>').appendTo('head');
            }
        },

        // Construct trees style based on user options
        _buildStyle: function() {

            var style = '.node-' + this._elementId + '{';
            if (this.options.color) {
                style += 'color:' + this.options.color + ';';
            }
            if (this.options.backColor) {
                style += 'background-color:' + this.options.backColor + ';';
            }
            if (!this.options.showBorder) {
                style += 'border:none;';
            }
            else if (this.options.borderColor) {
                style += 'border:1px solid ' + this.options.borderColor + ';';
            }
            style += '}';

            if (this.options.onhoverColor) {
                style += '.node-' + this._elementId + ':hover{' +
                    'background-color:' + this.options.onhoverColor + ';' +
                    '}';
            }

            return this._css + style;
        },

        _template: {
            list: '<ul class="list-group" ></ul>',
            item: '<li class="list-group-item" ></li>',
            indent: '<span class="indent"></span>',
            iconWrapper: '<span class="icon"></span>',
            emWrapper: '<em class="em-icon" style="margin-left: 10px;display: none" ></em>',
            icon: '<i></i>',
            link: '<a href="javascript:;" style="color:inherit;" ></a>',
            badge: '<span class="badge" style="background-color: #428bca;"></span>',
            btnlist:'<ul class=" dropdown-menu btn-down" style="left: 120px; top:30px; display: none"></ul>',
            btnitem:'<li></li>',
            btnlink:'<a href="" attr_id="" attr_title="" ></a>',
            btnlinkfun:'<a href="javascript:;" onclick="listfun(this)" attr_id="" attr_sign="" attr_title=""></a>',
        },

        _css: '.list-group-item{cursor:pointer;}span.indent{margin-left:10px;margin-right:10px}span.icon{margin-right:5px}.list-group-item>a{font-size:12px;}.list-group-item>.badge{font-size:9px;}'
        // _css: '.list-group-item{cursor:pointer;}.list-group-item:hover{background-color:#f5f5f5;}span.indent{margin-left:10px;margin-right:10px}span.icon{margin-right:5px}'

    };

    var logError = function(message) {
        if(window.console) {
            window.console.error(message);
        }
    };

    // Prevent against multiple instantiations,
    // handle updates and method calls
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
