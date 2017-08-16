/**
 * Created by lihuangyuan on 17-4-6.
 */
;(function($, window, document, undefined) {

    /*global jQuery, console*/

    'use strict';

    var pluginName = 'treeview';

    var Tree = function(element, options) {
        this.$element = $(element);
        this._element = element;
        this.tree = [];
        this.nodes = [];
        this._init(options);
    };

    Tree.defaults = {
        injectStyle: true,
        levels:10
    };

    Tree.prototype = {
        _init: function(options) {
            if (options.data){
                if (typeof options.data === 'string') {
                    options.data = $.parseJSON(options.data);
                }
                this.tree = $.extend(true, [], options.data);
                delete options.data;
            }
            this.options = $.extend({}, Tree.defaults, options);
            this._setInitialLevels(this.tree, 2);
            this._render();
        },

        // On initialization recurses the entire tree structure
        // setting expanded / collapsed states based on initial levels
        _setInitialLevels: function(nodes, level) {

            if (!nodes) { return; }
            level += 1;

            var self = this;
            $.each(nodes, function addNodes(id, node) {
                // Need to traverse both nodes and _nodes to ensure
                // all levels collapsed beyond levels
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
            self.$element.append(self.$wrapper);

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

            $.each(nodes, function addNodes(id,node) {
                self.nodes.push(node);
                var $i = '—';
                for (var i = 0; i < (level - 1); i++){
                    $i = $i+'—';
                }
                node.text = '|'+ $i+node.text;
                var treeItem = $(self._element);

                if(self.options.idable == 1){
                    treeItem
                        .append($(self._template.item)
                            .append(node.text )
                            .append($(self._template.emic)
                                .append('ID:'+node.id)
                            )
                        );
                }else{
                    treeItem
                        .append($(self._template.list)
                                .attr('value',node.id)
                                .append(node.text)
                            );
                }

                // Add item to the tree
                self.$wrapper.append(treeItem);

                // Recursively add child ndoes
                if (node.nodes) {
                    return self._buildTree(node.nodes, level);
                }
            });
        },



        _template: {
            list: '<option value=""></option>',
            item: '<li></li>',
            emic: '<em style="color: darkred; margin-left: 10px;"></em>'
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

