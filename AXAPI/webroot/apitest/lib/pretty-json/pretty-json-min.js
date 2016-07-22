/*
        License here,
        I dont think too  much about licence
        just feel free to do anything you want... :-)
*/
var PrettyJSON = {
    view: {},
    tpl: {}
};
PrettyJSON.util = {
    isObject: function(v) {
        return Object.prototype.toString.call(v) === '[object Object]';
    },
    pad: function(str, length) {
        str = String(str);
        while (str.length < length) str = '0' + str;
        return str;
    },
    dateFormat: function(date, f) {
        f = f.replace('YYYY', date.getFullYear());
        f = f.replace('YY', String(date.getFullYear()).slice( - 2));
        f = f.replace('MM', PrettyJSON.util.pad(date.getMonth() + 1, 2));
        f = f.replace('DD', PrettyJSON.util.pad(date.getDate(), 2));
        f = f.replace('HH24', PrettyJSON.util.pad(date.getHours(), 2));
        f = f.replace('HH', PrettyJSON.util.pad((date.getHours() % 12), 2));
        f = f.replace('MI', PrettyJSON.util.pad(date.getMinutes(), 2));
        f = f.replace('SS', PrettyJSON.util.pad(date.getSeconds(), 2));
        return f;
    }
}

PrettyJSON.tpl.Node = '' + '<span class="node-container">' + '<span class="node-top node-bracket" />' + '<span class="node-content-wrapper">' + '<ul class="node-body" />' + '</span>' + '<span class="node-down node-bracket" />' + '</span>';
PrettyJSON.tpl.Leaf = '' + '<span class="leaf-container">' + '<span class="<%= type %>" ondblclick="selectPrettySpan(this,event);"><%=data%></span><span><%= coma %></span>' + '</span>';//asinw edit
PrettyJSON.view.Node = Backbone.View.extend({
    tagName: 'span',
    data: null,
    level: 1,
    path: '',
    type: '',
    size: 0,
    isLast: true,
    rendered: false,
    events: {
        'click .node-bracket': 'collapse',
        'mouseover .node-container': 'mouseover',
        'mouseout .node-container': 'mouseout'
    },
    initialize: function(opt) {
        this.options = opt;
        this.data = this.options.data;
        this.level = this.options.level || this.level;
        this.path = this.options.path;
        this.isLast = _.isUndefined(this.options.isLast) ? this.isLast: this.options.isLast;
        this.dateFormat = this.options.dateFormat;
        var m = this.getMeta();
        this.type = m.type;
        this.size = m.size;
        this.childs = [];
        this.render();
        if (this.level == 1)
        this.show();
    },
    getMeta: function() {
        var val = {
            size: _.size(this.data),
            type: _.isArray(this.data) ? 'array': 'object',

        };
        return val;
    },
    elements: function() {
        this.els = {
            container: $(this.el).find('.node-container'),
            contentWrapper: $(this.el).find('.node-content-wrapper'),
            top: $(this.el).find('.node-top'),
            ul: $(this.el).find('.node-body'),
            down: $(this.el).find('.node-down')
        };
    },
    render: function() {
        this.tpl = _.template(PrettyJSON.tpl.Node);
        $(this.el).html(this.tpl);
        this.elements();
        var b = this.getBrackets();
        this.els.top.html(b.top);
        this.els.down.html(b.bottom);
        this.hide();
        return this;
    },
    renderChilds: function() {
        var  keyDescList = {};//axing add
        if (this.data['modelType'] && getDescriptionsInModel)
        {
            keyDescList = getDescriptionsInModel(this.data['modelType']);
        }
        var count = 1;
        _.each(this.data,
        function(val, key) {
            var isLast = (count == this.size);
            count = count + 1;
            var path = (this.type == 'array') ? this.path + '[' + key + ']': this.path + '.' + key;
            var opt = {
                key: key,
                data: val,
                parent: this,
                path: path,
                level: this.level + 1,
                dateFormat: this.dateFormat,
                isLast: isLast
            };
            var child = (PrettyJSON.util.isObject(val) || _.isArray(val)) ? new PrettyJSON.view.Node(opt) : new PrettyJSON.view.Leaf(opt);
            child.on('mouseover',
            function(e, path) {
                this.trigger("mouseover", e, path);
            },
            this);
            child.on('mouseout',
            function(e) {
                this.trigger("mouseout", e);
            },
            this);
            var li = $('<li/>');
            if (keyDescList[key])//axing add
            {
                li.attr('title',keyDescList[key]);
                li.tooltip({'placement':'left','delay': { "show": 0, "hide": 0 }});
            }
            var colom = '&nbsp;:&nbsp;';
            var left = $('<span' + ( typeof(val) == 'string' ? ' ondblclick="previewThisData(this,event);" el-string="'+encodeURIComponent(val)+'"':'')+ '/>');
            var right = $('<span />').append(child.el); (this.type == 'array') ? left.html('') : left.html(key + colom);
            left.append(right);
            li.append(left);
            this.els.ul.append(li);
            child.parent = this;
            this.childs.push(child);
        },
        this);
    },
    isVisible: function() {
        return this.els.contentWrapper.is(":visible");
    },
    collapse: function(e) {
        e.stopPropagation();
        this.isVisible() ? this.hide() : this.show();
        this.trigger("collapse", e);
    },
    show: function() {
        if (!this.rendered) {
            this.renderChilds();
            this.rendered = true;
        }
        this.els.top.html(this.getBrackets().top);
        this.els.contentWrapper.show();
        this.els.down.show();
    },
    hide: function() {
        var b = this.getBrackets();
        this.els.top.html(b.close);
        this.els.contentWrapper.hide();
        this.els.down.hide();
    },
    getBrackets: function() {
        var v = {
            top: '{',
            bottom: '}',
            close: '{ ... }'
        };
        if (this.type == 'array') {
            v = {
                top: '[',
                bottom: ']',
                close: '[ ... ]'
            };
        };
        v.bottom = (this.isLast) ? v.bottom: v.bottom + ',';
        v.close = (this.isLast) ? v.close: v.close + ',';
        return v;
    },
    mouseover: function(e) {
        e.stopPropagation();
        this.trigger("mouseover", e, this.path);
    },
    mouseout: function(e) {
        e.stopPropagation();
        this.trigger("mouseout", e);
    },
    expandAll: function() {
        _.each(this.childs,
        function(child) {
            if (child instanceof PrettyJSON.view.Node) {
                child.show();
                child.expandAll();
            }
        },
        this);
        this.show();
    },
    collapseAll: function() {
        _.each(this.childs,
        function(child) {
            if (child instanceof PrettyJSON.view.Node) {
                child.hide();
                child.collapseAll();
            }
        },
        this);
        if (this.level != 1)
        this.hide();
    }
});
selectPrettySpan = function(_this,e){//asinw add
    var range, selection;
    if (window.getSelection && document.createRange) {
        selection = window.getSelection();
        range = document.createRange();
        range.selectNodeContents(_this);
        selection.removeAllRanges();
        selection.addRange(range);
    } else if (document.selection && document.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(_this);
        range.select();
    }
}
hidePrettyImg = function(_this,e){//asinw add
	var imgDiv = document.getElementById('pretty_img_div');
	if (imgDiv)
	{
		imgDiv.style.display='none';
	}
}
showPrettyImg = function(_this,e){//asinw add
	var link = _this.innerHTML;
	if (link.match(/(image|jpg|jpeg|png|gif)/g))
	{
		var imgDiv = document.getElementById('pretty_img_div');
		if (!imgDiv)
		{
			imgDiv = document.createElement('div');
			imgDiv.setAttribute('id','pretty_img_div');
			imgDiv.style.cssText = 'position:absolute;border:1px solid #333;background:#f7f5d1;padding:1px;color:#333;display:none;z-index:9999;';
			document.body.appendChild(imgDiv);
		}
		imgDiv.style.top     = (e.pageY+20) + "px";
		imgDiv.style.right   =  (document.body.clientWidth - e.pageX + 20) + "px";
		imgDiv.innerHTML     =  '<img src="'+link+'"/>';
		imgDiv.style.display = 'block';
	}
	return false;
}

previewThisData = function(_this,e){
    var eTarget = (e.target)?e.target:e.srcElement;
    if (eTarget == _this)
    {
        var s = decodeURIComponent(_this.getAttribute ('el-string'));
        if (s.match(/^<.*?>[\s\S]*<.*?>$/g))
        {
            OpenWindow = window.open("", "newwin", "height=220,width=470,toolbar=no,scrollbars=" + scroll + ",menubar=no");;
            OpenWindow.document.write(s) ;
            OpenWindow.document.close() ;
            self.name = "main";
        }
    }
}

PrettyJSON.view.Leaf = Backbone.View.extend({
    tagName: 'span',
    data: null,
    level: 0,
    path: '',
    type: 'string',
    isLast: true,
    events: {
        "mouseover .leaf-container": "mouseover",
        "mouseout .leaf-container": "mouseout"
    },
    initialize: function(opt) {
        this.options = opt;
        this.data = this.options.data;
        this.level = this.options.level;
        this.path = this.options.path;
        this.type = this.getType();
        this.dateFormat = this.options.dateFormat;
        this.isLast = _.isUndefined(this.options.isLast) ? this.isLast: this.options.isLast;
        this.render();
    },
    getType: function() {
        var m = 'string';
        var d = this.data;
        if (_.isNumber(d)) m = 'number';
        else if (_.isBoolean(d)) m = 'boolean';
        else if (_.isDate(d)) m = 'date';
        return m;
    },
    getState: function() {
        var coma = this.isLast ? '': ',';
        var state = {
            data: this.data,
            level: this.level,
            path: this.path,
            type: this.type,
            coma: coma
        };
        return state;
    },
    render: function() {
        var state = this.getState();
        if (state.type == "date" && this.dateFormat) {
            state.data = PrettyJSON.util.dateFormat(this.data, this.dateFormat);
        }
        else if (typeof(state.data) == "string" )//asinw add
        {
	        state.data = state.data.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');//转义使html代码无效
	        state.data = state.data.replace(/((http[^ ,"']+|data:image[^ "']+))/g,function(link){
	        	return '<a target=_blank '+(link.match(/(image|jpg|jpeg|png|gif)/g)?'onmouseover="showPrettyImg(this,event)" onmouseout="hidePrettyImg(this,event)" style="color:gray;"':'')+' href="'+link+'" >'+link+'</a>';
	        });
        }
        this.tpl = _.template(PrettyJSON.tpl.Leaf, state);
        $(this.el).html(this.tpl);
        return this;
    },
    mouseover: function(e) {
        e.stopPropagation();
        var path = this.path + '&nbsp;:&nbsp;<span class="' + this.type + '"><b>' + this.data + '</b></span>';
        this.trigger("mouseover", e, path);
    },
    mouseout: function(e) {
        e.stopPropagation();
        this.trigger("mouseout", e);
    }
});
